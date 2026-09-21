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
}
