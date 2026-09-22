<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Reservation;
use App\Support\Status;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * US8 — Ringkasan antrean petugas: reservasi pending dan laporan
     * baru/diproses, supaya tidak ada yang terlewat. Semua diurut
     * created_at lama ke baru dan dibatasi 10 baris per daftar; daftar
     * lengkapnya ada di halaman officer/reservations dan officer/reports.
     */
    public function index(): View
    {
        $pendingReservationsCount = Reservation::query()
            ->where('status', Status::RESERVATION_PENDING)
            ->count();

        $newReportsCount = DamageReport::query()
            ->where('status', Status::REPORT_NEW)
            ->count();

        $inProgressReportsCount = DamageReport::query()
            ->where('status', Status::REPORT_IN_PROGRESS)
            ->count();

        $pendingReservations = Reservation::query()
            ->with(['user', 'facility'])
            ->where('status', Status::RESERVATION_PENDING)
            ->oldest('created_at')
            ->limit(10)
            ->get();

        $queuedReports = DamageReport::query()
            ->with(['user', 'facility'])
            ->whereIn('status', [Status::REPORT_NEW, Status::REPORT_IN_PROGRESS])
            ->oldest('created_at')
            ->limit(10)
            ->get();

        return view('officer.dashboard', [
            'pendingReservationsCount' => $pendingReservationsCount,
            'newReportsCount' => $newReportsCount,
            'inProgressReportsCount' => $inProgressReportsCount,
            'pendingReservations' => $pendingReservations,
            'queuedReports' => $queuedReports,
        ]);
    }
}
