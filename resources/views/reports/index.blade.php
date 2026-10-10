@extends('layouts.app')

@section('title', 'Status Laporan Saya - Fasilitas Kampus')

@section('content')
<div class="fotel-container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 pb-3 border-bottom gap-2">
        <div>
            <div class="small text-uppercase font-mono text-muted">Layanan Sivitas</div>
            <h1 class="fs-2 fw-bold text-dark mb-1 tracking-tight">Status Laporan Kerusakan</h1>
            <p class="text-secondary small mb-0">Pantau status penanganan dan perbaikan fasilitas yang telah Anda ajukan.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn-fotel-secondary py-1 px-3" data-ajax-refresh>Perbarui Laporan</button>
            <a href="{{ route('reports.create') }}" class="btn-fotel-gold py-1 px-3">
                + Buat Laporan Baru
            </a>
        </div>
    </div>

    {{-- Flash message --}}
    @if (session('success'))
        <div class="alert alert-success border rounded d-flex align-items-center justify-content-between mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="bg-white border rounded overflow-hidden">
        <div class="p-0">
            @if ($reports->isEmpty())
                <div class="text-center py-5 text-muted">
                    <p class="mb-1 fs-5 fw-bold text-dark">Belum Ada Laporan Kerusakan</p>
                    <p class="small text-muted mb-3">Klik tombol <strong>"+ Buat Laporan Baru"</strong> untuk melaporkan kerusakan fasilitas.</p>
                    <a href="{{ route('reports.create') }}" class="btn-fotel-gold py-1 px-3">
                        + Buat Laporan Baru
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light font-mono" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">
                            <tr>
                                <th class="py-3 ps-4" style="width: 50px;">No</th>
                                <th class="py-3">Fasilitas</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Deskripsi</th>
                                <th class="py-3">Tanggal</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Catatan Resolusi</th>
                                <th class="py-3 pe-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.88rem;">
                            @foreach ($reports as $report)
                                <tr>
                                    <td class="ps-4 font-mono text-muted">{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $report->facility->name }}</td>
                                    <td>
                                        <span class="fotel-badge fotel-badge-slate font-mono">{{ $report->categoryLabel() }}</span>
                                        @if ($report->category === 'lainnya' && $report->other_category)
                                            <br><small class="text-muted font-mono">{{ $report->other_category }}</small>
                                        @endif
                                    </td>
                                    <td class="text-secondary">{{ Str::limit($report->description, 50) }}</td>
                                    <td class="font-mono small">{{ $report->created_at->translatedFormat('d M Y') }}</td>
                                    <td><span class="badge {{ $report->statusBadge() }}">{{ $report->statusLabel() }}</span></td>
                                    <td class="text-muted small">
                                        @if ($report->resolution_note)
                                            {{ Str::limit($report->resolution_note, 60) }}
                                        @else
                                            <em>Belum ditangani</em>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('reports.show', $report) }}" class="btn-fotel-secondary py-1 px-3" style="font-size: 0.78rem;">
                                            Detail
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
