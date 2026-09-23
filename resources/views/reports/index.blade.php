@extends('layouts.app')

@section('title', 'Status Laporan Saya')

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
</style>

<!-- Header Navy -->
<div class="hero-header py-4 text-white mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Status Laporan Kerusakan</h2>
            <p class="mb-0 text-light opacity-75">Pantau status penanganan laporan yang telah kamu ajukan.</p>
        </div>
        <a href="{{ route('reports.create') }}" class="btn btn-gold px-3">+ Buat Laporan Baru</a>
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

    <div class="card card-custom shadow-sm rounded-3">
        <div class="card-body p-0">
            @if ($reports->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-1 fs-5">Belum ada laporan kerusakan.</p>
                    <p>Klik <strong>"+ Buat Laporan Baru"</strong> untuk melaporkan kerusakan fasilitas.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4">No</th>
                                <th class="py-3">Fasilitas</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Deskripsi</th>
                                <th class="py-3">Tanggal</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Catatan Resolusi</th>
                                <th class="py-3 pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td class="ps-4">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $report->facility->name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $report->categoryLabel() }}</span>
                                        @if ($report->category === 'lainnya' && $report->other_category)
                                            <br><small class="text-muted">{{ $report->other_category }}</small>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($report->description, 50) }}</td>
                                    <td>{{ $report->created_at->format('d M Y') }}</td>
                                    <td><span class="badge {{ $report->statusBadge() }}">{{ $report->statusLabel() }}</span></td>
                                    <td class="pe-4 text-muted small">
                                        @if ($report->resolution_note)
                                            {{ Str::limit($report->resolution_note, 60) }}
                                        @else
                                            <em>Belum ditangani</em>
                                        @endif
                                    </td>
                                    <td class="pe-4">
                                        <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-outline-primary">Detail</a>
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