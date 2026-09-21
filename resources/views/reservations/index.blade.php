@extends('layouts.app')

@section('title', 'Riwayat Reservasi Saya')

@section('content')
<section class="hero-band" style="background: var(--ink); color: #f5f3ed;">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}" style="color: var(--brass);">Katalog Fasilitas</a></li>
                        <li class="breadcrumb-item active text-light" aria-current="page">Reservasi Saya</li>
                    </ol>
                </nav>
                <h1 class="font-serif fw-semibold display-6 mb-1">Riwayat Reservasi Saya</h1>
                <p class="mb-0 text-muted" style="color: #c7cdd2 !important;">
                    Pantau status persetujuan petugas, detail jadwal peminjaman, dan pembatalan mandiri.
                </p>
            </div>
            <div>
                <a href="{{ route('reservations.create') }}" class="btn btn-primary px-4 fw-semibold">
                    + Ajukan Reservasi Baru
                </a>
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

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
        <div class="d-flex gap-2">
            <a href="{{ route('reservations.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-outline-secondary' }}">
                Semua
            </a>
            <a href="{{ route('reservations.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning text-dark fw-semibold' : 'btn-outline-secondary' }}">
                Menunggu (Pending)
            </a>
            <a href="{{ route('reservations.index', ['status' => 'approved']) }}" class="btn btn-sm {{ request('status') === 'approved' ? 'btn-success fw-semibold' : 'btn-outline-secondary' }}">
                Disetujui
            </a>
            <a href="{{ route('reservations.index', ['status' => 'rejected']) }}" class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-danger fw-semibold' : 'btn-outline-secondary' }}">
                Ditolak
            </a>
            <a href="{{ route('reservations.index', ['status' => 'cancelled']) }}" class="btn btn-sm {{ request('status') === 'cancelled' ? 'btn-secondary fw-semibold' : 'btn-outline-secondary' }}">
                Dibatalkan
            </a>
        </div>
        <div class="small text-muted">
            Total: <strong>{{ $reservations->total() }}</strong> pengajuan
        </div>
    </div>

    @if($reservations->isEmpty())
        <div class="text-center py-5 bg-white border rounded">
            <div class="fs-4 mb-2 text-muted">Belum ada data reservasi</div>
            <p class="small text-muted mb-3">Anda belum memiliki riwayat reservasi fasilitas kampus pada kategori ini.</p>
            <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm">Mulai Ajukan Reservasi</a>
        </div>
    @else
        <div class="table-responsive bg-white border rounded shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light font-mono small">
                    <tr>
                        <th class="ps-3" style="width: 70px;">ID</th>
                        <th>Fasilitas</th>
                        <th>Jadwal Penggunaan</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $res)
                        <tr>
                            <td class="ps-3 font-mono text-muted">#{{ $res->id }}</td>
                            <td>
                                <a href="{{ route('reservations.show', $res) }}" class="fw-semibold text-decoration-none text-dark">
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
                                <div class="fw-semibold font-mono">
                                    {{ $res->start_time->translatedFormat('d M Y') }}
                                </div>
                                <div class="small text-secondary font-mono">
                                    {{ $res->start_time->format('H:i') }} - {{ $res->end_time->format('H:i') }} WIB
                                </div>
                            </td>
                            <td class="font-mono small">
                                {{ $res->durationMinutes() / 60 }} jam
                                <span class="text-muted">({{ $res->durationMinutes() / 30 }} slot)</span>
                            </td>
                            <td>
                                @if($res->status === \App\Support\Status::RESERVATION_PENDING)
                                    <span class="badge bg-warning text-dark border border-warning">Menunggu Petugas</span>
                                @elseif($res->status === \App\Support\Status::RESERVATION_APPROVED)
                                    <span class="badge bg-success">Disetujui</span>
                                @elseif($res->status === \App\Support\Status::RESERVATION_REJECTED)
                                    <span class="badge bg-danger">Ditolak</span>
                                @elseif($res->status === \App\Support\Status::RESERVATION_CANCELLED)
                                    <span class="badge bg-secondary">Dibatalkan</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-2 align-items-center">
                                    <a href="{{ route('reservations.show', $res) }}" class="btn btn-sm btn-outline-dark">
                                        Detail
                                    </a>

                                    @if($res->isCancellableByUser())
                                        <form method="POST" action="{{ route('reservations.cancel', $res) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Batalkan
                                            </button>
                                        </form>
                                    @elseif(in_array($res->status, [\App\Support\Status::RESERVATION_PENDING, \App\Support\Status::RESERVATION_APPROVED]) && !$res->isCancellableByUser())
                                        <span class="small text-muted font-monospace" title="Batas pembatalan mandiri 2 jam sebelum jadwal mulai telah terlewat">
                                            <small>&lt; 2 Jam (Terkunci)</small>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $reservations->links() }}
        </div>
    @endif
</section>
@endsection
