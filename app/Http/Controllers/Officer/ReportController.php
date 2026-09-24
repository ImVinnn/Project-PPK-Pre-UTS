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
     */
    public function index(Request $request): View
    {
        $selectedStatus = $request->query('status');

        $query = DamageReport::with(['facility', 'user'])->latest();

        if ($selectedStatus && in_array($selectedStatus, Status::REPORT_STATUSES, true)) {
            $query->where('status', $selectedStatus);
        }

        $reports = $query->paginate(15)->withQueryString();

        $counts = [
            'all'      => DamageReport::count(),
            'baru'     => DamageReport::where('status', Status::REPORT_NEW)->count(),
            'diproses' => DamageReport::where('status', Status::REPORT_IN_PROGRESS)->count(),
            'selesai'  => DamageReport::where('status', Status::REPORT_COMPLETED)->count(),
            'ditolak'  => DamageReport::where('status', Status::REPORT_REJECTED)->count(),
        ];

        return view('officer.reports.index', compact('reports', 'selectedStatus', 'counts'));
    }

    /**
     * US11 & US12 — Detail laporan kerusakan, status fasilitas, & reservasi terdampak.
     */
    public function show(DamageReport $report): View
    {
        $report->load(['facility.equipmentDetail', 'user']);

        // Ambil daftar reservasi approved pada fasilitas ini yang masih/akan berlangsung
        $impactedReservations = collect();
        if ($report->facility) {
            $impactedReservations = $report->facility->reservations()
                ->approved()
                ->with('user')
                ->where('end_time', '>=', now())
                ->orderBy('start_time', 'asc')
                ->get();
        }

        return view('officer.reports.show', compact('report', 'impactedReservations'));
    }

    /**
     * US12 — Perbarui status dan catatan resolusi laporan oleh Petugas.
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

    /**
     * US12 Tambahan — Perbarui status kondisi fasilitas (active / maintenance) & stock_unavailable.
     */
    public function updateFacilityCondition(Request $request, DamageReport $report): RedirectResponse
    {
        $facility = $report->facility;

        if (!$facility) {
            return back()->with('error', 'Fasilitas tidak ditemukan.');
        }

        $validated = $request->validate([
            'facility_status'   => 'required|string',
            'stock_unavailable' => 'nullable|integer|min:0',
        ]);

        // 1. Update status fasilitas (misal: active / maintenance)
        $facility->status = $validated['facility_status'];
        $facility->save();

        // 2. Jika fasilitas berupa Alat, update stock_unavailable
        if ($facility->isEquipment() && $facility->equipmentDetail) {
            $maxStock = $facility->equipmentDetail->stock_total;
            $unavailable = min((int) ($validated['stock_unavailable'] ?? 0), $maxStock);

            $facility->equipmentDetail->update([
                'stock_unavailable' => $unavailable,
            ]);
        }

        return redirect()
            ->route('officer.reports.show', $report)
            ->with('success', 'Kondisi fasilitas dan stok tidak dapat digunakan berhasil diperbarui.');
    }
}