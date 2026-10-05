<?php

namespace Tests\Unit\Services;

use App\Models\Building;
use App\Models\DamageReport;
use App\Models\Facility;
use App\Models\Faculty;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RecapService;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecapServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecapService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 08:00:00'));
        $this->service = new RecapService();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function makeFacility(string $type = Status::FACILITY_CLASSROOM, array $overrides = []): Facility
    {
        return Facility::query()->create(array_merge([
            'name' => 'Fasilitas '.fake()->unique()->word(),
            'type' => $type,
            'capacity' => $type === Status::FACILITY_EQUIPMENT ? null : 40,
            'location_detail' => 'Lantai 1',
            'status' => Status::FACILITY_ACTIVE,
        ], $overrides));
    }

    private function makeReservation(
        Facility $facility,
        string $start,
        string $end,
        string $status = Status::RESERVATION_APPROVED,
        int $quantity = 1,
    ): Reservation {
        return Reservation::query()->create([
            'user_id' => $this->user->id,
            'facility_id' => $facility->id,
            'quantity' => $quantity,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => 'Uji rekap',
            'status' => $status,
        ]);
    }

    /** user_id dan status bukan fillable di DamageReport; diisi langsung seperti di controller. */
    private function makeReport(Facility $facility, string $status, string $createdAt): DamageReport
    {
        $this->travelTo(Carbon::parse($createdAt));

        $report = new DamageReport([
            'facility_id' => $facility->id,
            'category' => Status::REPORT_PHYSICAL_DAMAGE,
            'description' => 'Uji rekap laporan',
            'photo_path' => 'reports/dummy.jpg',
        ]);
        $report->user_id = $this->user->id;
        $report->status = $status;
        $report->save();

        return $report;
    }

    private function recap(string $type, string $from, string $to, ?int $facultyId = null, ?int $buildingId = null): array
    {
        return $this->service->recap($type, Carbon::parse($from), Carbon::parse($to), $facultyId, $buildingId);
    }

    public function test_t16_place_minutes_are_clipped_to_the_period(): void
    {
        $room = $this->makeFacility(overrides: ['name' => 'Ruang A101']);
        $this->makeReservation($room, '2026-10-21 09:00:00', '2026-10-21 11:00:00');

        $rows = $this->recap(RecapService::TYPE_PLACES, '2026-10-21 10:00:00', '2026-10-21 12:00:00');

        $this->assertSame([[
            'faculty' => 'Universitas',
            'building' => 'Di luar gedung',
            'facility' => 'Ruang A101',
            'reservation_count' => 1,
            'minutes' => 60,
        ]], $rows);
    }

    public function test_reservations_outside_or_only_touching_the_period_are_not_counted(): void
    {
        $room = $this->makeFacility();
        $this->makeReservation($room, '2026-10-21 07:00:00', '2026-10-21 08:00:00');
        // Berakhir tepat saat periode mulai: tidak beririsan.
        $this->makeReservation($room, '2026-10-21 09:00:00', '2026-10-21 10:00:00');
        $this->makeReservation($room, '2026-10-22 10:00:00', '2026-10-22 11:00:00');

        $this->assertSame([], $this->recap(RecapService::TYPE_PLACES, '2026-10-21 10:00:00', '2026-10-21 12:00:00'));
    }

    public function test_equipment_unit_minutes_multiply_overlap_by_quantity(): void
    {
        $speaker = $this->makeFacility(Status::FACILITY_EQUIPMENT, ['name' => 'Speaker']);
        $room = $this->makeFacility();
        $this->makeReservation($speaker, '2026-10-21 09:00:00', '2026-10-21 11:00:00', quantity: 3);
        $this->makeReservation($room, '2026-10-21 10:00:00', '2026-10-21 11:00:00');

        $rows = $this->recap(RecapService::TYPE_EQUIPMENT, '2026-10-21 10:00:00', '2026-10-21 12:00:00');

        $this->assertCount(1, $rows);
        $this->assertSame('Speaker', $rows[0]['facility']);
        $this->assertSame(1, $rows[0]['reservation_count']);
        $this->assertSame(3, $rows[0]['quantity']);
        $this->assertSame(180, $rows[0]['unit_minutes']);

        // Alat tidak ikut di rekap tempat, dan sebaliknya.
        $places = $this->recap(RecapService::TYPE_PLACES, '2026-10-21 10:00:00', '2026-10-21 12:00:00');
        $this->assertSame([$room->name], array_column($places, 'facility'));
    }

    public function test_only_approved_reservations_are_counted(): void
    {
        $room = $this->makeFacility();
        $this->makeReservation($room, '2026-10-21 10:00:00', '2026-10-21 11:00:00', Status::RESERVATION_APPROVED);

        foreach ([Status::RESERVATION_PENDING, Status::RESERVATION_REJECTED, Status::RESERVATION_CANCELLED] as $status) {
            $this->makeReservation($room, '2026-10-21 10:00:00', '2026-10-21 11:00:00', $status);
        }

        $rows = $this->recap(RecapService::TYPE_PLACES, '2026-10-21 00:00:00', '2026-10-22 00:00:00');

        $this->assertSame(1, $rows[0]['reservation_count']);
        $this->assertSame(60, $rows[0]['minutes']);
    }

    public function test_reports_are_counted_by_created_at_and_status(): void
    {
        $room = $this->makeFacility();
        $this->makeReport($room, Status::REPORT_NEW, '2026-10-01 08:00:00');
        $this->makeReport($room, Status::REPORT_NEW, '2026-10-10 12:00:00');
        $this->makeReport($room, Status::REPORT_IN_PROGRESS, '2026-10-15 12:00:00');
        $this->makeReport($room, Status::REPORT_COMPLETED, '2026-10-31 23:59:00');
        // Di luar periode [1 Okt, 1 Nov).
        $this->makeReport($room, Status::REPORT_REJECTED, '2026-09-30 23:59:00');
        $this->makeReport($room, Status::REPORT_REJECTED, '2026-11-01 00:00:00');

        $rows = $this->recap(RecapService::TYPE_REPORTS, '2026-10-01 00:00:00', '2026-11-01 00:00:00');

        $this->assertCount(1, $rows);
        $this->assertSame(4, $rows[0]['total']);
        $this->assertSame(2, $rows[0][Status::REPORT_NEW]);
        $this->assertSame(1, $rows[0][Status::REPORT_IN_PROGRESS]);
        $this->assertSame(1, $rows[0][Status::REPORT_COMPLETED]);
        $this->assertSame(0, $rows[0][Status::REPORT_REJECTED]);
    }

    public function test_faculty_and_building_filters(): void
    {
        $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
        $building = Building::factory()->create(['faculty_id' => $faculty->id, 'name' => 'Gedung A']);
        $otherBuilding = Building::factory()->create(['faculty_id' => $faculty->id, 'name' => 'Gedung B']);

        $inA = $this->makeFacility(overrides: ['faculty_id' => $faculty->id, 'building_id' => $building->id]);
        $inB = $this->makeFacility(overrides: ['faculty_id' => $faculty->id, 'building_id' => $otherBuilding->id]);
        $campus = $this->makeFacility();

        foreach ([$inA, $inB, $campus] as $facility) {
            $this->makeReservation($facility, '2026-10-21 10:00:00', '2026-10-21 11:00:00');
            $this->makeReport($facility, Status::REPORT_NEW, '2026-10-21 08:00:00');
        }

        $from = '2026-10-01 00:00:00';
        $to = '2026-11-01 00:00:00';

        $byFaculty = $this->recap(RecapService::TYPE_PLACES, $from, $to, $faculty->id);
        $this->assertEqualsCanonicalizing([$inA->name, $inB->name], array_column($byFaculty, 'facility'));
        $this->assertSame(['Fakultas Teknik', 'Fakultas Teknik'], array_column($byFaculty, 'faculty'));

        $byBuilding = $this->recap(RecapService::TYPE_PLACES, $from, $to, $faculty->id, $building->id);
        $this->assertSame([$inA->name], array_column($byBuilding, 'facility'));
        $this->assertSame('Gedung A', $byBuilding[0]['building']);

        $reports = $this->recap(RecapService::TYPE_REPORTS, $from, $to, buildingId: $otherBuilding->id);
        $this->assertSame([$inB->name], array_column($reports, 'facility'));
    }
}
