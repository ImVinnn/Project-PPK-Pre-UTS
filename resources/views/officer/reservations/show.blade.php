@extends('layouts.app')

@section('title', 'Detail Reservasi — Petugas')

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
    .card-header-custom {
        background-color: #1e2a38;
        color: #ffffff;
    }
    .detail-label {
        font-weight: 600;
        color: #56636d;
        width: 180px;
    }
</style>

<div class="hero-header py-4 text-white mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Reservasi #{{ $reservation->id }}</h2>
            <p class="mb-0 text-light opacity-75">Periksa detail dan informasi bantuan sebelum mengambil keputusan.</p>
        </div>
        <a href="{{ route('officer.reservations.index') }}" class="btn btn-outline-light btn-sm px-3">
            ← Kembali ke Antrean
        </a>
    </div>
</div>

<div class="container mb-5">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-custom shadow-sm rounded-3">
                <div class="card-header card-header-custom py-3 rounded-top d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Informasi Reservasi</h5>
                    <span class="badge {{ $statusBadge }} fs-6">{{ $statusLabel }}</span>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="detail-label">Pemohon</td>
                                <td>
                                    <strong>{{ $reservation->user->name ?? '-' }}</strong>
                                    <div class="text-muted small">{{ $reservation->user->email ?? '-' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label">Fasilitas</td>
                                <td class="fw-semibold">
                                    {{ $reservation->facility->name ?? '-' }}
                                    <div class="text-muted small fw-normal">
                                        {{ $reservation->facility->faculty->code ?? 'Universitas' }}
                                        &middot;
                                        {{ $reservation->facility->building->name ?? 'Di luar gedung' }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label">Waktu Penggunaan</td>
                                <td>
                                    {{ $reservation->start_time->format('d M Y, H:i') }}
                                    &ndash;
                                    {{ $reservation->end_time->format('H:i') }} WIB
                                </td>
                            </tr>
                            @if ($reservation->facility?->isEquipment())
                                <tr>
                                    <td class="detail-label">Jumlah Diajukan</td>
                                    <td>{{ $reservation->quantity }} unit</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="detail-label">Tujuan Penggunaan</td>
                                <td class="text-break">{{ $reservation->purpose }}</td>
                            </tr>
                            <tr>
                                <td class="detail-label">Diajukan Pada</td>
                                <td>{{ $reservation->created_at->format('d M Y, H:i') }} WIB</td>
                            </tr>

                            @if ($reservation->status === 'rejected')
                                <tr>
                                    <td class="detail-label">Diproses Oleh</td>
                                    <td>{{ $reservation->processor->name ?? '-' }} &middot; {{ $reservation->processed_at?->format('d M Y, H:i') }} WIB</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Alasan Penolakan</td>
                                    <td class="text-break">
                                        <div class="p-2 bg-light border rounded small">{{ $reservation->rejection_reason }}</div>
                                    </td>
                                </tr>
                            @endif

                            @if ($reservation->status === 'approved')
                                <tr>
                                    <td class="detail-label">Disetujui Oleh</td>
                                    <td>{{ $reservation->processor->name ?? '-' }} &middot; {{ $reservation->processed_at?->format('d M Y, H:i') }} WIB</td>
                                </tr>
                            @endif

                            @if ($reservation->status === 'cancelled')
                                <tr>
                                    <td class="detail-label">Dibatalkan Oleh</td>
                                    <td>{{ $reservation->canceller->name ?? '-' }} &middot; {{ $reservation->cancelled_at?->format('d M Y, H:i') }} WIB</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Alasan Pembatalan</td>
                                    <td class="text-break">
                                        <div class="p-2 bg-light border rounded small">{{ $reservation->cancel_reason }}</div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($conflictInfo)
                <div class="card card-custom shadow-sm rounded-3 mt-4">
                    <div class="card-header bg-light py-3 rounded-top">
                        <h6 class="mb-0 fw-semibold">Informasi Bantuan Keputusan</h6>
                        <small class="text-muted">Hanya tampilan — persetujuan tetap diperiksa ulang oleh sistem.</small>
                    </div>
                    <div class="card-body p-4">
                        @if ($conflictInfo['type'] === 'equipment')
                            <p class="mb-1">
                                Sisa stok pada rentang waktu ini: <strong>{{ $conflictInfo['available'] }}</strong>
                                dari <strong>{{ $conflictInfo['usable_stock'] }}</strong> unit layak pakai.
                            </p>
                            @if ($conflictInfo['available'] < $reservation->quantity)
                                <p class="text-danger small mb-0">
                                    Stok tersisa lebih kecil dari jumlah yang diajukan ({{ $reservation->quantity }} unit).
                                </p>
                            @else
                                <p class="text-success small mb-0">Stok mencukupi jumlah yang diajukan.</p>
                            @endif
                        @else
                            @if ($conflictInfo['conflicts']->isEmpty())
                                <p class="text-success small mb-0">Tidak ada reservasi disetujui lain yang bentrok pada rentang waktu ini.</p>
                            @else
                                <p class="text-danger small mb-2">
                                    Ada {{ $conflictInfo['conflicts']->count() }} reservasi disetujui lain yang bentrok:
                                </p>
                                <ul class="small mb-0">
                                    @foreach ($conflictInfo['conflicts'] as $conflict)
                                        <li>
                                            #{{ $conflict->id }} &middot;
                                            {{ $conflict->start_time->format('d M Y, H:i') }}
                                            &ndash;
                                            {{ $conflict->end_time->format('H:i') }} WIB
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="card card-custom shadow-sm rounded-3 sticky-top" style="top: 20px;">
                <div class="card-header card-header-custom py-3 rounded-top">
                    <h5 class="mb-0 fw-semibold">Tindakan Petugas</h5>
                </div>
                <div class="card-body p-4">
                    @if ($reservation->status === 'pending')
                        <p class="small text-muted">Pending belum menjamin persetujuan — periksa informasi bantuan di samping sebelum memutuskan.</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-gold py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#approveModal">
                                Setujui Reservasi
                            </button>
                            <button type="button" class="btn btn-outline-danger py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                Tolak Reservasi
                            </button>
                        </div>
                    @elseif ($reservation->status === 'approved')
                        <p class="small text-muted">Pembatalan oleh petugas tidak terikat batas dua jam, tetapi wajib menyertakan alasan.</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-danger py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                Batalkan Reservasi
                            </button>
                        </div>
                    @else
                        <p class="small text-muted mb-0">Tidak ada tindakan lebih lanjut untuk reservasi berstatus <strong>{{ $statusLabel }}</strong>.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($reservation->status === 'pending')
    {{-- Modal: Setujui --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('officer.reservations.approve', $reservation) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">Setujui Reservasi #{{ $reservation->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Menyetujui reservasi ini akan mengunci fasilitas <strong>{{ $reservation->facility->name ?? '-' }}</strong>
                            untuk rentang waktu yang diajukan. Sistem akan memeriksa ulang bentrok dan stok saat tombol ini ditekan —
                            kalau ternyata sudah tidak aman, persetujuan akan otomatis ditolak dan pesannya ditampilkan di halaman ini.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gold">Ya, Setujui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Tolak --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('officer.reservations.reject', $reservation) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">Tolak Reservasi #{{ $reservation->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Reservasi akan berstatus <strong>ditolak</strong> dan pemohon dapat melihat alasannya di riwayat mereka.</p>
                        <label for="reject_reason" class="form-label fw-semibold">Alasan Penolakan</label>
                        <textarea
                            class="form-control @error('reason') is-invalid @enderror"
                            id="reject_reason"
                            name="reason"
                            rows="4"
                            maxlength="500"
                            required
                            placeholder="Contoh: bentrok dengan agenda pemeliharaan gedung."
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($reservation->status === 'approved')
    {{-- Modal: Batalkan --}}
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('officer.reservations.cancel', $reservation) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelModalLabel">Batalkan Reservasi #{{ $reservation->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Reservasi yang sudah <strong>disetujui</strong> ini akan dibatalkan. Gunakan untuk kondisi mendesak saja —
                            alasan akan terlihat di riwayat pemohon.</p>
                        <label for="cancel_reason" class="form-label fw-semibold">Alasan Pembatalan</label>
                        <textarea
                            class="form-control @error('reason') is-invalid @enderror"
                            id="cancel_reason"
                            name="reason"
                            rows="4"
                            maxlength="500"
                            required
                            placeholder="Contoh: fasilitas mendadak perlu perbaikan darurat."
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Batalkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<script>
    // Buka kembali modal yang relevan kalau validasi server gagal (ada error 'reason').
    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->has('reason') && $reservation->status === 'pending')
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        @elseif ($errors->has('reason') && $reservation->status === 'approved')
            new bootstrap.Modal(document.getElementById('cancelModal')).show();
        @endif
    });
</script>
@endsection
