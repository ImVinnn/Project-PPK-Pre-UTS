<?php

namespace Tests\Feature\Reservation;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'Mahasiswa A',
            'role' => Status::ROLE_USER,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);

        $this->userB = User::factory()->create([
            'name' => 'Mahasiswa B',
            'role' => Status::ROLE_USER,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);

        $this->facility = Facility::query()->create([
            'name' => 'Lab Komputer A',
            'type' => Status::FACILITY_LABORATORY,
            'capacity' => 30,
            'location_detail' => 'Gedung FTI',
            'status' => Status::FACILITY_ACTIVE,
        ]);
    }

    public function test_user_can_view_own_reservations_and_not_others(): void
    {
        $resA = Reservation::query()->create([
            'user_id' => $this->userA->id,
            'facility_id' => $this->facility->id,
            'start_time' => now()->addDays(2)->setTime(9, 0),
            'end_time' => now()->addDays(2)->setTime(11, 0),
            'purpose' => 'Kegiatan Khusus User A',
            'status' => Status::RESERVATION_PENDING,
        ]);

        $resB = Reservation::query()->create([
            'user_id' => $this->userB->id,
            'facility_id' => $this->facility->id,
            'start_time' => now()->addDays(3)->setTime(13, 0),
            'end_time' => now()->addDays(3)->setTime(15, 0),
            'purpose' => 'Kegiatan Khusus User B',
            'status' => Status::RESERVATION_PENDING,
        ]);

        $response = $this->actingAs($this->userA)->get(route('reservations.index'));

        $response->assertOk();
        $response->assertSee(route('reservations.show', $resA));
        $response->assertDontSee(route('reservations.show', $resB));
    }

    public function test_user_can_view_own_reservation_detail(): void
    {
        $resA = Reservation::query()->create([
            'user_id' => $this->userA->id,
            'facility_id' => $this->facility->id,
            'start_time' => now()->addDays(2)->setTime(9, 0),
            'end_time' => now()->addDays(2)->setTime(11, 0),
            'purpose' => 'Praktikum Mandiri Jaringan',
            'status' => Status::RESERVATION_PENDING,
        ]);

        $response = $this->actingAs($this->userA)->get(route('reservations.show', $resA));

        $response->assertOk();
        $response->assertSee('Praktikum Mandiri Jaringan');
        $response->assertSee($this->facility->name);
    }

    public function test_ownership_check_prevents_user_from_viewing_others_reservation(): void
    {
        $resB = Reservation::query()->create([
            'user_id' => $this->userB->id,
            'facility_id' => $this->facility->id,
            'start_time' => now()->addDays(3)->setTime(13, 0),
            'end_time' => now()->addDays(3)->setTime(15, 0),
            'purpose' => 'Kegiatan Rahasia B',
            'status' => Status::RESERVATION_PENDING,
        ]);

        // User A tries to view User B's reservation -> must be 403 Forbidden!
        $response = $this->actingAs($this->userA)->get(route('reservations.show', $resB));

        $response->assertForbidden();
    }

    public function test_user_can_cancel_own_reservation_if_more_than_two_hours_before(): void
    {
        // Reservation starts 5 hours from now
        $resA = Reservation::query()->create([
            'user_id' => $this->userA->id,
            'facility_id' => $this->facility->id,
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(7),
            'purpose' => 'Rapat Divisi',
            'status' => Status::RESERVATION_PENDING,
        ]);

        $response = $this->actingAs($this->userA)->patch(route('reservations.cancel', $resA), [
            'cancel_reason' => 'Ada kendala mendadak',
        ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $resA->id,
            'status' => Status::RESERVATION_CANCELLED,
            'cancelled_by' => $this->userA->id,
            'cancel_reason' => 'Ada kendala mendadak',
        ]);
    }

    public function test_user_cannot_cancel_when_less_than_two_hours_before_start(): void
    {
        // Reservation starts 1 hour (60 minutes) from now -> within 2-hour cutoff!
        $resA = Reservation::query()->create([
            'user_id' => $this->userA->id,
            'facility_id' => $this->facility->id,
            'start_time' => now()->addMinutes(60),
            'end_time' => now()->addMinutes(120),
            'purpose' => 'Kegiatan Kuliah',
            'status' => Status::RESERVATION_APPROVED,
        ]);

        $response = $this->actingAs($this->userA)->patch(route('reservations.cancel', $resA));

        $response->assertSessionHasErrors('cancel');

        $this->assertDatabaseHas('reservations', [
            'id' => $resA->id,
            'status' => Status::RESERVATION_APPROVED, // Remains approved!
        ]);
    }

    public function test_ownership_check_prevents_user_from_cancelling_others_reservation(): void
    {
        $resB = Reservation::query()->create([
            'user_id' => $this->userB->id,
            'facility_id' => $this->facility->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
            'purpose' => 'Kegiatan Milik B',
            'status' => Status::RESERVATION_PENDING,
        ]);

        // User A attempts to cancel User B's reservation -> must be 403 Forbidden!
        $response = $this->actingAs($this->userA)->patch(route('reservations.cancel', $resB));

        $response->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $resB->id,
            'status' => Status::RESERVATION_PENDING,
        ]);
    }
}
