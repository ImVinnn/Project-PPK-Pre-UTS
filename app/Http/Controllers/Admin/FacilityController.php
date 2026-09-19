<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Models\Building;
use App\Models\Facility;
use App\Models\Faculty;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FacilityController extends Controller
{
    private const array ROOM_LIKE_TYPES = [
        Status::FACILITY_CLASSROOM,
        Status::FACILITY_HALL,
        Status::FACILITY_LABORATORY,
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $facilities = Facility::query()
            ->with(['faculty', 'building', 'roomDetail', 'equipmentDetail'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.facilities.index', compact('facilities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        [$faculties, $buildings] = $this->masterData();

        return view('admin.facilities.create', compact('faculties', 'buildings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFacilityRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $facility = Facility::query()->create([
                'faculty_id' => $data['faculty_id'],
                'building_id' => $data['building_id'],
                'name' => $data['name'],
                'type' => $data['type'],
                'capacity' => $data['capacity'] ?? null,
                'location_detail' => $data['location_detail'],
                'description' => $data['description'] ?? null,
            ]);

            $this->syncSubtype($facility, $data);
        });

        return to_route('admin.facilities.index')
            ->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Facility $facility): View
    {
        $facility->load(['roomDetail', 'equipmentDetail']);
        [$faculties, $buildings] = $this->masterData();

        return view('admin.facilities.edit', compact('facility', 'faculties', 'buildings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFacilityRequest $request, Facility $facility): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($facility, $data): void {
            $facility->update([
                'faculty_id' => $data['faculty_id'],
                'building_id' => $data['building_id'],
                'name' => $data['name'],
                'type' => $data['type'],
                'capacity' => $data['capacity'] ?? null,
                'location_detail' => $data['location_detail'],
                'description' => $data['description'] ?? null,
            ]);

            $this->syncSubtype($facility, $data);
        });

        return to_route('admin.facilities.index')
            ->with('success', 'Fasilitas berhasil diperbarui.');
    }

    /**
     * Deactivate the specified resource. Facilities are never hard-deleted.
     */
    public function deactivate(Facility $facility): RedirectResponse
    {
        $facility->update(['status' => Status::FACILITY_INACTIVE]);

        return to_route('admin.facilities.index')
            ->with('success', 'Fasilitas berhasil dinonaktifkan.');
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, Faculty>, 1: \Illuminate\Support\Collection<int, Building>}
     */
    private function masterData(): array
    {
        $faculties = Faculty::query()->orderBy('code')->get();
        $buildings = Building::query()->with('faculty')->orderBy('code')->get();

        return [$faculties, $buildings];
    }

    /**
     * Keep facility subtype rows (room_details / equipment_details) in sync
     * with the current type, within the caller's transaction.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncSubtype(Facility $facility, array $data): void
    {
        $type = $data['type'];

        if (in_array($type, self::ROOM_LIKE_TYPES, true)) {
            $facility->equipmentDetail()->delete();
            $facility->roomDetail()->updateOrCreate([], [
                'room_number' => $data['room_number'],
                'floor' => $data['floor'] ?? null,
            ]);

            return;
        }

        if ($type === Status::FACILITY_EQUIPMENT) {
            $facility->roomDetail()->delete();
            $facility->equipmentDetail()->updateOrCreate([], [
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'stock_total' => $data['stock_total'],
                'stock_unavailable' => $data['stock_unavailable'] ?? 0,
            ]);

            return;
        }

        // lapangan: no subtype row.
        $facility->roomDetail()->delete();
        $facility->equipmentDetail()->delete();
    }
}
