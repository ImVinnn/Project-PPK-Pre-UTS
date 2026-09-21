<?php

namespace Tests\Feature\Reservation;

use App\Models\EquipmentDetail;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreReservationValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $activeUser;
    private Facility $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeUser = User::factory()->create([
            'role' => Status::ROLE_USER,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);

        $this->classroom = Facility::query()->create([
            'name' => 'Ruang 101',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 30,
            'location_detail' => 'Gedung A',
            'status' => Status::FACILITY_ACTIVE,
        ]);
    }

    public function test_guest_cannot_access_reservation_form_or_submit(): void
    {
        $resCreate = $this->get(route('reservations.create'));
        $resCreate->assertRedirect(route('login'));

        $resStore = $this->post(route('reservations.store'), []);
        $resStore->assertRedirect(route('login'));
    }

    public function test_active_user_can_submit_valid_reservation_as_pending(): void
    {
        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        $response = $this->actingAs($this->activeUser)
            ->post(route('reservations.store'), [
                'facility_id' => $this->classroom->id,
                'date' => $date,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Kegiatan Kuliah Pengganti Algoritma',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->activeUser->id,
            'facility_id' => $this->classroom->id,
            'purpose' => 'Kegiatan Kuliah Pengganti Algoritma',
            'status' => Status::RESERVATION_PENDING,
        ]);
    }

    public function test_fails_when_time_is_not_multiple_of_30_minutes(): void
    {
        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        $response = $this->actingAs($this->activeUser)
            ->post(route('reservations.store'), [
                'facility_id' => $this->classroom->id,
                'date' => $date,
                'start_time' => '09:15',
                'end_time' => '10:45',
                'purpose' => 'Kegiatan Kuliah',
            ]);

        $response->assertSessionHasErrors(['start_time', 'end_time']);
    }

    public function test_fails_when_outside_operational_hours(): void
    {
        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        $response = $this->actingAs($this->activeUser)
            ->post(route('reservations.store'), [
                'facility_id' => $this->classroom->id,
                'date' => $date,
                'start_time' => '06:30',
                'end_time' => '08:00',
                'purpose' => 'Kegiatan Pagi Buta',
            ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_fails_when_end_time_is_less_than_or_equal_to_start_time(): void
    {
        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        $response = $this->actingAs($this->activeUser)
            ->post(route('reservations.store'), [
                'facility_id' => $this->classroom->id,
                'date' => $date,
                'start_time' => '10:00',
                'end_time' => '09:00',
                'purpose' => 'Kegiatan Terbalik',
            ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_fails_when_facility_is_in_maintenance(): void
    {
        $maintenanceFacility = Facility::query()->create([
            'name' => 'Lab Rusak',
            'type' => Status::FACILITY_LABORATORY,
            'capacity' => 20,
            'location_detail' => 'Gedung C',
            'status' => Status::FACILITY_MAINTENANCE,
        ]);

        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        $response = $this->actingAs($this->activeUser)
            ->post(route('reservations.store'), [
                'facility_id' => $maintenanceFacility->id,
                'date' => $date,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Mencoba fasilitas rusak',
            ]);

        $response->assertSessionHasErrors('facility_id');
    }

    public function test_fails_when_exceeding_max_duration_for_facility_type(): void
    {
        // Classroom max duration is 180 minutes (3 hours)
        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        $response = $this->actingAs($this->activeUser)
            ->post(route('reservations.store'), [
                'facility_id' => $this->classroom->id,
                'date' => $date,
                'start_time' => '08:00',
                'end_time' => '12:00', // 4 hours = 240 minutes > 180 max!
                'purpose' => 'Kegiatan Kuliah 4 Jam Berturut-turut',
            ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_fails_when_contiguous_chain_exceeds_max_duration(): void
    {
        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        // Existing reservation from 09:00 to 11:00 (2 hours = 120 minutes)
        Reservation::query()->create([
            'user_id' => $this->activeUser->id,
            'facility_id' => $this->classroom->id,
            'start_time' => "{$date} 09:00:00",
            'end_time' => "{$date} 11:00:00",
            'purpose' => 'Sesi 1 Kuliah',
            'status' => Status::RESERVATION_PENDING,
        ]);

        // User tries to add an immediately adjacent slot: 11:00 to 13:00 (2 hours)
        // Cumulative unbroken chain would be 09:00 to 13:00 (4 hours = 240 mins > 180 mins limit!)
        $response = $this->actingAs($this->activeUser)
            ->post(route('reservations.store'), [
                'facility_id' => $this->classroom->id,
                'date' => $date,
                'start_time' => '11:00',
                'end_time' => '13:00',
                'purpose' => 'Sesi 2 Kuliah Lanjutan',
            ]);

        $response->assertSessionHasErrors('start_time');
    }
}
