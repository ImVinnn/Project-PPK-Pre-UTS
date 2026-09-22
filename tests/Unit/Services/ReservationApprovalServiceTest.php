<?php

namespace Tests\Unit\Services;

use App\Exceptions\ReservationApprovalException;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationApprovalService;
use App\Services\ReservationAvailabilityService;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * These tests run against SQLite in-memory (see phpunit.xml), where
 * lockForUpdate() is accepted but does not actually block concurrent
 * connections the way InnoDB row locks do. They prove the SERVICE'S
 * LOGIC (the six approve() checks, the fresh-read-after-lock behaviour,
 * the reject/cancel guards) is correct — they do not prove concurrency
 * safety under real simultaneous requests. See the "cara menguji
 * concurrency" note in the final summary for a manual MySQL test.
 */
class ReservationApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReservationApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservationApprovalService(new ReservationAvailabilityService());
    }

    private function makeOfficer(): User
    {
        return User::factory()->officer()->create();
    }

    private function makeApplicant(): User
    {
        return User::factory()->create();
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

    private function makeEquipmentFacility(int $stockTotal, int $stockUnavailable = 0): Facility
    {
        $facility = Facility::query()->create([
            'name' => 'Speaker Portable',
            'type' => Status::FACILITY_EQUIPMENT,
            'capacity' => null,
            'location_detail' => 'Gudang',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        // Created via the relation (not EquipmentDetail::query()->create()) so the
        // foreign key is set by Eloquent regardless of EquipmentDetail's $fillable —
        // see the report after this test suite about a latent bug there.
        $facility->equipmentDetail()->create([
            'stock_total' => $stockTotal,
            'stock_unavailable' => $stockUnavailable,
        ]);

        return $facility->fresh();
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
            'purpose' => 'Uji coba',
            'status' => $status,
        ]);
    }

    // -- approve(): happy path -------------------------------------------------

    public function test_approve_succeeds_for_pending_place_reservation(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 08:00:00'));

        $officer = $this->makeOfficer();
        $applicant = $this->makeApplicant();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($applicant, $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $approved = $this->service->approve($reservation, $officer);

        $this->assertSame(Status::RESERVATION_APPROVED, $approved->status);
        $this->assertSame($officer->id, $approved->processed_by);
        $this->assertNotNull($approved->processed_at);
    }

    public function test_approve_allows_adjacent_ranges_not_overlapping(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 08:00:00'));

        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();

        // Existing approved 09:00-10:00; new request starts exactly when it ends.
        $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 10:00:00', '2026-10-01 11:00:00');

        $approved = $this->service->approve($reservation, $officer);

        $this->assertSame(Status::RESERVATION_APPROVED, $approved->status);
    }

    // -- approve(): 3a ----------------------------------------------------------

    public function test_approve_rejects_when_status_is_not_pending(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation(
            $this->makeApplicant(),
            $facility,
            '2026-10-01 09:00:00',
            '2026-10-01 10:00:00',
            Status::RESERVATION_REJECTED,
        );

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('tidak lagi berstatus pending');

        $this->service->approve($reservation, $officer);
    }

    public function test_approve_rereads_fresh_status_ignoring_stale_in_memory_object(): void
    {
        // Proves the service re-reads from the DB after locking instead of
        // trusting whatever status the caller's in-memory object still holds.
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        // Reservation object in hand still says "pending"...
        $this->assertTrue($reservation->isPending());

        // ...but another process already rejected it in the DB.
        Reservation::query()->whereKey($reservation->id)->update(['status' => Status::RESERVATION_REJECTED]);

        $this->expectException(ReservationApprovalException::class);

        $this->service->approve($reservation, $officer);
    }

    // -- approve(): 3b ------------------------------------------------------------

    public function test_approve_rejects_when_start_time_has_passed(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 09:30:00'));

        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('melewati waktu mulai');

        try {
            $this->service->approve($reservation, $officer);
        } finally {
            $this->travelBack();
        }
    }

    // -- approve(): 3c --------------------------------------------------------------

    public function test_approve_rejects_when_facility_is_under_maintenance(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility(['status' => Status::FACILITY_MAINTENANCE]);
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('tidak aktif');

        $this->service->approve($reservation, $officer);
    }

    // -- approve(): 3d ------------------------------------------------------------

    public function test_approve_rejects_when_facility_has_overlapping_approved_reservation(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();

        $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:30:00', '2026-10-01 10:30:00');

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('rentang waktu yang sama');

        $this->service->approve($reservation, $officer);
    }

    // -- approve(): 3e --------------------------------------------------------------

    public function test_approve_rejects_when_applicant_has_other_overlapping_place_reservation(): void
    {
        $officer = $this->makeOfficer();
        $applicant = $this->makeApplicant();
        $facilityA = $this->makeRoomFacility(['name' => 'Ruang A']);
        $facilityB = $this->makeRoomFacility(['name' => 'Ruang B']);

        $this->makeReservation($applicant, $facilityA, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);
        $reservation = $this->makeReservation($applicant, $facilityB, '2026-10-01 09:30:00', '2026-10-01 10:30:00');

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('reservasi tempat lain');

        $this->service->approve($reservation, $officer);
    }

    public function test_approve_allows_place_and_equipment_overlap_for_same_applicant(): void
    {
        $officer = $this->makeOfficer();
        $applicant = $this->makeApplicant();
        $room = $this->makeRoomFacility();
        $equipment = $this->makeEquipmentFacility(stockTotal: 5);

        // Applicant already has an approved PLACE reservation...
        $this->makeReservation($applicant, $room, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);

        // ...and now requests EQUIPMENT at the exact same time. PRD 7.2: allowed.
        $reservation = $this->makeReservation($applicant, $equipment, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $approved = $this->service->approve($reservation, $officer);

        $this->assertSame(Status::RESERVATION_APPROVED, $approved->status);
    }

    // -- approve(): 3f ------------------------------------------------------------------

    public function test_approve_rejects_prd_t08_insufficient_equipment_stock(): void
    {
        // PRD T08: 5 speaker, 1 unavailable (usable 4), approved overlap quantity 2,
        // approve quantity 3 -> ditolak (4 - 2 = 2 < 3).
        $officer = $this->makeOfficer();
        $facility = $this->makeEquipmentFacility(stockTotal: 5, stockUnavailable: 1);

        $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED, quantity: 2);
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', quantity: 3);

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('Stok alat tidak cukup');

        $this->service->approve($reservation, $officer);
    }

    public function test_approve_does_not_overcount_equipment_stock_across_segments(): void
    {
        // Regression test for the "sum across the whole range" bug: stock 5, no
        // unavailable units. Approved A 09:00-10:00 qty 3, approved B 10:00-11:00
        // qty 3. A naive whole-range sum (3 + 3 = 6) would wrongly reject a
        // request for qty 2 spanning 09:00-11:00, even though at any single
        // point in time only 3 units are ever in use (5 - 3 = 2 remain).
        $officer = $this->makeOfficer();
        $facility = $this->makeEquipmentFacility(stockTotal: 5, stockUnavailable: 0);

        $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED, quantity: 3);
        $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 10:00:00', '2026-10-01 11:00:00', Status::RESERVATION_APPROVED, quantity: 3);
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 11:00:00', quantity: 2);

        $approved = $this->service->approve($reservation, $officer);

        $this->assertSame(Status::RESERVATION_APPROVED, $approved->status);
    }

    // -- reject() -----------------------------------------------------------------------

    public function test_reject_requires_non_empty_reason(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $this->expectException(ReservationApprovalException::class);

        $this->service->reject($reservation, $officer, '   ');
    }

    public function test_reject_succeeds_from_pending(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $rejected = $this->service->reject($reservation, $officer, 'Bentrok jadwal internal.');

        $this->assertSame(Status::RESERVATION_REJECTED, $rejected->status);
        $this->assertSame('Bentrok jadwal internal.', $rejected->rejection_reason);
        $this->assertSame($officer->id, $rejected->processed_by);
    }

    public function test_reject_fails_when_fresh_status_is_no_longer_pending(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        // Simulates another officer having approved it first.
        Reservation::query()->whereKey($reservation->id)->update(['status' => Status::RESERVATION_APPROVED]);

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('berstatus pending');

        $this->service->reject($reservation, $officer, 'Terlambat.');
    }

    // -- cancelByOfficer() ----------------------------------------------------------------

    public function test_cancel_by_officer_requires_non_empty_reason(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);

        $this->expectException(ReservationApprovalException::class);

        $this->service->cancelByOfficer($reservation, $officer, '');
    }

    public function test_cancel_by_officer_succeeds_from_approved(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00', Status::RESERVATION_APPROVED);

        $cancelled = $this->service->cancelByOfficer($reservation, $officer, 'Kondisi darurat gedung.');

        $this->assertSame(Status::RESERVATION_CANCELLED, $cancelled->status);
        $this->assertSame('Kondisi darurat gedung.', $cancelled->cancel_reason);
        $this->assertSame($officer->id, $cancelled->cancelled_by);
    }

    public function test_cancel_by_officer_fails_when_status_is_not_approved(): void
    {
        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $reservation = $this->makeReservation($this->makeApplicant(), $facility, '2026-10-01 09:00:00', '2026-10-01 10:00:00');

        $this->expectException(ReservationApprovalException::class);
        $this->expectExceptionMessage('berstatus approved');

        $this->service->cancelByOfficer($reservation, $officer, 'Alasan apa pun.');
    }
}
