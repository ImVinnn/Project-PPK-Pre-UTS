<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\User;
use App\Support\Status;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Ringkasan admin: akun menunggu verifikasi dan jumlah fasilitas per
     * status. Jumlah per status dihitung dengan satu query GROUP BY;
     * daftar akun pending dibatasi 5 baris terlama.
     */
    public function index(): View
    {
        $pendingAccountsCount = User::query()
            ->where('account_status', Status::ACCOUNT_PENDING)
            ->count();

        $facilityCounts = Facility::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingAccounts = User::query()
            ->where('account_status', Status::ACCOUNT_PENDING)
            ->oldest('created_at')
            ->oldest('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'pendingAccountsCount' => $pendingAccountsCount,
            'facilityCounts' => $facilityCounts,
            'pendingAccounts' => $pendingAccounts,
        ]);
    }
}
