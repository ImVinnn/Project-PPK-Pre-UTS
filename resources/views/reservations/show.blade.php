@extends('layouts.app')

@section('title', 'Detail Reservasi #' . $reservation->id)

@section('content')
<section class="hero-band" style="background: var(--ink); color: #f5f3ed;">
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}" style="color: var(--brass);">Katalog</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reservations.index') }}" style="color: var(--brass);">Reservasi Saya</a></li>
                <li class="breadcrumb-item active text-light" aria-current="page">#{{ $reservation->id }}</li>
            </ol>
        </nav>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="font-serif fw-semibold display-6 mb-1">Detail Reservasi #{{ $reservation->id }}</h1>
                <p class="mb-0 text-muted" style="color: #c7cdd2 !important;">
                    Fasilitas: <strong>{{ $reservation->facility->name }}</strong> &middot; Diajukan pada {{ $reservation->created_at->translatedFormat('d F Y, H:i') }} WIB
                </p>
            </div>
            <div>
                @if($reservation->status === \App\Support\Status::RESERVATION_PENDING)
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2 border border-warning">Menunggu Verifikasi Petugas</span>
                @elseif($reservation->status === \App\Support\Status::RESERVATION_APPROVED)
                    <span class="badge bg-success fs-6 px-3 py-2">Disetujui Petugas</span>
                @elseif($reservation->status === \App\Support\Status::RESERVATION_REJECTED)
                    <span class="badge bg-danger fs-6 px-3 py-2">Pengajuan Ditolak</span>
                @elseif($reservation->status === \App\Support\Status::RESERVATION_CANCELLED)
                    <span class="badge bg-secondary fs-6 px-3 py-2">Reservasi Dibatalkan</span>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    @if(session('success'))
        <div class="alert alert-success border-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('cancel'))
        <div class="alert alert-danger border-danger alert-dismissible fade show mb-4" role="alert">
            {{ $errors->first('cancel') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="p-4 bg-white border rounded shadow-sm mb-4">
                <h5 class="font-serif fw-semibold mb-3 border-bottom pb-2">Informasi Peminjaman</h5>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="small text-muted">Fasilitas Kampus</div>
                        <div class="fw-semibold fs-5">{{ $reservation->facility->name }}</div>
                        <div class="small text-secondary">{{ $reservation->facility->location_detail }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted">Jenis Fasilitas</div>
                        <div class="fw-semibold fs-5">{{ ucfirst(str_replace('_', ' ', $reservation->facility->type)) }}</div>
                        @if($reservation->facility->isEquipment())
                            <div class="small text-secondary">Dipinjam: <strong>{{ $reservation->quantity }} unit</strong></div>
                        @else
                            <div class="small text-secondary">Kapasitas: {{ $reservation->facility->capacity }} orang</div>
                        @endif
                    </div>
                </div>

                <div class="row g-3 mb-4 pt-3 border-top">
                    <div class="col-sm-6">
                        <div class="small text-muted">Tanggal Peminjaman</div>
                        <div class="fw-semibold font-mono">{{ $reservation->start_time->translatedFormat('l, d F Y') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted">Jam Penggunaan</div>
                        <div class="fw-semibold font-mono">
                            {{ $reservation->start_time->format('H:i') }} - {{ $reservation->end_time->format('H:i') }} WIB
                            <span class="badge bg-light text-dark border ms-1">{{ $reservation->durationMinutes() / 60 }} Jam</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-top">
                    <div class="small text-muted mb-1">Tujuan / Agenda Kegiatan</div>
                    <div class="p-3 bg-light rounded border text-dark">
                        {{ $reservation->purpose }}
                    </div>
                </div>
            </div>

            @if($reservation->status === \App\Support\Status::RESERVATION_REJECTED && $reservation->rejection_reason)
                <div class="p-4 bg-danger-subtle border border-danger rounded shadow-sm mb-4">
                    <h6 class="text-danger fw-semibold mb-2">Alasan Penolakan Petugas:</h6>
                    <p class="mb-0 text-dark">{{ $reservation->rejection_reason }}</p>
                </div>
            @endif

            @if($reservation->status === \App\Support\Status::RESERVATION_CANCELLED && $reservation->cancel_reason)
                <div class="p-4 bg-secondary-subtle border border-secondary rounded shadow-sm mb-4">
                    <h6 class="text-secondary fw-semibold mb-2">Alasan Pembatalan:</h6>
                    <p class="mb-0 text-dark">{{ $reservation->cancel_reason }}</p>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="p-4 bg-white border rounded shadow-sm mb-4">
                <h5 class="font-serif fw-semibold mb-3">Status & Audit</h5>
                <ul class="list-unstyled small mb-4" style="line-height: 1.8;">
                    <li><strong>Waktu Pengajuan:</strong> {{ $reservation->created_at->format('d/m/Y H:i') }} WIB</li>
                    @if($reservation->processed_at)
                        <li><strong>Diproses Petugas:</strong> {{ $reservation->processed_at->format('d/m/Y H:i') }} WIB</li>
                    @endif
                    @if($reservation->cancelled_at)
                        <li><strong>Waktu Batal:</strong> {{ $reservation->cancelled_at->format('d/m/Y H:i') }} WIB</li>
                    @endif
                </ul>

                @if($reservation->isCancellableByUser())
                    <div class="pt-3 border-top">
                        <h6 class="fw-semibold text-danger mb-2">Pembatalan Mandiri</h6>
                        <p class="small text-muted mb-3">
                            Anda dapat membatalkan reservasi ini karena jadwal mulai masih lebih dari 2 jam ke depan.
                        </p>
                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="cancel_reason" class="form-label small text-muted">Alasan Pembatalan (Opsional):</label>
                                <input type="text" class="form-control form-control-sm" id="cancel_reason" name="cancel_reason" placeholder="Contoh: Jadwal perkuliahan diundur">
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-semibold">
                                Batalkan Reservasi Ini
                            </button>
                        </form>
                    </div>
                @elseif(in_array($reservation->status, [\App\Support\Status::RESERVATION_PENDING, \App\Support\Status::RESERVATION_APPROVED]))
                    <div class="pt-3 border-top">
                        <div class="alert alert-secondary py-2 px-3 small mb-0">
                            <strong>Pembatalan Terkunci:</strong> Waktu pelaksanaan kurang dari 2 jam. Sesuai aturan operasional, pembatalan mandiri ditutup dan harus berkoordinasi langsung dengan petugas.
                        </div>
                    </div>
                @endif
            </div>

            <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary w-100">
                &larr; Kembali ke Daftar Reservasi
            </a>
        </div>
    </div>
</section>
@endsection
