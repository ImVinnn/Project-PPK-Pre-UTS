@extends('layouts.app')

@section('title', 'Dashboard Petugas')

@section('content')
<style>
    .hero-header {
        background-color: #1e2a38;
        border-bottom: 3px solid #ab7a2c;
    }
    .card-custom {
        border: 1px solid #e2ded4;
    }
    .stat-number {
        color: #1e2a38;
    }
    .btn-gold {
        background-color: #ab7a2c;
        color: #ffffff;
        border: none;
    }
    .btn-gold:hover {
        background-color: #926622;
        color: #ffffff;
    }
</style>

<div class="hero-header py-4 text-white mb-4">
    <div class="container">
        <h2 class="fw-bold mb-1">Dashboard Petugas</h2>
        <p class="mb-0 text-light opacity-75">Ringkasan antrean reservasi dan laporan kerusakan yang masih menunggu.</p>
    </div>
</div>

<div class="container mb-5">

    {{-- Kartu ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-custom shadow-sm rounded-3 h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 fw-bold stat-number">{{ $pendingReservationsCount }}</div>
                    <div class="text-muted small text-uppercase">Reservasi Pending</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom shadow-sm rounded-3 h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 fw-bold stat-number">{{ $newReportsCount }}</div>
                    <div class="text-muted small text-uppercase">Laporan Baru</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom shadow-sm rounded-3 h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 fw-bold stat-number">{{ $inProgressReportsCount }}</div>
                    <div class="text-muted small text-uppercase">Laporan Diproses</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Antrean reservasi pending --}}
    <div class="card card-custom shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Reservasi Menunggu Persetujuan</h5>
            <a href="{{ route('officer.reservations.index') }}" class="small">Lihat semua &rarr;</a>
        </div>
        <div class="card-body p-0">
            @if ($pendingReservations->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-0 fs-5">Tidak ada reservasi pending. Antrean bersih!</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4">Pemohon</th>
                                <th class="py-3">Fasilitas</th>
                                <th class="py-3">Waktu Penggunaan</th>
                                <th class="py-3">Jumlah</th>
                                <th class="py-3">Diajukan Sejak</th>
                                <th class="py-3">Penanda</th>
                                <th class="py-3 pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingReservations as $reservation)
                                @php
                                    $secondsUntilStart = $reservation->start_time->timestamp - now()->timestamp;
                                    $isExpired = $secondsUntilStart <= 0;
                                    $isSoon = ! $isExpired && $secondsUntilStart <= 86400;
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-semibold">{{ $reservation->user->name ?? '-' }}</span>
                                        <br>
                                        <small class="text-muted">{{ $reservation->user->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $reservation->facility->name ?? '-' }}</td>
                                    <td class="small">
                                        {{ $reservation->start_time->locale('id')->translatedFormat('l, d M Y, H:i') }} WIB
                                    </td>
                                    <td>
                                        @if ($reservation->facility?->isEquipment())
                                            {{ $reservation->quantity }} unit
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        {{ $reservation->created_at->locale('id')->diffForHumans() }}
                                    </td>
                                    <td>
                                        @if ($isExpired)
                                            <span class="badge bg-dark">Kedaluwarsa</span>
                                        @elseif ($isSoon)
                                            <span class="badge bg-danger">Segera</span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-center">
                                        <a href="{{ route('officer.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-primary px-3">
                                            Proses
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

    {{-- Antrean laporan kerusakan --}}
    <div class="card card-custom shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Laporan Kerusakan Menunggu Tindak Lanjut</h5>
            <a href="{{ route('officer.reports.index') }}" class="small">Lihat semua &rarr;</a>
        </div>
        <div class="card-body p-0">
            @if ($queuedReports->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-0 fs-5">Tidak ada laporan baru atau diproses. Antrean bersih!</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4">Pelapor</th>
                                <th class="py-3">Fasilitas</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Dilaporkan Sejak</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($queuedReports as $report)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-semibold">{{ $report->user->name ?? '-' }}</span>
                                        <br>
                                        <small class="text-muted">{{ $report->user->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $report->facility->name ?? '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ $report->categoryLabel() }}</span></td>
                                    <td class="small text-muted">
                                        {{ $report->created_at->locale('id')->diffForHumans() }}
                                    </td>
                                    <td><span class="badge {{ $report->statusBadge() }}">{{ $report->statusLabel() }}</span></td>
                                    <td class="pe-4 text-center">
                                        <a href="{{ route('officer.reports.show', $report) }}" class="btn btn-sm btn-gold px-3">
                                            Tindak Lanjut
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
