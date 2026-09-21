<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDamageReportRequest;
use App\Models\DamageReport;
use App\Models\Facility;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DamageReportController extends Controller
{
    /**
     * US7 — Tampilkan riwayat laporan milik pengguna yang sedang login.
     */
    public function index(): View
    {
        $reports = DamageReport::with('facility')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('reports.index', compact('reports'));
    }

    /**
     * US6 — Tampilkan form untuk membuat laporan kerusakan baru.
     */
    public function create(): View
    {
        $facilities = Facility::orderBy('name')->get(['id', 'name']);

        return view('reports.create', compact('facilities'));
    }

    /**
     * US6 — Simpan laporan kerusakan baru.
     *
     * - user_id diambil dari auth()->id(), BUKAN dari request
     * - status otomatis 'baru' (Status::REPORT_NEW), BUKAN dari request
     * - Foto disimpan ke folder 'reports' dengan nama acak server
     */
    public function store(StoreDamageReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Upload foto — nama file diacak oleh Storage facade
        $photoPath = $request->file('photo')->store('reports', 'public');

        $report = new DamageReport($validated);
        $report->user_id = auth()->id();
        $report->status = Status::REPORT_NEW;
        $report->photo_path = $photoPath;
        $report->save();

        return redirect()
            ->route('reports.index')
            ->with('success', 'Laporan kerusakan berhasil dikirim.');
    }

    /**
     * US7 — Tampilkan detail laporan.
     *
     * Ownership check: abort 403 jika laporan bukan milik user yang login.
     */
    public function show(DamageReport $report): View
    {
        abort_unless($report->user_id === auth()->id(), 403);

        $report->load('facility');

        return view('reports.show', compact('report'));
    }
}

