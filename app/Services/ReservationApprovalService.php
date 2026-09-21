<?php

namespace App\Services;

use App\Exceptions\ReservationApprovalException;
use App\Models\EquipmentDetail;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Illuminate\Support\Facades\DB;

class ReservationApprovalService
{
    public function __construct(
        private readonly ReservationAvailabilityService $availabilityService = new ReservationAvailabilityService(),
    ) {}

    /**
     * Approve a pending reservation, or throw with a clear reason why it can't be approved.
     *
     * Lock order is fixed (user -> facility -> equipment_details -> reservation) across every
     * call so concurrent approvals can only serialize on a shared resource, never deadlock on it.
     */
    public function approve(Reservation $reservation, User $officer): Reservation
    {
        return DB::transaction(function () use ($reservation, $officer) {
            $applicantId = $reservation->user_id;
            $facilityId = $reservation->facility_id;

            User::query()->lockForUpdate()->findOrFail($applicantId);

            $facility = Facility::query()->lockForUpdate()->findOrFail($facilityId);

            $equipmentDetail = null;
            if ($facility->isEquipment()) {
                $equipmentDetail = EquipmentDetail::query()->lockForUpdate()->find($facility->id);
                // Attach the just-locked row so isRangeAvailable() below reads this
                // transaction's locked stock figures instead of lazy-loading a stale copy.
                $facility->setRelation('equipmentDetail', $equipmentDetail);
            }

            $fresh = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            // 3a. Status masih pending.
            if (! $fresh->isPending()) {
                throw new ReservationApprovalException('Reservasi ini sudah diproses dan tidak lagi berstatus pending.');
            }

            // 3b. Pending yang sudah lewat start_time tidak boleh di-approve.
            if (now()->greaterThanOrEqualTo($fresh->start_time)) {
                throw new ReservationApprovalException('Reservasi sudah melewati waktu mulai dan tidak dapat disetujui.');
            }

            // 3c. Fasilitas harus active.
            if ($facility->status !== Status::FACILITY_ACTIVE) {
                throw new ReservationApprovalException('Fasilitas sedang tidak aktif (perbaikan/nonaktif) dan tidak dapat menerima persetujuan.');
            }

            if ($facility->isEquipment()) {
                if (! $equipmentDetail) {
                    throw new ReservationApprovalException('Detail alat untuk fasilitas ini tidak ditemukan.');
                }

                // 3f. Stok dihitung PER SEGMEN 30 menit oleh isRangeAvailable() milik P2 —
                // menjumlah overlap sekaligus untuk seluruh rentang akan over-count.
                $stockAvailable = $this->availabilityService->isRangeAvailable(
                    $facility,
                    $fresh->start_time,
                    $fresh->end_time,
                    $fresh->quantity,
                );

                if (! $stockAvailable) {
                    throw new ReservationApprovalException('Stok alat tidak cukup untuk rentang waktu yang diajukan.');
                }
            } else {
                // 3d. Tempat eksklusif: tidak boleh ada approved lain yang overlap di facility yang sama.
                $facilityConflict = Reservation::query()
                    ->where('facility_id', $facility->id)
                    ->approved()
                    ->overlapping($fresh->start_time, $fresh->end_time)
                    ->exists();

                if ($facilityConflict) {
                    throw new ReservationApprovalException('Sudah ada reservasi lain yang disetujui untuk fasilitas ini pada rentang waktu yang sama.');
                }

                // 3e. HANYA untuk tempat: pemohon tidak boleh punya tempat approved lain yang
                // overlap. Tempat + alat pada waktu sama tetap diizinkan — dijamin karena
                // pembanding di sini difilter tipe-nya bukan alat.
                $userPlaceConflict = Reservation::query()
                    ->where('user_id', $applicantId)
                    ->approved()
                    ->overlapping($fresh->start_time, $fresh->end_time)
                    ->whereHas('facility', fn ($query) => $query->where('type', '!=', Status::FACILITY_EQUIPMENT))
                    ->exists();

                if ($userPlaceConflict) {
                    throw new ReservationApprovalException('Pemohon sudah memiliki reservasi tempat lain yang disetujui pada rentang waktu yang sama.');
                }
            }

            $fresh->status = Status::RESERVATION_APPROVED;
            $fresh->processed_by = $officer->id;
            $fresh->processed_at = now();
            $fresh->save();

            return $fresh->refresh();
        });
    }

    /**
     * Reject a pending reservation. rejection_reason is mandatory.
     */
    public function reject(Reservation $reservation, User $officer, string $reason): Reservation
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new ReservationApprovalException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($reservation, $officer, $reason) {
            // Re-read under lock: if approve() won the race on this same reservation,
            // this call must see the fresh status and fail, not the caller's stale copy.
            $fresh = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if (! $fresh->isPending()) {
                throw new ReservationApprovalException('Hanya reservasi berstatus pending yang dapat ditolak.');
            }

            $fresh->status = Status::RESERVATION_REJECTED;
            $fresh->processed_by = $officer->id;
            $fresh->processed_at = now();
            $fresh->rejection_reason = $reason;
            $fresh->save();

            return $fresh->refresh();
        });
    }

    /**
     * Officer-initiated cancellation of an approved reservation. Not bound by the
     * 2-hour user notice window — cancel_reason is mandatory instead.
     */
    public function cancelByOfficer(Reservation $reservation, User $officer, string $reason): Reservation
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new ReservationApprovalException('Alasan pembatalan wajib diisi.');
        }

        return DB::transaction(function () use ($reservation, $officer, $reason) {
            $fresh = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if (! $fresh->isApproved()) {
                throw new ReservationApprovalException('Hanya reservasi berstatus approved yang dapat dibatalkan petugas.');
            }

            $fresh->status = Status::RESERVATION_CANCELLED;
            $fresh->cancelled_by = $officer->id;
            $fresh->cancelled_at = now();
            $fresh->cancel_reason = $reason;
            $fresh->save();

            return $fresh->refresh();
        });
    }
}
