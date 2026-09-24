<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Officer\UpdateReportStatusRequest;
use App\Models\DamageReport;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * US11 — Tampilkan daftar seluruh laporan kerusakan untuk Petugas.
     * Mendukung filter status laporan.
     */
    public function index(Request $request): View
    {
        $selectedStatus = $request->query('status');

        $query = DamageReport::with(['facility', 'user'])->latest();

        if ($selectedStatus && in_array($selectedStatus, Status::REPORT_STATUSES, true)) {
            $query->where('status', $selectedStatus);
        }

        $reports = $query->paginate(15)->withQueryString();

        // Hitung jumlah laporan per status untuk indikator tab filter
        $counts = [
            'all'         => DamageReport::count(),
            'baru'        => DamageReport::where('status', Status::REPORT_NEW)->count(),
            'diproses'    => DamageReport::where('status', Status::REPORT_IN_PROGRESS)->count(),
            'selesai'     => DamageReport::where('status', Status::REPORT_COMPLETED)->count(),
            'ditolak'     => DamageReport::where('status', Status::REPORT_REJECTED)->count(),
        ];

        return view('officer.reports.index', compact('reports', 'selectedStatus', 'counts'));
    }

    /**
     * US11 & US12 — Tampilkan detail laporan kerusakan beserta form tindak lanjut petugas.
     */
    public function show(DamageReport $report): View
    {
        $report->load(['facility', 'user']);

        return view('officer.reports.show', compact('report'));
    }

    /**
     * US12 — Perbarui status dan catatan resolusi laporan oleh Petugas.
     * Sesuai PRD:
     * - resolution_note wajib jika status 'selesai' atau 'ditolak'.
     * - DILARANG menambah/mengisi handled_by atau handled_at.
     * - Status laporan dan status fasilitas independen.
     */
    public function updateStatus(UpdateReportStatusRequest $request, DamageReport $report): RedirectResponse
    {
        $report->status = $request->validated('status');
        $report->resolution_note = $request->validated('resolution_note');
        $report->save();

        return redirect()
            ->route('officer.reports.show', $report)
            ->with('success', 'Status laporan dan catatan resolusi berhasil diperbarui.');
    }
}

