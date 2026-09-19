<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFacultyRequest;
use App\Http\Requests\Admin\UpdateFacultyRequest;
use App\Models\Faculty;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FacultyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $faculties = Faculty::query()
            ->withCount('buildings')
            ->orderBy('code')
            ->paginate(15);

        return view('admin.faculties.index', compact('faculties'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.faculties.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFacultyRequest $request): RedirectResponse
    {
        Faculty::query()->create($request->validated());

        return to_route('admin.faculties.index')
            ->with('success', 'Fakultas berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Faculty $faculty): View
    {
        return view('admin.faculties.edit', compact('faculty'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty): RedirectResponse
    {
        $faculty->update($request->validated());

        return to_route('admin.faculties.index')
            ->with('success', 'Fakultas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faculty $faculty): RedirectResponse
    {
        $isInUse = $faculty->buildings()->exists()
            || DB::table('facilities')->where('faculty_id', $faculty->id)->exists();

        if ($isInUse) {
            return to_route('admin.faculties.index')
                ->with('error', 'Fakultas tidak dapat dihapus karena masih digunakan gedung atau fasilitas.');
        }

        try {
            $faculty->delete();
        } catch (QueryException) {
            return to_route('admin.faculties.index')
                ->with('error', 'Fakultas tidak dapat dihapus karena masih digunakan data lain.');
        }

        return to_route('admin.faculties.index')
            ->with('success', 'Fakultas berhasil dihapus.');
    }
}
