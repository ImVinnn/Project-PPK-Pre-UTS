@extends('layouts.app')

@section('title', 'Antrean Laporan Kerusakan — Petugas')

@section('content')
<style>
    .hero-header {
        background-color: #1e2a38;
        border-bottom: 3px solid #ab7a2c;
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

<!-- Header Navy -->
<div class="hero-header py-4 text-white mb-4">
    <div class="container">
        <h2 class="fw-bold mb-1">Antrean Laporan Kerusakan</h2>
        <p class="mb-0 text-light opacity-75">Kelola, verifikasi, dan tindak lanjuti laporan kerusakan fasilitas dari pengguna.</p>
    </div>
</div>

<div class="container mb-5">

    {{-- Flash message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Status -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link {{ empty($selectedStatus) ? 'active' : '' }}"
                   href="{{ route('officer.reports.index') }}">
                    Semua <span class="badge bg-secondary ms-1">{{ $counts['all'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'baru' ? 'active' : '' }}"
                   href="{{ route('officer.reports.index', ['status' => 'baru']) }}">
                    Baru <span class="badge bg-warning text-dark ms-1">{{ $counts['baru'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'diproses' ? 'active' : '' }}"
                   href="{{ route('officer.reports.index', ['status' => 'diproses']) }}">
                    Diproses <span class="badge bg-info text-dark ms-1">{{ $counts['diproses'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'selesai' ? 'active' : '' }}"
                   href="{{ route('officer.reports.index', ['status' => 'selesai']) }}">
                    Selesai <span class="badge bg-success ms-1">{{ $counts['selesai'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $selectedStatus === 'ditolak' ? 'active' : '' }}"
                   href="{{ route('officer.reports.index', ['status' => 'ditolak']) }}">
                    Ditolak <span class="badge bg-danger ms-1">{{ $counts['ditolak'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Tabel Laporan -->
    <div class="card card-custom shadow-sm rounded-3">
        <div class="card-body p-0">
            @if ($reports->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-1 fs-5">Tidak ada laporan kerusakan ditemukan.</p>
                    <p class="small text-secondary">
                        @if ($selectedStatus)
                            Tidak ada laporan dengan status <strong>"{{ $selectedStatus }}"</strong>.
                        @else
                            Belum ada laporan kerusakan yang diajukan oleh pengguna.
                        @endif
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4">No</th>
                                <th class="py-3">Tanggal</th>
                                <th class="py-3">Fasilitas</th>
                                <th class="py-3">Pelapor</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Catatan Resolusi</th>
                                <th class="py-3 pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td class="ps-4">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="small fw-semibold">{{ $report->created_at->format('d M Y') }}</span>
                                        <br>
                                        <small class="text-muted">{{ $report->created_at->format('H:i') }} WIB</small>
                                    </td>
                                    <td class="fw-semibold">{{ $report->facility->name ?? '-' }}</td>
                                    <td>
                                        <span>{{ $report->user->name ?? '-' }}</span>
                                        <br>
                                        <small class="text-muted">{{ $report->user->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $report->categoryLabel() }}</span>
                                        @if ($report->category === 'lainnya' && $report->other_category)
                                            <br><small class="text-muted">{{ $report->other_category }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $report->statusBadge() }}">{{ $report->statusLabel() }}</span>
                                    </td>
                                    <td class="text-muted small">
                                        @if ($report->resolution_note)
                                            {{ Str::limit($report->resolution_note, 45) }}
                                        @else
                                            <em class="opacity-50">-</em>
                                        @endif
                                    </td>
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

                @if ($reports->hasPages())
                    <div class="p-3 border-top d-flex justify-content-center">
                        {{ $reports->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

