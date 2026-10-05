<?php

namespace Tests\Feature\Admin;

use App\Models\DamageReport;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RecapService;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RecapTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 08:00:00'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function makeFacility(string $name, string $type): Facility
    {
        return Facility::query()->create([
            'name' => $name,
            'type' => $type,
            'capacity' => $type === Status::FACILITY_EQUIPMENT ? null : 40,
            'location_detail' => 'Lantai 1',
            'status' => Status::FACILITY_ACTIVE,
        ]);
    }

    private function makeReservation(User $user, Facility $facility, string $start, string $end, int $quantity = 1): void
    {
        Reservation::query()->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'quantity' => $quantity,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => 'Uji rekap',
            'status' => Status::RESERVATION_APPROVED,
        ]);
    }

    private function makeReport(User $user, Facility $facility, string $status): void
    {
        $report = new DamageReport([
            'facility_id' => $facility->id,
            'category' => Status::REPORT_ELECTRICAL,
            'description' => 'Uji rekap laporan',
            'photo_path' => 'reports/dummy.jpg',
        ]);
        $report->user_id = $user->id;
        $report->status = $status;
        $report->save();
    }

    /** @return list<list<string>> baris CSV tanpa BOM */
    private function parseCsv(string $content): array
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $lines = preg_split('/\R/', trim(substr($content, 3)));

        return array_map(fn (string $line): array => str_getcsv($line, ',', '"', ''), $lines);
    }

    // -- Akses --------------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.recaps.index'))->assertRedirect(route('login'));
        $this->get(route('admin.recaps.export', RecapService::TYPE_PLACES))->assertRedirect(route('login'));
    }

    public function test_user_and_officer_are_forbidden(): void
    {
        foreach ([User::factory()->create(), User::factory()->officer()->create()] as $actor) {
            $this->actingAs($actor)->get(route('admin.recaps.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.recaps.export', RecapService::TYPE_REPORTS))->assertForbidden();
        }
    }

    public function test_admin_sees_current_month_by_default(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.recaps.index'));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool => $filters['from']->toDateTimeString() === '2026-10-01 00:00:00'
            && $filters['to']->toDateTimeString() === '2026-11-01 00:00:00');
        $response->assertSee('Tidak ada reservasi tempat yang disetujui pada periode ini.');
        $response->assertSee('bukan kehadiran nyata');
    }

    // -- Validasi -----------------------------------------------------------------

    public function test_end_before_start_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.recaps.index', ['from' => '2026-10-10T10:00', 'to' => '2026-10-10T09:00']))
            ->assertRedirect(route('admin.recaps.index'))
            ->assertSessionHasErrors(['to' => 'Waktu akhir harus setelah waktu awal.']);
    }

    public function test_range_longer_than_one_year_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.recaps.export', ['type' => RecapService::TYPE_PLACES, 'from' => '2025-10-01T00:00', 'to' => '2026-10-01T00:01']))
            ->assertSessionHasErrors(['to' => 'Rentang periode rekap maksimal 1 tahun.']);
    }

    // -- T17 ----------------------------------------------------------------------

    public function test_t17_csv_matches_page_for_same_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $room = $this->makeFacility('Aula Utama', Status::FACILITY_HALL);
        $speaker = $this->makeFacility('Speaker Portabel', Status::FACILITY_EQUIPMENT);

        $this->makeReservation($user, $room, '2026-10-20 07:00:00', '2026-10-20 09:00:00');
        $this->makeReservation($user, $room, '2026-10-22 13:00:00', '2026-10-22 14:30:00');
        $this->makeReservation($user, $speaker, '2026-10-20 07:00:00', '2026-10-20 10:00:00', 2);
        $this->makeReport($user, $room, Status::REPORT_NEW);
        $this->makeReport($user, $room, Status::REPORT_COMPLETED);

        $query = ['from' => '2026-10-20T08:00', 'to' => '2026-10-23T00:00'];

        $page = $this->actingAs($admin)->get(route('admin.recaps.index', $query));
        $page->assertOk();
        $recaps = $page->viewData('recaps');

        // Sanity: angka yang diharapkan benar-benar muncul di halaman.
        $this->assertSame(150, $recaps[RecapService::TYPE_PLACES][0]['minutes']);
        $this->assertSame(240, $recaps[RecapService::TYPE_EQUIPMENT][0]['unit_minutes']);
        $this->assertSame(2, $recaps[RecapService::TYPE_REPORTS][0]['total']);

        foreach (RecapService::COLUMNS as $type => $columns) {
            $response = $this->actingAs($admin)->get(route('admin.recaps.export', ['type' => $type] + $query));
            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertStringContainsString("rekap-{$type}_20261020-0800_sd_20261023-0000.csv", $response->headers->get('Content-Disposition'));

            $csv = $this->parseCsv($response->streamedContent());

            $this->assertSame(array_values($columns), array_shift($csv));
            $expected = array_map(
                fn (array $row): array => array_map('strval', array_values(array_merge($columns, $row))),
                $recaps[$type],
            );
            $this->assertSame($expected, $csv);

            foreach ($recaps[$type] as $row) {
                foreach ($row as $value) {
                    $page->assertSee((string) $value);
                }
            }
        }
    }
}
