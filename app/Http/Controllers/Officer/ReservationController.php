<?php

namespace App\Http\Controllers\Officer;

use App\Exceptions\ReservationApprovalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Officer\CancelReservationRequest;
use App\Http\Requests\Officer\RejectReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationApprovalService;
use App\Services\ReservationAvailabilityService;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationApprovalService $approvalService,
        private readonly ReservationAvailabilityService $availabilityService,
    ) {}

    /**
     * US8/US9 — Antrean reservasi untuk petugas. Default menampilkan pending,
     * diurutkan created_at lama ke baru (PRD bagian 6, US8).
     */
    public function index(Request $request): View
    {
        $selectedStatus = $request->query('status', Status::RESERVATION_PENDING);

        if (! in_array($selectedStatus, Status::RESERVATION_STATUSES, true)) {
            $selectedStatus = null; // "Semua"
        }

        $reservations = Reservation::query()
            ->with(['user', 'facility'])
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->oldest('created_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => Reservation::query()->count(),
            Status::RESERVATION_PENDING => Reservation::query()->where('status', Status::RESERVATION_PENDING)->count(),
            Status::RESERVATION_APPROVED => Reservation::query()->where('status', Status::RESERVATION_APPROVED)->count(),
            Status::RESERVATION_REJECTED => Reservation::query()->where('status', Status::RESERVATION_REJECTED)->count(),
            Status::RESERVATION_CANCELLED => Reservation::query()->where('status', Status::RESERVATION_CANCELLED)->count(),
        ];

        return view('officer.reservations.index', compact('reservations', 'selectedStatus', 'counts'));
    }

    /**
     * US9/US10 — Detail reservasi beserta informasi konflik/stok (hanya tampilan,
     * bukan pemeriksaan resmi — approve()/reject()/cancelByOfficer() tetap
     * memeriksa ulang semuanya sendiri di dalam transaksi).
     */
    public function show(Reservation $reservation): View
    {
        $reservation->loadMissing([
            'user',
            'facility.faculty',
            'facility.building',
            'facility.roomDetail',
            'facility.equipmentDetail',
            'processor',
            'canceller',
        ]);

        $conflictInfo = $reservation->isPending() ? $this->buildConflictInfo($reservation) : null;

        return view('officer.reservations.show', compact('reservation', 'conflictInfo'));
    }

    public function approve(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->approvalService->approve($reservation, $request->user());
        } catch (ReservationApprovalException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('officer.reservations.show', $reservation)
            ->with('success', "Reservasi #{$reservation->id} berhasil disetujui.");
    }

    public function reject(RejectReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->approvalService->reject($reservation, $request->user(), $request->validated('reason'));
        } catch (ReservationApprovalException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('officer.reservations.show', $reservation)
            ->with('success', "Reservasi #{$reservation->id} berhasil ditolak.");
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->approvalService->cancelByOfficer($reservation, $request->user(), $request->validated('reason'));
        } catch (ReservationApprovalException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('officer.reservations.show', $reservation)
            ->with('success', "Reservasi #{$reservation->id} berhasil dibatalkan.");
    }

    /**
     * Informasi bantuan keputusan untuk reservasi pending: reservasi approved lain
     * yang overlap (tempat), atau sisa stok pada rentang yang sama (alat).
     *
     * @return array<string, mixed>
     */
    private function buildConflictInfo(Reservation $reservation): array
    {
        if ($reservation->facility->isEquipment()) {
            $slots = $this->availabilityService->getDailyAvailability(
                $reservation->facility,
                $reservation->start_time->toDateString(),
            );

            $overlappingSlots = collect($slots)->filter(
                fn (array $slot) => $reservation->start_time->lt($slot['end_datetime'])
                    && $reservation->end_time->gt($slot['start_datetime']),
            );

            return [
                'type' => 'equipment',
                'available' => $overlappingSlots->min('available_quantity') ?? 0,
                'usable_stock' => $overlappingSlots->first()['stock_total'] ?? 0,
            ];
        }

        $conflicts = Reservation::query()
            ->where('facility_id', $reservation->facility_id)
            ->approved()
            ->overlapping($reservation->start_time, $reservation->end_time)
            ->orderBy('start_time')
            ->get(['id', 'start_time', 'end_time']);

        return [
            'type' => 'place',
            'conflicts' => $conflicts,
        ];
    }
}
