@extends('layouts.app')

@section('title', 'Dashboard Admin - Fasilitas Kampus')

@php
    use App\Support\Status;

    $facilityCards = [
        [Status::FACILITY_ACTIVE, 'Fasilitas Aktif', 'text-success'],
        [Status::FACILITY_MAINTENANCE, 'Dalam Perbaikan', 'text-warning'],
        [Status::FACILITY_INACTIVE, 'Fasilitas Nonaktif', 'text-secondary'],
    ];
@endphp

@section('content')
<div class="fotel-container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 pb-3 border-bottom gap-2">
        <div>
            <div class="small text-uppercase font-mono text-muted">Panel Administrasi</div>
            <h1 class="fs-2 fw-bold text-dark mb-1 tracking-tight">Dashboard Admin</h1>
            <p class="text-secondary small mb-0">Ringkasan verifikasi akun dan kondisi data master fasilitas.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn-fotel-secondary py-1 px-3" data-ajax-refresh>Perbarui Data</button>
            <a href="{{ route('admin.accounts.index') }}" class="btn-fotel-secondary py-1 px-3">
                Kelola Akun
            </a>
            <a href="{{ route('admin.recaps.index') }}" class="btn-fotel-gold py-1 px-3">
                Rekap
            </a>
        </div>
    </div>

    {{-- Kartu Ringkasan Metrik --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <a href="{{ route('admin.accounts.index') }}" class="d-block p-4 bg-white border rounded h-100 text-center text-decoration-none">
                <div class="display-6 fw-bold font-mono text-dark">{{ $pendingAccountsCount }}</div>
                <div class="text-muted small text-uppercase font-mono mt-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    Akun Menunggu Verifikasi
                </div>
            </a>
        </div>
        @foreach ($facilityCards as [$status, $label, $color])
            <div class="col-6 col-md">
                <a href="{{ route('admin.facilities.index', ['status' => $status]) }}" class="d-block p-4 bg-white border rounded h-100 text-center text-decoration-none">
                    <div class="display-6 fw-bold font-mono {{ $color }}">{{ $facilityCounts[$status] ?? 0 }}</div>
                    <div class="text-muted small text-uppercase font-mono mt-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        {{ $label }}
                    </div>
                </a>
            </div>
        @endforeach
        <div class="col-12 col-md">
            <a href="{{ route('admin.recaps.index') }}" class="d-flex flex-column justify-content-center p-4 bg-white border rounded h-100 text-center text-decoration-none">
                <div class="fs-4 fw-bold text-dark">Rekap &rarr;</div>
                <div class="text-muted small text-uppercase font-mono mt-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    Reservasi &amp; Laporan
                </div>
            </a>
        </div>
    </div>

    {{-- Akun Pending Terlama --}}
    <div class="bg-white border rounded overflow-hidden">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fs-6 fw-bold text-dark text-uppercase font-mono" style="letter-spacing: 0.05em;">
                Akun Menunggu Verifikasi Terlama
            </h5>
            <a href="{{ route('admin.accounts.index') }}" class="small text-decoration-none fw-bold text-dark">
                Lihat Semua &rarr;
            </a>
        </div>
        <div class="p-0">
            @if ($pendingAccounts->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-0 fs-5">Tidak ada akun yang menunggu verifikasi.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light font-mono" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">
                            <tr>
                                <th class="py-3 ps-4">Nama</th>
                                <th class="py-3">Mendaftar</th>
                                <th class="py-3 pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.88rem;">
                            @foreach ($pendingAccounts as $account)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold">{{ $account->name }}</span>
                                        <br>
                                        <small class="text-muted font-mono">{{ $account->email }}</small>
                                    </td>
                                    <td class="small font-mono">
                                        {{ $account->created_at->locale('id')->translatedFormat('d F Y, H:i') }} WIB
                                        <br>
                                        <small class="text-muted">{{ $account->created_at->locale('id')->diffForHumans() }}</small>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <a href="{{ route('admin.accounts.index') }}" class="btn-fotel-secondary py-1 px-3" style="font-size: 0.78rem;">
                                            Verifikasi
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
