@extends('layouts.app')

@section('title', 'Antrean Reservasi — Petugas')

@section('content')
<style>
    .hero-header {
        background-color: #1e2a38;
        border-bottom: 3px solid #ab7a2c;
    }
    .card-custom {
        border: 1px solid #e2ded4;
    }
    .nav-pills .nav-link {
        color: #56636d;
        border-radius: 20px;
        padding: 6px 16px;
        font-weight: 500;
        font-size: 0.9rem;
    }
    .nav-pills .nav-link.active {
        background-color: #1e2a38;
        color: #ffffff;
    }
</style>

<div class="hero-header py-4 text-white mb-4">
    <div class="container">
        <h2 class="fw-bold mb-1">Antrean Reservasi</h2>
        <p class="mb-0 text-light opacity-75">Proses pengajuan reservasi tempat dan alat dari pengguna.</p>
    </div>
</div>

<div class="container mb-5">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-4">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link {{ is_null($selectedStatus) ? 'active' : '' }}"
                   href="{{ route('officer.reservations.index', ['status' => 'all']) }}">
                    Semua <span class="badge bg-secondary ms-1">{{ $counts['all'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'pending' ? 'active' : '' }}"
                   href="{{ route('officer.reservations.index', ['status' => 'pending']) }}">
                    Pending <span class="badge bg-warning text-dark ms-1">{{ $counts['pending'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'approved' ? 'active' : '' }}"
                   href="{{ route('officer.reservations.index', ['status' => 'approved']) }}">
                    Disetujui <span class="badge bg-success ms-1">{{ $counts['approved'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'rejected' ? 'active' : '' }}"
                   href="{{ route('officer.reservations.index', ['status' => 'rejected']) }}">
                    Ditolak <span class="badge bg-danger ms-1">{{ $counts['rejected'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'cancelled' ? 'active' : '' }}"
                   href="{{ route('officer.reservations.index', ['status' => 'cancelled']) }}">
                    Dibatalkan <span class="badge bg-secondary ms-1">{{ $counts['cancelled'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="card card-custom shadow-sm rounded-3">
        <div class="card-body p-0">
            @if ($reservations->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-1 fs-5">Tidak ada reservasi ditemukan.</p>
                    <p class="small text-secondary">
                        @if ($selectedStatus)
                            Tidak ada reservasi dengan status <strong>"{{ $selectedStatus }}"</strong>.
                        @else
                            Belum ada reservasi yang diajukan pengguna.
                        @endif
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4">Diajukan</th>
                                <th class="py-3">Pemohon</th>
                                <th class="py-3">Fasilitas</th>
                                <th class="py-3">Waktu Penggunaan</th>
                                <th class="py-3">Jumlah</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservations as $reservation)
                                @php
                                    $statusLabel = match ($reservation->status) {
                                        'pending' => 'Pending',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        default => 'Dibatalkan',
                                    };
                                    $statusBadge = match ($reservation->status) {
                                        'pending' => 'bg-warning text-dark',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <span class="small">{{ $reservation->created_at->format('d M Y') }}</span>
                                        <br>
                                        <small class="text-muted">{{ $reservation->created_at->format('H:i') }} WIB</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $reservation->user->name ?? '-' }}</span>
                                        <br>
                                        <small class="text-muted">{{ $reservation->user->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $reservation->facility->name ?? '-' }}</td>
                                    <td class="small">
                                        {{ $reservation->start_time->format('d M Y, H:i') }}
                                        &ndash;
                                        {{ $reservation->end_time->format('H:i') }} WIB
                                    </td>
                                    <td>
                                        @if ($reservation->facility?->isEquipment())
                                            {{ $reservation->quantity }} unit
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
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

                @if ($reservations->hasPages())
                    <div class="p-3 border-top d-flex justify-content-center">
                        {{ $reservations->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
