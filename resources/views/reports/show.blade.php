@extends('layouts.app')

@section('title', 'Detail Laporan Kerusakan')

@section('content')
<style>
    .hero-header {
        background-color: #1e2a38;
        border-bottom: 3px solid #ab7a2c;
    }
    .card-custom {
        border: 1px solid #e2ded4;
    }
    .card-header-custom {
        background-color: #1e2a38;
        color: #ffffff;
    }
    .detail-label {
        font-weight: 600;
        color: #56636d;
        min-width: 180px;
    }
</style>

<!-- Header Navy -->
<div class="hero-header py-4 text-white mb-5">
    <div class="container">
        <h2 class="fw-bold mb-1">Detail Laporan Kerusakan</h2>
        <p class="mb-0 text-light opacity-75">Informasi lengkap laporan kerusakan yang kamu ajukan.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-custom shadow-sm rounded-3">
                <div class="card-header card-header-custom py-3 rounded-top d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Laporan #{{ $report->id }}</h5>
                    <span class="badge {{ $report->statusBadge() }} fs-6">{{ $report->statusLabel() }}</span>
                </div>
                <div class="card-body p-4">

                    <table class="table table-borderless mb-4">
                        <tbody>
                            <tr>
                                <td class="detail-label">Fasilitas</td>
                                <td>{{ $report->facility->name }}</td>
                            </tr>
                            <tr>
                                <td class="detail-label">Kategori</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $report->categoryLabel() }}</span>
                                    @if ($report->category === 'lainnya' && $report->other_category)
                                        &mdash; {{ $report->other_category }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label">Deskripsi</td>
                                <td>{{ $report->description }}</td>
                            </tr>
                            <tr>
                                <td class="detail-label">Tanggal Laporan</td>
                                <td>{{ $report->created_at->format('d M Y, H:i') }} WIB</td>
                            </tr>
                            <tr>
                                <td class="detail-label">Status</td>
                                <td><span class="badge {{ $report->statusBadge() }}">{{ $report->statusLabel() }}</span></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Catatan Resolusi</td>
                                <td>
                                    @if ($report->resolution_note)
                                        {{ $report->resolution_note }}
                                    @else
                                        <em class="text-muted">Belum ada catatan resolusi.</em>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Foto Bukti -->
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-2">Foto Bukti Kerusakan</h6>
                        <img src="{{ Storage::url($report->photo_path) }}"
                             alt="Foto kerusakan"
                             class="img-fluid rounded border"
                             style="max-height: 400px;">
                    </div>

                    <div class="pt-3 border-top">
                        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">← Kembali ke Daftar Laporan</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

