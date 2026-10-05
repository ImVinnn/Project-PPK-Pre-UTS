@extends('layouts.app')

@section('title', 'Detail Reservasi #' . $reservation->id . ' - ' . $reservation->facility->name)

@section('content')
{{-- Breadcrumbs Minimalis Ala Fotel --}}
<nav class="border-bottom small py-3">
    <div class="fotel-container">
        <div class="d-flex align-items-center gap-2 text-muted">
            <a href="{{ route('facilities.index') }}" class="text-muted text-decoration-none">Katalog</a>
            <span>&bull;</span>
            <a href="{{ route('reservations.index') }}" class="text-muted text-decoration-none">Reservasi Saya</a>
            <span>&bull;</span>
            <span class="text-dark fw-semibold">Detail #{{ $reservation->id }}</span>
        </div>
    </div>
</nav>

<section class="fotel-container py-5">
    {{-- Header Detail --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="small text-uppercase font-mono text-muted">ID Reservasi: #{{ str_pad($reservation->id, 5, '0', STR_PAD_LEFT) }}</div>
            <h1 class="fs-2 fw-bold text-dark mb-1 tracking-tight">Detail Reservasi Fasilitas</h1>
            <p class="text-secondary small mb-0">
                Diajukan pada {{ $reservation->created_at->translatedFormat('d F Y, H:i') }} WIB
            </p>
        </div>
        <div>
            @if($reservation->status === \App\Support\Status::RESERVATION_PENDING)
                <span class="fotel-badge fotel-badge-amber fs-6 px-3 py-2">Menunggu Verifikasi Petugas</span>
            @elseif($reservation->status === \App\Support\Status::RESERVATION_APPROVED)
                <span class="fotel-badge fotel-badge-green fs-6 px-3 py-2">Disetujui Petugas</span>
            @elseif($reservation->status === \App\Support\Status::RESERVATION_REJECTED)
                <span class="fotel-badge fotel-badge-rose fs-6 px-3 py-2">Pengajuan Ditolak</span>
            @elseif($reservation->status === \App\Support\Status::RESERVATION_CANCELLED)
                <span class="fotel-badge fotel-badge-slate fs-6 px-3 py-2">Reservasi Dibatalkan</span>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="alert alert-success border mb-4 rounded d-flex align-items-center justify-content-between" role="alert">
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

    @if($errors->has('cancel'))
        <div class="alert alert-danger border mb-4 rounded d-flex align-items-center justify-content-between" role="alert">
            <div class="d-flex align-items-center gap-2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>{{ $errors->first('cancel') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Layout 2 Kolom Fotel --}}
    <div class="row g-5">
        {{-- Kolom Kiri: Rincian Peminjaman & Agenda --}}
        <div class="col-lg-8">
            <div class="p-4 p-md-5 bg-white border rounded mb-4">
                <h5 class="fw-bold mb-4 fs-6 text-uppercase tracking-wider border-bottom pb-2">Informasi Peminjaman</h5>

                <div class="row g-4 mb-4">
                    <div class="col-sm-6">
                        <div class="small text-muted font-mono text-uppercase">Fasilitas Kampus</div>
                        <div class="fw-bold fs-5 text-dark mt-1">{{ $reservation->facility->name }}</div>
                        <div class="small text-secondary">{{ $reservation->facility->location_detail }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted font-mono text-uppercase">Tipe & Kapasitas</div>
                        <div class="fw-bold fs-5 text-dark mt-1">
                            {{ ucfirst(str_replace('_', ' ', $reservation->facility->type)) }}
                        </div>
                        @if($reservation->facility->isEquipment())
                            <div class="small text-secondary">Dipinjam: <strong>{{ $reservation->quantity }} unit</strong></div>
                        @else
                            <div class="small text-secondary">Kapasitas: {{ $reservation->facility->capacity }} orang</div>
                        @endif
                    </div>
                </div>

                <div class="row g-4 mb-4 pt-3 border-top">
                    <div class="col-sm-6">
                        <div class="small text-muted font-mono text-uppercase">Tanggal Penggunaan</div>
                        <div class="fw-bold font-mono fs-5 text-dark mt-1">
                            {{ $reservation->start_time->translatedFormat('l, d F Y') }}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted font-mono text-uppercase">Jam Penggunaan</div>
                        <div class="fw-bold font-mono fs-5 text-dark mt-1 d-flex align-items-center gap-2">
                            <span>{{ $reservation->start_time->format('H:i') }} - {{ $reservation->end_time->format('H:i') }} WIB</span>
                            <span class="fotel-badge fotel-badge-slate font-mono" style="font-size: 0.72rem;">
                                {{ $reservation->durationMinutes() / 60 }} Jam
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Agenda Kegiatan --}}
                <div class="pt-3 border-top">
                    <div class="small text-muted font-mono text-uppercase mb-2">Tujuan / Agenda Kegiatan</div>
                    <div class="p-3 bg-light rounded border text-dark" style="line-height: 1.7;">
                        {{ $reservation->purpose }}
                    </div>
                </div>
            </div>

            {{-- Alasan Penolakan / Pembatalan Jika Ada --}}
            @if($reservation->status === \App\Support\Status::RESERVATION_REJECTED && $reservation->rejection_reason)
                <div class="p-4 bg-danger-subtle border border-danger-subtle rounded mb-4">
                    <h6 class="text-danger fw-bold mb-2">Catatan Penolakan Petugas:</h6>
                    <p class="mb-0 text-dark">{{ $reservation->rejection_reason }}</p>
                </div>
            @endif

            @if($reservation->status === \App\Support\Status::RESERVATION_CANCELLED && $reservation->cancel_reason)
                <div class="p-4 bg-secondary-subtle border border-secondary-subtle rounded mb-4">
                    <h6 class="text-secondary fw-bold mb-2">Alasan Pembatalan:</h6>
                    <p class="mb-0 text-dark">{{ $reservation->cancel_reason }}</p>
                </div>
            @endif
        </div>

        {{-- Kolom Kanan: Audit Trail & Pembatalan --}}
        <div class="col-lg-4">
            <div class="p-4 bg-white border rounded mb-4">
                <h6 class="fw-bold text-dark mb-3 text-uppercase fs-6 tracking-wider">Audit & Siklus Status</h6>

                <ul class="list-unstyled small mb-4 font-mono" style="line-height: 2;">
                    <li>
                        <span class="text-muted">Diajukan:</span><br>
                        <strong>{{ $reservation->created_at->format('d/m/Y H:i') }} WIB</strong>
                    </li>
                    @if($reservation->processed_at)
                        <li class="mt-2">
                            <span class="text-muted">Diproses Petugas:</span><br>
                            <strong>{{ $reservation->processed_at->format('d/m/Y H:i') }} WIB</strong>
                        </li>
                    @endif
                    @if($reservation->cancelled_at)
                        <li class="mt-2">
                            <span class="text-muted">Dibatalkan Pada:</span><br>
                            <strong>{{ $reservation->cancelled_at->format('d/m/Y H:i') }} WIB</strong>
                        </li>
                    @endif
                </ul>

                {{-- Modul Pembatalan Mandiri --}}
                @if($reservation->isCancellableByUser())
                    <div class="pt-3 border-top">
                        <div class="small fw-bold text-danger text-uppercase font-mono mb-1">Pembatalan Mandiri</div>
                        <p class="small text-muted mb-3">
                            Jadwal mulai masih lebih dari 2 jam ke depan. Anda masih dapat membatalkan reservasi ini.
                        </p>
                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="cancel_reason" class="form-label small text-muted">Alasan Pembatalan (Opsional):</label>
                                <input type="text" class="form-control form-control-sm" id="cancel_reason" name="cancel_reason" placeholder="Contoh: Jadwal perkuliahan diundur">
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold">
                                Batalkan Reservasi Ini
                            </button>
                        </form>
                    </div>
                @elseif(in_array($reservation->status, [\App\Support\Status::RESERVATION_PENDING, \App\Support\Status::RESERVATION_APPROVED]))
                    <div class="pt-3 border-top">
                        <div class="p-3 bg-light rounded border small">
                            <strong class="d-block text-dark mb-1">Pembatalan Mandiri Ditutup</strong>
                            <span class="text-muted">
                                Sesuai ketentuan kampus, pembatalan mandiri hanya diizinkan maksimal 2 jam sebelum waktu mulai. Hubungi petugas operasional untuk perubahan darurat.
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            <a href="{{ route('reservations.index') }}" class="btn-fotel-secondary w-100 text-center text-decoration-none">
                &larr; Kembali ke Daftar Reservasi
            </a>
        </div>
    </div>
</section>
@endsection
