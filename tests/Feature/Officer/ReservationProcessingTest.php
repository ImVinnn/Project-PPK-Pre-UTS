<?php

namespace Tests\Feature\Officer;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReservationProcessingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeOfficer(): User
    {
        return User::factory()->officer()->create();
    }

    private function makeRoomFacility(array $overrides = []): Facility
    {
        return Facility::query()->create(array_merge([
            'name' => 'Ruang Kelas A101',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 40,
            'location_detail' => 'Gedung A',
            'status' => Status::FACILITY_ACTIVE,
        ], $overrides));
    }

    private function makeReservation(
        User $user,
        Facility $facility,
        string $start,
        string $end,
        string $status = Status::RESERVATION_PENDING,
        int $quantity = 1,
    ): Reservation {
        return Reservation::query()->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'quantity' => $quantity,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => 'Rapat koordinasi',
            'status' => $status,
        ]);
    }

    // -- Akses ------------------------------------------------------------------

    public function test_guest_is_redirected_to_login_from_every_route(): void
    {
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $this->get(route('officer.reservations.index'))->assertRedirect(route('login'));
        $this->get(route('officer.reservations.show', $reservation))->assertRedirect(route('login'));
        $this->patch(route('officer.reservations.approve', $reservation))->assertRedirect(route('login'));
        $this->patch(route('officer.reservations.reject', $reservation), ['reason' => 'x'])->assertRedirect(route('login'));
        $this->patch(route('officer.reservations.cancel', $reservation), ['reason' => 'x'])->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_every_route(): void
    {
        $user = User::factory()->create();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($user, $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $this->actingAs($user)->get(route('officer.reservations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('officer.reservations.show', $reservation))->assertForbidden();
        $this->actingAs($user)->patch(route('officer.reservations.approve', $reservation))->assertForbidden();
        $this->actingAs($user)->patch(route('officer.reservations.reject', $reservation), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($user)->patch(route('officer.reservations.cancel', $reservation), ['reason' => 'x'])->assertForbidden();
    }

    public function test_admin_is_forbidden_from_every_route(): void
    {
        $admin = User::factory()->admin()->create();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $this->actingAs($admin)->get(route('officer.reservations.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('officer.reservations.show', $reservation))->assertForbidden();
        $this->actingAs($admin)->patch(route('officer.reservations.approve', $reservation))->assertForbidden();
        $this->actingAs($admin)->patch(route('officer.reservations.reject', $reservation), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($admin)->patch(route('officer.reservations.cancel', $reservation), ['reason' => 'x'])->assertForbidden();
    }

    // -- approve() ----------------------------------------------------------------

    public function test_officer_can_approve_a_pending_reservation(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 08:00:00'));

        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $response = $this->actingAs($officer)->patch(route('officer.reservations.approve', $reservation));

        $response->assertRedirect(route('officer.reservations.show', $reservation));
        $response->assertSessionHas('success');
        $this->assertSame(Status::RESERVATION_APPROVED, $reservation->fresh()->status);
        $this->assertSame($officer->id, $reservation->fresh()->processed_by);
    }

    public function test_approve_shows_error_and_keeps_pending_on_facility_conflict(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();

        $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:30:00', '2026-10-01 10:30:00');

        $response = $this->actingAs($officer)->patch(route('officer.reservations.approve', $reservation));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(Status::RESERVATION_PENDING, $reservation->fresh()->status);
    }

    public function test_approve_rejects_pending_reservation_past_its_start_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 09:30:00'));

        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        try {
            $response = $this->actingAs($officer)->patch(route('officer.reservations.approve', $reservation));
        } finally {
            $this->travelBack();
        }

        $response->assertSessionHas('error');
        $this->assertSame(Status::RESERVATION_PENDING, $reservation->fresh()->status);
    }

    // -- reject() -----------------------------------------------------------------

    public function test_reject_without_reason_fails_validation(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $response = $this->actingAs($officer)->patch(route('officer.reservations.reject', $reservation), ['reason' => '']);

        $response->assertSessionHasErrors('reason');
        $this->assertSame(Status::RESERVATION_PENDING, $reservation->fresh()->status);
    }

    public function test_officer_can_reject_a_pending_reservation_with_reason(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $response = $this->actingAs($officer)->patch(route('officer.reservations.reject', $reservation), [
            'reason' => 'Bentrok dengan agenda pemeliharaan.',
        ]);

        $response->assertRedirect(route('officer.reservations.show', $reservation));
        $fresh = $reservation->fresh();
        $this->assertSame(Status::RESERVATION_REJECTED, $fresh->status);
        $this->assertSame('Bentrok dengan agenda pemeliharaan.', $fresh->rejection_reason);
        $this->assertSame($officer->id, $fresh->processed_by);
    }

    // -- cancelByOfficer() ----------------------------------------------------------

    public function test_officer_can_cancel_an_approved_reservation_with_reason(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);

        $response = $this->actingAs($officer)->patch(route('officer.reservations.cancel', $reservation), [
            'reason' => 'Fasilitas mendadak perlu perbaikan darurat.',
        ]);

        $response->assertRedirect(route('officer.reservations.show', $reservation));
        $fresh = $reservation->fresh();
        $this->assertSame(Status::RESERVATION_CANCELLED, $fresh->status);
        $this->assertSame('Fasilitas mendadak perlu perbaikan darurat.', $fresh->cancel_reason);
        $this->assertSame($officer->id, $fresh->cancelled_by);
    }

    public function test_cancel_fails_when_reservation_is_not_approved(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $response = $this->actingAs($officer)->patch(route('officer.reservations.cancel', $reservation), [
            'reason' => 'Alasan apa pun.',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(Status::RESERVATION_PENDING, $reservation->fresh()->status);
    }
}
