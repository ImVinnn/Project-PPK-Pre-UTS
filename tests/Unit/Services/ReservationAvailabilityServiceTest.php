<?php

namespace Tests\Unit\Services;

use App\Models\EquipmentDetail;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationAvailabilityService;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReservationAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservationAvailabilityService();
    }

    public function test_generates_exactly_26_daily_slots(): void
    {
        $slots = $this->service->generateSlotDefinitions();

        $this->assertCount(26, $slots);
        $this->assertSame('07:00', $slots[0]['start_time']);
        $this->assertSame('07:30', $slots[0]['end_time']);
        $this->assertSame('19:30', $slots[25]['start_time']);
        $this->assertSame('20:00', $slots[25]['end_time']);
    }

    public function test_room_availability_considers_approved_but_not_pending_reservations(): void
    {
        $user = User::factory()->create();
        $facility = Facility::query()->create([
            'name' => 'Ruang 101',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 30,
            'location_detail' => 'Gedung A',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        $date = '2026-10-01';

        // Approved reservation at 09:00 - 10:00 (slots 09:00-09:30 and 09:30-10:00)
        Reservation::query()->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => "{$date} 09:00:00",
            'end_time' => "{$date} 10:00:00",
            'purpose' => 'Kuliah',
            'status' => Status::RESERVATION_APPROVED,
        ]);

        // Pending reservation at 10:00 - 11:00 (should NOT block slot availability!)
        Reservation::query()->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => "{$date} 10:00:00",
            'end_time' => "{$date} 11:00:00",
            'purpose' => 'Seminar',
            'status' => Status::RESERVATION_PENDING,
        ]);

        $availability = $this->service->getDailyAvailability($facility, $date);

        $slot0830 = collect($availability)->firstWhere('start_time', '08:30');
        $slot0900 = collect($availability)->firstWhere('start_time', '09:00');
        $slot0930 = collect($availability)->firstWhere('start_time', '09:30');
        $slot1000 = collect($availability)->firstWhere('start_time', '10:00');

        $this->assertTrue($slot0830['is_available']);
        $this->assertFalse($slot0900['is_available']);
        $this->assertFalse($slot0930['is_available']);
        // Pending does not block slot
        $this->assertTrue($slot1000['is_available']);
    }

    public function test_equipment_calculates_pooled_available_stock(): void
    {
        $user = User::factory()->create();
        $facility = Facility::query()->create([
            'name' => 'Proyektor A',
            'type' => Status::FACILITY_EQUIPMENT,
            'capacity' => null,
            'location_detail' => 'Gedung B',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        EquipmentDetail::query()->create([
            'facility_id' => $facility->id,
            'brand' => 'Epson',
            'model' => 'X500',
            'stock_total' => 5,
            'stock_unavailable' => 1, // Usable = 4
        ]);

        $date = '2026-10-01';

        // Approved reservation borrows 3 units at 08:00 - 09:00
        Reservation::query()->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'quantity' => 3,
            'start_time' => "{$date} 08:00:00",
            'end_time' => "{$date} 09:00:00",
            'purpose' => 'Kuliah Tamu',
            'status' => Status::RESERVATION_APPROVED,
        ]);

        $availability = $this->service->getDailyAvailability($facility, $date);

        $slot0800 = collect($availability)->firstWhere('start_time', '08:00');
        $slot0900 = collect($availability)->firstWhere('start_time', '09:00');

        // Slot 08:00 - 08:30 has 4 - 3 = 1 available
        $this->assertSame(1, $slot0800['available_quantity']);
        $this->assertTrue($slot0800['is_available']);

        // Slot 09:00 - 09:30 has all 4 available
        $this->assertSame(4, $slot0900['available_quantity']);
        $this->assertTrue($slot0900['is_available']);
    }

    public function test_maintenance_facility_blocks_all_slots(): void
    {
        $facility = Facility::query()->create([
            'name' => 'Lab Rusak',
            'type' => Status::FACILITY_LABORATORY,
            'capacity' => 20,
            'location_detail' => 'Gedung C',
            'status' => Status::FACILITY_MAINTENANCE,
        ]);

        $availability = $this->service->getDailyAvailability($facility, '2026-10-01');

        $this->assertCount(26, $availability);
        foreach ($availability as $slot) {
            $this->assertFalse($slot['is_available']);
            $this->assertSame('Dalam Perbaikan', $slot['reason']);
        }
    }

    public function test_two_way_contiguous_chain_duration_calculation(): void
    {
        $user = User::factory()->create();
        $facility = Facility::query()->create([
            'name' => 'Kelas B',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 40,
            'location_detail' => 'Gedung A',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        $date = '2026-10-01';

        // Existing reservation 11:00 - 12:00 (60 mins)
        Reservation::query()->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => "{$date} 11:00:00",
            'end_time' => "{$date} 12:00:00",
            'purpose' => 'Rapat',
            'status' => Status::RESERVATION_PENDING,
        ]);

        // User now proposes 09:00 - 11:00 (120 mins). Directly adjacent to 11:00 - 12:00!
        $chainMinutes = $this->service->calculateContiguousChainMinutes(
            $user->id,
            $facility->id,
            Carbon::parse("{$date} 09:00:00"),
            Carbon::parse("{$date} 11:00:00")
        );

        // Chain is 09:00 - 12:00 = 180 minutes!
        $this->assertSame(180, $chainMinutes);
    }
}
