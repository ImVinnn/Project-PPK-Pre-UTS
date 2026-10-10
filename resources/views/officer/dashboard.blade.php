@extends('layouts.app')

@section('title', 'Dashboard Petugas - Fasilitas Kampus')

@section('content')
<div class="fotel-container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 pb-3 border-bottom gap-2">
        <div>
            <div class="small text-uppercase font-mono text-muted">Panel Operasional</div>
            <h1 class="fs-2 fw-bold text-dark mb-1 tracking-tight">Dashboard Petugas</h1>
            <p class="text-secondary small mb-0">Ringkasan antrean verifikasi reservasi dan laporan penanganan kerusakan fasilitas.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn-fotel-secondary py-1 px-3" data-ajax-refresh>Perbarui Data</button>
            <a href="{{ route('officer.reservations.index') }}" class="btn-fotel-secondary py-1 px-3">
                Antrean Reservasi
            </a>
            <a href="{{ route('officer.reports.index') }}" class="btn-fotel-gold py-1 px-3">
                Laporan Kerusakan
            </a>
        </div>
    </div>

    {{-- Kartu Ringkasan Metrik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-4 bg-white border rounded h-100 text-center">
                <div class="display-6 fw-bold font-mono text-dark">{{ $pendingReservationsCount }}</div>
                <div class="text-muted small text-uppercase font-mono mt-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    Reservasi Pending
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 bg-white border rounded h-100 text-center">
                <div class="display-6 fw-bold font-mono text-warning">{{ $newReportsCount }}</div>
                <div class="text-muted small text-uppercase font-mono mt-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    Laporan Baru
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 bg-white border rounded h-100 text-center">
                <div class="display-6 fw-bold font-mono text-primary">{{ $inProgressReportsCount }}</div>
                <div class="text-muted small text-uppercase font-mono mt-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    Laporan Diproses
                </div>
            </div>
        </div>
    </div>

    {{-- Antrean Reservasi Pending --}}
    <div class="bg-white border rounded mb-4 overflow-hidden">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fs-6 fw-bold text-dark text-uppercase font-mono" style="letter-spacing: 0.05em;">
                Reservasi Menunggu Persetujuan
            </h5>
            <a href="{{ route('officer.reservations.index') }}" class="small text-decoration-none fw-bold text-dark">
                Lihat Semua &rarr;
            </a>
        </div>
        <div class="p-0">
            @if ($pendingReservations->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-0 fs-5">Tidak ada reservasi pending. Antrean bersih!</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light font-mono" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">
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
                        <tbody style="font-size: 0.88rem;">
                            @foreach ($pendingReservations as $reservation)
                                @php
                                    $secondsUntilStart = $reservation->start_time->timestamp - now()->timestamp;
                                    $isExpired = $secondsUntilStart <= 0;
                                    $isSoon = ! $isExpired && $secondsUntilStart <= 86400;
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold">{{ $reservation->user->name ?? '-' }}</span>
                                        <br>
                                        <small class="text-muted font-mono">{{ $reservation->user->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $reservation->facility->name ?? '-' }}</td>
                                    <td class="small font-mono">
                                        {{ $reservation->start_time->locale('id')->translatedFormat('l, d M Y, H:i') }} WIB
                                    </td>
                                    <td class="font-mono small">
                                        @if ($reservation->facility?->isEquipment())
                                            {{ $reservation->quantity }} unit
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted font-mono">
                                        {{ $reservation->created_at->locale('id')->diffForHumans() }}
                                    </td>
                                    <td>
                                        @if ($isExpired)
                                            <span class="fotel-badge fotel-badge-slate">Kedaluwarsa</span>
                                        @elseif ($isSoon)
                                            <span class="fotel-badge fotel-badge-rose">Segera</span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-center">
                                        <a href="{{ route('officer.reservations.show', $reservation) }}" class="btn-fotel-secondary py-1 px-3" style="font-size: 0.78rem;">
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

    {{-- Antrean Laporan Kerusakan --}}
    <div class="bg-white border rounded overflow-hidden">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fs-6 fw-bold text-dark text-uppercase font-mono" style="letter-spacing: 0.05em;">
                Laporan Kerusakan Menunggu Tindak Lanjut
            </h5>
            <a href="{{ route('officer.reports.index') }}" class="small text-decoration-none fw-bold text-dark">
                Lihat Semua &rarr;
            </a>
        </div>
        <div class="p-0">
            @if ($queuedReports->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-0 fs-5">Tidak ada laporan baru atau diproses. Antrean bersih!</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light font-mono" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">
                            <tr>
                                <th class="py-3 ps-4">Pelapor</th>
                                <th class="py-3">Fasilitas</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Dilaporkan Sejak</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.88rem;">
                            @foreach ($queuedReports as $report)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold">{{ $report->user->name ?? '-' }}</span>
                                        <br>
                                        <small class="text-muted font-mono">{{ $report->user->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $report->facility->name ?? '-' }}</td>
                                    <td><span class="fotel-badge fotel-badge-slate font-mono">{{ $report->categoryLabel() }}</span></td>
                                    <td class="small text-muted font-mono">
                                        {{ $report->created_at->locale('id')->diffForHumans() }}
                                    </td>
                                    <td><span class="badge {{ $report->statusBadge() }}">{{ $report->statusLabel() }}</span></td>
                                    <td class="pe-4 text-center">
                                        <a href="{{ route('officer.reports.show', $report) }}" class="btn-fotel-gold py-1 px-3" style="font-size: 0.78rem;">
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
