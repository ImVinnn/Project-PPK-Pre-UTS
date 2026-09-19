<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBuildingRequest;
use App\Http\Requests\Admin\UpdateBuildingRequest;
use App\Models\Building;
use App\Models\Faculty;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BuildingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $buildings = Building::query()
            ->with('faculty')
            ->orderBy('code')
            ->paginate(15);

        return view('admin.buildings.index', compact('buildings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $faculties = Faculty::query()->orderBy('code')->get();

        return view('admin.buildings.create', compact('faculties'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBuildingRequest $request): RedirectResponse
    {
        Building::query()->create($request->validated());

        return to_route('admin.buildings.index')
            ->with('success', 'Gedung berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Building $building): View
    {
        $faculties = Faculty::query()->orderBy('code')->get();

        return view('admin.buildings.edit', compact('building', 'faculties'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBuildingRequest $request, Building $building): RedirectResponse
    {
        $building->update($request->validated());

        return to_route('admin.buildings.index')
            ->with('success', 'Gedung berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Building $building): RedirectResponse
    {
        if (DB::table('facilities')->where('building_id', $building->id)->exists()) {
            return to_route('admin.buildings.index')
                ->with('error', 'Gedung tidak dapat dihapus karena masih digunakan fasilitas.');
        }

        try {
            $building->delete();
        } catch (QueryException) {
            return to_route('admin.buildings.index')
                ->with('error', 'Gedung tidak dapat dihapus karena masih digunakan data lain.');
        }

        return to_route('admin.buildings.index')
            ->with('success', 'Gedung berhasil dihapus.');
    }
}
