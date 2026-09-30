@extends('layouts.app')

@section('title', 'Riwayat Reservasi Saya - Fasilitas Kampus')

@section('content')
{{-- Breadcrumbs Minimalis Ala Fotel --}}
<nav class="border-bottom small py-3">
    <div class="fotel-container">
        <div class="d-flex align-items-center gap-2 text-muted">
            <a href="{{ route('facilities.index') }}" class="text-muted text-decoration-none">Katalog Fasilitas</a>
            <span>&bull;</span>
            <span class="text-dark fw-semibold">Reservasi Saya</span>
        </div>
    </div>
</nav>

<section class="fotel-container py-5">
    {{-- Header Judul & Tombol Tambah --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="small text-uppercase font-mono text-muted">Manajemen Akun</div>
            <h1 class="fs-2 fw-bold text-dark mb-1 tracking-tight">Riwayat Reservasi Saya</h1>
            <p class="text-secondary small mb-0">
                Pantau proses verifikasi petugas, detail jadwal penggunaan, dan pembatalan mandiri sebelum batas 2 jam.
            </p>
        </div>
        <div>
            <a href="{{ route('reservations.create') }}" class="btn-fotel-gold">
                + Ajukan Reservasi Baru
            </a>
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

    {{-- Filter Status Tabs (Fotel Strip Navigation) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('reservations.index') }}" 
               class="btn btn-sm {{ !request('status') ? 'btn-fotel-dark' : 'btn-fotel-secondary' }}">
                Semua
            </a>
            <a href="{{ route('reservations.index', ['status' => 'pending']) }}" 
               class="btn btn-sm {{ request('status') === 'pending' ? 'btn-fotel-dark' : 'btn-fotel-secondary' }}">
                Menunggu (Pending)
            </a>
            <a href="{{ route('reservations.index', ['status' => 'approved']) }}" 
               class="btn btn-sm {{ request('status') === 'approved' ? 'btn-fotel-dark' : 'btn-fotel-secondary' }}">
                Disetujui
            </a>
            <a href="{{ route('reservations.index', ['status' => 'rejected']) }}" 
               class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-fotel-dark' : 'btn-fotel-secondary' }}">
                Ditolak
            </a>
            <a href="{{ route('reservations.index', ['status' => 'cancelled']) }}" 
               class="btn btn-sm {{ request('status') === 'cancelled' ? 'btn-fotel-dark' : 'btn-fotel-secondary' }}">
                Dibatalkan
            </a>
        </div>
        <div class="small font-mono text-muted">
            Total: <strong>{{ $reservations->total() }}</strong> Pengajuan
        </div>
    </div>

    {{-- Table Listing --}}
    @if($reservations->isEmpty())
        <div class="text-center py-5 bg-white border rounded">
            <div class="fs-4 fw-bold mb-2">Belum Ada Riwayat Reservasi</div>
            <p class="small text-muted mb-4">Anda belum memiliki pengajuan peminjaman fasilitas pada status ini.</p>
            <a href="{{ route('reservations.create') }}" class="btn-fotel-gold">
                Ajukan Reservasi Pertama
            </a>
        </div>
    @else
        <div class="bg-white border rounded overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light font-mono" style="font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase;">
                        <tr>
                            <th class="ps-4 py-3" style="width: 80px;">ID</th>
                            <th class="py-3">Fasilitas</th>
                            <th class="py-3">Jadwal Penggunaan</th>
                            <th class="py-3">Durasi</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.88rem;">
                        @foreach($reservations as $res)
                            <tr>
                                <td class="ps-4 font-mono text-muted">
                                    #{{ $res->id }}
                                </td>
                                <td>
                                    <a href="{{ route('reservations.show', $res) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $res->facility->name }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ $res->facility->location_detail }}
                                        @if($res->facility->isEquipment())
                                            &middot; <strong>{{ $res->quantity }} unit</strong>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold font-mono">
                                        {{ $res->start_time->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="small text-muted font-mono">
                                        {{ $res->start_time->format('H:i') }} - {{ $res->end_time->format('H:i') }} WIB
                                    </div>
                                </td>
                                <td class="font-mono small">
                                    {{ $res->durationMinutes() / 60 }} jam
                                    <span class="text-muted">({{ $res->durationMinutes() / 30 }} slot)</span>
                                </td>
                                <td>
                                    @if($res->status === \App\Support\Status::RESERVATION_PENDING)
                                        <span class="fotel-badge fotel-badge-amber">Menunggu Petugas</span>
                                    @elseif($res->status === \App\Support\Status::RESERVATION_APPROVED)
                                        <span class="fotel-badge fotel-badge-green">Disetujui</span>
                                    @elseif($res->status === \App\Support\Status::RESERVATION_REJECTED)
                                        <span class="fotel-badge fotel-badge-rose">Ditolak</span>
                                    @elseif($res->status === \App\Support\Status::RESERVATION_CANCELLED)
                                        <span class="fotel-badge fotel-badge-slate">Dibatalkan</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        <a href="{{ route('reservations.show', $res) }}" class="btn-fotel-secondary py-1 px-3" style="font-size: 0.78rem;">
                                            Detail
                                        </a>

                                        @if($res->isCancellableByUser())
                                            <form method="POST" action="{{ route('reservations.cancel', $res) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 0.75rem;">
                                                    Batalkan
                                                </button>
                                            </form>
                                        @elseif(in_array($res->status, [\App\Support\Status::RESERVATION_PENDING, \App\Support\Status::RESERVATION_APPROVED]) && !$res->isCancellableByUser())
                                            <span class="small text-muted font-mono" style="font-size: 0.72rem;" title="Batas pembatalan mandiri 2 jam sebelum mulai telah terlewat">
                                                &lt; 2 Jam (Terkunci)
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $reservations->links() }}
        </div>
    @endif
</section>
@endsection
