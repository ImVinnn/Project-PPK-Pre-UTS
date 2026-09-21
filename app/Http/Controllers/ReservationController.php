<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Facility;
use App\Models\Reservation;
use App\Services\ReservationAvailabilityService;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationAvailabilityService $availabilityService
    ) {}

    /**
     * Show the form for creating a new reservation (US3).
     */
    public function create(Request $request): View
    {
        $facilities = Facility::query()
            ->with(['faculty', 'building', 'equipmentDetail'])
            ->where('status', Status::FACILITY_ACTIVE)
            ->orderBy('name')
            ->get();

        $selectedFacilityId = $request->input('facility_id');
        $selectedFacility = $facilities->firstWhere('id', $selectedFacilityId);

        $today = Carbon::today(config('app.timezone', 'Asia/Jakarta'))->toDateString();
        $maxDate = Carbon::today(config('app.timezone', 'Asia/Jakarta'))->addDays(Status::MAX_ADVANCE_DAYS)->toDateString();
        $selectedDate = $request->input('date', $today);

        $slots = [];
        if ($selectedFacility) {
            $slots = $this->availabilityService->getDailyAvailability($selectedFacility, $selectedDate);
        }

        $slotDefinitions = $this->availabilityService->generateSlotDefinitions();

        return view('reservations.create', [
            'facilities' => $facilities,
            'selectedFacility' => $selectedFacility,
            'selectedDate' => $selectedDate,
            'today' => $today,
            'maxDate' => $maxDate,
            'slots' => $slots,
            'slotDefinitions' => $slotDefinitions,
        ]);
    }

    /**
     * Store a newly created reservation in storage (US3).
     */
    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $facility = Facility::query()->findOrFail($request->input('facility_id'));

        $startDateTime = $request->getStartDateTime();
        $endDateTime = $request->getEndDateTime();

        $quantity = $facility->isEquipment() ? (int) ($request->input('quantity') ?? 1) : 1;

        $reservation = Reservation::query()->create([
            'user_id' => $request->user()->id,
            'facility_id' => $facility->id,
            'quantity' => $quantity,
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'purpose' => $request->input('purpose'),
            'status' => Status::RESERVATION_PENDING,
        ]);

        return to_route('reservations.index')
            ->with('success', "Pengajuan reservasi #{$reservation->id} untuk fasilitas {$facility->name} berhasil dikirim dan menunggu persetujuan petugas.");
    }

    /**
     * Display a listing of reservations belonging to the authenticated user (US5).
     */
    public function index(Request $request): View
    {
        $reservations = Reservation::query()
            ->where('user_id', $request->user()->id)
            ->with(['facility.building', 'facility.faculty', 'facility.roomDetail', 'facility.equipmentDetail'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('reservations.index', compact('reservations'));
    }

    /**
     * Display the specified reservation detail (US5).
     */
    public function show(Request $request, Reservation $reservation): View
    {
        // Enforce data ownership check (PRD 3.10)
        abort_if($reservation->user_id !== $request->user()->id, 403, 'Anda tidak memiliki hak akses untuk melihat reservasi ini.');

        $reservation->loadMissing(['facility.building', 'facility.faculty', 'facility.roomDetail', 'facility.equipmentDetail', 'processor', 'canceller']);

        return view('reservations.show', compact('reservation'));
    }

    /**
     * Cancel a pending or approved reservation by the user (US4).
     * Enforces the 2-hour minimum notice cutoff rule (PRD 3.6).
     */
    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        // Enforce data ownership check (PRD 3.10)
        abort_if($reservation->user_id !== $request->user()->id, 403, 'Anda tidak memiliki hak akses untuk membatalkan reservasi ini.');

        // Only pending and approved can be cancelled
        if (! in_array($reservation->status, [Status::RESERVATION_PENDING, Status::RESERVATION_APPROVED], true)) {
            return back()->withErrors(['cancel' => 'Reservasi ini tidak dapat dibatalkan karena sudah ditolak atau sebelumnya telah dibatalkan.']);
        }

        $now = now(config('app.timezone', 'Asia/Jakarta'));
        $minutesUntilStart = (int) $now->diffInMinutes($reservation->start_time, false);

        // 2-hour cutoff check: now <= start_time - 2 hours (120 minutes)
        if ($minutesUntilStart < Status::USER_CANCELLATION_NOTICE_MINUTES) {
            return back()->withErrors([
                'cancel' => 'Pembatalan mandiri hanya dapat dilakukan paling lambat 2 jam sebelum waktu mulai reservasi. Karena waktu telah melewati batas, silakan hubungi petugas operasional secara langsung.',
            ]);
        }

        $reservation->update([
            'status' => Status::RESERVATION_CANCELLED,
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => $now,
            'cancel_reason' => $request->input('cancel_reason', 'Dibatalkan mandiri oleh pemohon.'),
        ]);

        return to_route('reservations.index')
            ->with('success', "Reservasi #{$reservation->id} berhasil dibatalkan.");
    }
}
