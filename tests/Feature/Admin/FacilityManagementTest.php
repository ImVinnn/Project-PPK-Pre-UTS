<?php

namespace Tests\Feature\Admin;

use App\Models\Building;
use App\Models\DamageReport;
use App\Models\Facility;
use App\Models\Faculty;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FacilityManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 08:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'faculty_id' => '',
            'building_id' => '',
            'name' => 'Ruang Kelas B201',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 40,
            'location_detail' => 'Lantai 2',
            'room_number' => 'B201',
            'floor' => 2,
        ], $overrides);
    }

    private function equipmentPayload(array $overrides = []): array
    {
        return array_merge([
            'faculty_id' => '',
            'building_id' => '',
            'name' => 'Proyektor Epson',
            'type' => Status::FACILITY_EQUIPMENT,
            'location_detail' => 'Gudang alat',
            'brand' => 'Epson',
            'model' => 'EB-X06',
            'stock_total' => 5,
            'stock_unavailable' => 1,
        ], $overrides);
    }

    private function makeFacility(string $type = Status::FACILITY_CLASSROOM, string $status = Status::FACILITY_ACTIVE): Facility
    {
        return Facility::query()->create([
            'name' => 'Fasilitas '.fake()->unique()->word(),
            'type' => $type,
            'capacity' => $type === Status::FACILITY_EQUIPMENT ? null : 40,
            'location_detail' => 'Lantai 1',
            'status' => $status,
        ]);
    }

    private function makeReservation(Facility $facility, string $start, string $end, string $status = Status::RESERVATION_APPROVED, ?User $user = null): Reservation
    {
        return Reservation::query()->create([
            'user_id' => ($user ?? User::factory()->create())->id,
            'facility_id' => $facility->id,
            'quantity' => 1,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => 'Uji fasilitas admin',
            'status' => $status,
        ]);
    }

    // -- Tambah fasilitas -----------------------------------------------------------

    public function test_admin_can_create_classroom_with_room_detail(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.facilities.store'), $this->payload())
            ->assertRedirect(route('admin.facilities.index'))
            ->assertSessionHasNoErrors();

        $facility = Facility::query()->where('name', 'Ruang Kelas B201')->firstOrFail();
        $this->assertSame(Status::FACILITY_ACTIVE, $facility->fresh()->status);
        $this->assertSame(40, $facility->capacity);
        $this->assertSame('B201', $facility->roomDetail->room_number);
        $this->assertSame(2, (int) $facility->roomDetail->floor);
        $this->assertNull($facility->equipmentDetail);
    }

    public function test_admin_can_create_equipment_with_equipment_detail_and_null_capacity(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.facilities.store'), $this->equipmentPayload())
            ->assertSessionHasNoErrors();

        $facility = Facility::query()->where('name', 'Proyektor Epson')->firstOrFail();
        $this->assertNull($facility->capacity);
        $this->assertNull($facility->roomDetail);
        $this->assertSame(5, (int) $facility->equipmentDetail->stock_total);
        $this->assertSame(1, (int) $facility->equipmentDetail->stock_unavailable);
        $this->assertSame('Epson', $facility->equipmentDetail->brand);
    }

    public function test_admin_can_create_field_without_subtype(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.facilities.store'), $this->payload([
                'name' => 'Lapangan Futsal',
                'type' => Status::FACILITY_FIELD,
                'room_number' => null,
                'floor' => null,
            ]))
            ->assertSessionHasNoErrors();

        $facility = Facility::query()->where('name', 'Lapangan Futsal')->firstOrFail();
        $this->assertNull($facility->roomDetail);
        $this->assertNull($facility->equipmentDetail);
    }

    // -- Validasi -------------------------------------------------------------------

    public function test_capacity_is_required_for_places(): void
    {
        foreach ([Status::FACILITY_CLASSROOM, Status::FACILITY_FIELD] as $type) {
            $this->actingAs($this->admin)
                ->post(route('admin.facilities.store'), $this->payload(['type' => $type, 'capacity' => '']))
                ->assertSessionHasErrors('capacity');
        }

        $this->assertSame(0, Facility::query()->count());
    }

    public function test_capacity_is_rejected_for_equipment(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.facilities.store'), $this->equipmentPayload(['capacity' => 10]))
            ->assertSessionHasErrors('capacity');

        $this->assertSame(0, Facility::query()->count());
    }

    public function test_facility_faculty_must_match_building_faculty(): void
    {
        $faculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();
        $building = Building::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.facilities.store'), $this->payload([
                'faculty_id' => $otherFaculty->id,
                'building_id' => $building->id,
            ]))
            ->assertSessionHasErrors('faculty_id');

        $this->actingAs($this->admin)
            ->post(route('admin.facilities.store'), $this->payload([
                'faculty_id' => $faculty->id,
                'building_id' => $building->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Facility::query()->count());
    }

    public function test_stock_unavailable_cannot_exceed_stock_total(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.facilities.store'), $this->equipmentPayload(['stock_total' => 3, 'stock_unavailable' => 4]))
            ->assertSessionHasErrors('stock_unavailable');

        $this->assertSame(0, Facility::query()->count());
    }

    public function test_type_cannot_change_when_facility_has_reservation(): void
    {
        $facility = $this->makeFacility();
        $facility->roomDetail()->create(['room_number' => 'A1']);
        $this->makeReservation($facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_CANCELLED);

        $this->actingAs($this->admin)
            ->put(route('admin.facilities.update', $facility), $this->payload(['type' => Status::FACILITY_FIELD, 'name' => $facility->name]))
            ->assertSessionHasErrors('type');

        $this->assertSame(Status::FACILITY_CLASSROOM, $facility->fresh()->type);
    }

    public function test_type_cannot_change_when_facility_has_damage_report(): void
    {
        $facility = $this->makeFacility();
        $facility->roomDetail()->create(['room_number' => 'A1']);

        $report = new DamageReport([
            'facility_id' => $facility->id,
            'category' => Status::REPORT_PHYSICAL_DAMAGE,
            'description' => 'Kursi patah',
            'photo_path' => 'reports/dummy.jpg',
        ]);
        $report->user_id = User::factory()->create()->id;
        $report->status = Status::REPORT_NEW;
        $report->save();

        $this->actingAs($this->admin)
            ->put(route('admin.facilities.update', $facility), $this->payload(['type' => Status::FACILITY_HALL, 'name' => $facility->name]))
            ->assertSessionHasErrors('type');

        $this->assertSame(Status::FACILITY_CLASSROOM, $facility->fresh()->type);
    }

    // -- Nonaktifkan & aktifkan kembali ----------------------------------------------

    public function test_deactivate_sets_inactive_without_deleting_data(): void
    {
        $facility = $this->makeFacility();
        $facility->roomDetail()->create(['room_number' => 'A1']);

        $this->actingAs($this->admin)
            ->patch(route('admin.facilities.deactivate', $facility))
            ->assertRedirect(route('admin.facilities.index'))
            ->assertSessionHas('success');

        $this->assertSame(Status::FACILITY_INACTIVE, $facility->fresh()->status);
        $this->assertDatabaseHas('facilities', ['id' => $facility->id]);
        $this->assertDatabaseHas('room_details', ['facility_id' => $facility->id]);
    }

    public function test_activate_turns_inactive_facility_active(): void
    {
        $facility = $this->makeFacility(status: Status::FACILITY_INACTIVE);

        $this->actingAs($this->admin)
            ->patch(route('admin.facilities.activate', $facility))
            ->assertRedirect(route('admin.facilities.index'))
            ->assertSessionHas('success');

        $this->assertSame(Status::FACILITY_ACTIVE, $facility->fresh()->status);
    }

    public function test_activate_is_rejected_for_non_inactive_facility(): void
    {
        foreach ([Status::FACILITY_ACTIVE, Status::FACILITY_MAINTENANCE] as $status) {
            $facility = $this->makeFacility(status: $status);

            $this->actingAs($this->admin)
                ->patch(route('admin.facilities.activate', $facility))
                ->assertRedirect(route('admin.facilities.index'))
                ->assertSessionHas('error');

            $this->assertSame($status, $facility->fresh()->status);
        }
    }

    public function test_index_shows_activate_only_for_inactive_and_deactivate_for_others(): void
    {
        $inactive = $this->makeFacility(status: Status::FACILITY_INACTIVE);
        $active = $this->makeFacility();

        $this->actingAs($this->admin)
            ->get(route('admin.facilities.index'))
            ->assertOk()
            ->assertSee('id="activate-modal-'.$inactive->id.'"', false)
            ->assertDontSee('id="deactivate-modal-'.$inactive->id.'"', false)
            ->assertSee('id="deactivate-modal-'.$active->id.'"', false)
            ->assertDontSee('id="activate-modal-'.$active->id.'"', false);
    }

    public function test_deactivate_warns_about_future_approved_reservations_and_keeps_them(): void
    {
        // Jangan bergantung pada APP_LOCALE di .env masing-masing anggota.
        Carbon::setLocale('id');
        $facility = $this->makeFacility();
        $applicant = User::factory()->create(['name' => 'Sinta Pemohon']);

        $future = $this->makeReservation($facility, '2026-10-21 09:00:00', '2026-10-21 10:00:00', user: $applicant);
        $past = $this->makeReservation($facility, '2026-10-19 09:00:00', '2026-10-19 10:00:00', user: User::factory()->create(['name' => 'Lalu Pemohon']));
        $pending = $this->makeReservation($facility, '2026-10-22 09:00:00', '2026-10-22 10:00:00', Status::RESERVATION_PENDING, User::factory()->create(['name' => 'Tunggu Pemohon']));

        $this->actingAs($this->admin)
            ->get(route('admin.facilities.index'))
            ->assertOk()
            ->assertSee('1 reservasi disetujui di masa depan')
            ->assertSee('Sinta Pemohon')
            ->assertSee('21 Okt 2026, 09:00')
            ->assertDontSee(route('officer.reservations.show', $future))
            ->assertDontSee('Lalu Pemohon')
            ->assertDontSee('Tunggu Pemohon');

        $this->actingAs($this->admin)
            ->patch(route('admin.facilities.deactivate', $facility))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, '1 reservasi disetujui di masa depan'));

        $this->assertSame(Status::FACILITY_INACTIVE, $facility->fresh()->status);
        $this->assertSame(Status::RESERVATION_APPROVED, $future->fresh()->status);
        $this->assertSame(Status::RESERVATION_APPROVED, $past->fresh()->status);
        $this->assertSame(Status::RESERVATION_PENDING, $pending->fresh()->status);
    }

    // -- Akses ----------------------------------------------------------------------

    /** @return list<array{0: string, 1: string}> */
    private function adminRoutes(Facility $facility): array
    {
        return [
            ['get', route('admin.facilities.index')],
            ['get', route('admin.facilities.create')],
            ['post', route('admin.facilities.store')],
            ['get', route('admin.facilities.edit', $facility)],
            ['put', route('admin.facilities.update', $facility)],
            ['patch', route('admin.facilities.deactivate', $facility)],
            ['patch', route('admin.facilities.activate', $facility)],
        ];
    }

    public function test_guest_user_and_officer_are_rejected_from_every_admin_action(): void
    {
        $facility = $this->makeFacility(status: Status::FACILITY_INACTIVE);

        foreach ($this->adminRoutes($facility) as [$method, $url]) {
            $this->{$method}($url, $this->payload())->assertRedirect(route('login'));
        }

        foreach ([User::factory()->create(), User::factory()->officer()->create()] as $actor) {
            foreach ($this->adminRoutes($facility) as [$method, $url]) {
                $this->actingAs($actor)->{$method}($url, $this->payload())->assertForbidden();
            }
        }

        $this->assertSame(Status::FACILITY_INACTIVE, $facility->fresh()->status);
        $this->assertSame(1, Facility::query()->count());
    }
}
