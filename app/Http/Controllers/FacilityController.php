<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Facility;
use App\Services\ReservationAvailabilityService;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function __construct(
        private readonly ReservationAvailabilityService $availabilityService
    ) {}

    /**
     * Display a listing of facilities with filters (US1 & US2).
     */
    public function index(Request $request): View
    {
        $query = Facility::query()
            ->with(['faculty', 'building', 'roomDetail', 'equipmentDetail'])
            ->where('status', '!=', Status::FACILITY_INACTIVE);

        // Filter by type
        if ($request->filled('tipe') && in_array($request->string('tipe')->value(), Status::FACILITY_TYPES, true)) {
            $query->where('type', $request->string('tipe'));
        }

        // Filter by location / keyword
        if ($request->filled('lokasi')) {
            $keyword = '%'.$request->string('lokasi').'%';
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', $keyword)
                    ->orWhere('location_detail', 'like', $keyword)
                    ->orWhereHas('building', fn ($b) => $b->where('name', 'like', $keyword)->orWhere('code', 'like', $keyword))
                    ->orWhereHas('faculty', fn ($f) => $f->where('name', 'like', $keyword)->orWhere('code', 'like', $keyword));
            });
        }

        // Filter by minimum capacity
        if ($request->filled('kapasitas_min') && is_numeric($request->input('kapasitas_min'))) {
            $minCap = (int) $request->input('kapasitas_min');
            if ($minCap > 0) {
                $query->where('capacity', '>=', $minCap);
            }
        }

        $facilities = $query->orderBy('name')->paginate(12)->withQueryString();

        // Statistics for header
        $stats = [
            'total' => Facility::query()->where('status', '!=', Status::FACILITY_INACTIVE)->count(),
            'active' => Facility::query()->where('status', Status::FACILITY_ACTIVE)->count(),
            'maintenance' => Facility::query()->where('status', Status::FACILITY_MAINTENANCE)->count(),
        ];

        return view('facilities.index', compact('facilities', 'stats'));
    }

    /**
     * Display the specified facility and its 26 daily availability slots (US1).
     */
    public function show(Request $request, Facility $facility): View
    {
        $facility->loadMissing(['faculty', 'building', 'roomDetail', 'equipmentDetail']);

        // Selected date defaults to today in Asia/Jakarta, capped to +90 days
        $today = Carbon::today(config('app.timezone', 'Asia/Jakarta'));
        $maxDate = $today->copy()->addDays(Status::MAX_ADVANCE_DAYS);

        $selectedDateString = $request->input('date', $today->toDateString());
        try {
            $selectedDate = Carbon::parse($selectedDateString, config('app.timezone', 'Asia/Jakarta'))->startOfDay();
            if ($selectedDate->lt($today)) {
                $selectedDate = $today->copy();
            } elseif ($selectedDate->gt($maxDate)) {
                $selectedDate = $maxDate->copy();
            }
        } catch (\Throwable) {
            $selectedDate = $today->copy();
        }

        $slots = $this->availabilityService->getDailyAvailability($facility, $selectedDate);
        $maxDurationMinutes = Status::MAX_DURATION_MINUTES[$facility->type] ?? 180;

        return view('facilities.show', [
            'facility' => $facility,
            'selectedDate' => $selectedDate->toDateString(),
            'today' => $today->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'slots' => $slots,
            'maxDurationMinutes' => $maxDurationMinutes,
        ]);
    }
}
