@extends('layouts.app')

@section('title', 'Kelola Fasilitas')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Master Data</p>
            <h1 class="font-serif fw-semibold mb-1">Fasilitas</h1>
            <p class="text-secondary mb-0">Kelola ruang, alat, dan lapangan. Fasilitas lama tidak dihapus, hanya dinonaktifkan.</p>
        </div>
        <a href="{{ route('admin.facilities.create') }}" class="btn btn-primary">Tambah Fasilitas</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.facilities.index') }}" class="row g-2 align-items-end mb-4">
        <div class="col-md-4">
            <label for="filter-type" class="form-label small text-muted mb-1">Tipe</label>
            <select class="form-select" id="filter-type" name="type">
                <option value="">Semua tipe</option>
                @foreach (\App\Support\Status::FACILITY_TYPES as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>
                        {{ ucwords(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="filter-status" class="form-label small text-muted mb-1">Status</label>
            <select class="form-select" id="filter-status" name="status">
                <option value="">Semua status</option>
                <option value="{{ \App\Support\Status::FACILITY_ACTIVE }}" @selected(request('status') === \App\Support\Status::FACILITY_ACTIVE)>Aktif</option>
                <option value="{{ \App\Support\Status::FACILITY_MAINTENANCE }}" @selected(request('status') === \App\Support\Status::FACILITY_MAINTENANCE)>Perbaikan</option>
                <option value="{{ \App\Support\Status::FACILITY_INACTIVE }}" @selected(request('status') === \App\Support\Status::FACILITY_INACTIVE)>Nonaktif</option>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-outline-primary w-100">Terapkan Filter</button>
        </div>
    </form>

    <div class="table-responsive bg-white border">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3">Nama</th>
                    <th>Tipe</th>
                    <th>Fakultas / Gedung</th>
                    <th>Kapasitas / Stok</th>
                    <th>Status</th>
                    <th class="text-end px-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($facilities as $facility)
                    @php
                        $statusLabel = match ($facility->status) {
                            \App\Support\Status::FACILITY_ACTIVE => 'Aktif',
                            \App\Support\Status::FACILITY_MAINTENANCE => 'Perbaikan',
                            default => 'Nonaktif',
                        };
                        $statusBadge = match ($facility->status) {
                            \App\Support\Status::FACILITY_ACTIVE => 'text-bg-success',
                            \App\Support\Status::FACILITY_MAINTENANCE => 'text-bg-warning',
                            default => 'text-bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td class="px-3 fw-medium">{{ $facility->name }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $facility->type)) }}</td>
                        <td class="small">
                            {{ $facility->faculty->code ?? 'Universitas' }}
                            &middot;
                            {{ $facility->building->name ?? 'Di luar gedung' }}
                        </td>
                        <td class="font-mono small">
                            @if ($facility->equipmentDetail)
                                {{ $facility->equipmentDetail->stock_total - $facility->equipmentDetail->stock_unavailable }}
                                / {{ $facility->equipmentDetail->stock_total }} tersedia
                            @else
                                {{ $facility->capacity }} orang
                            @endif
                        </td>
                        <td><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                        <td class="text-end px-3">
                            <a href="{{ route('admin.facilities.edit', $facility) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                            @if ($facility->status === \App\Support\Status::FACILITY_INACTIVE)
                                <button type="button" class="btn btn-outline-success btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#activate-modal-{{ $facility->id }}">
                                    Aktifkan kembali
                                </button>
                            @else
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#deactivate-modal-{{ $facility->id }}">
                                    Nonaktifkan
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Belum ada fasilitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $facilities->links() }}</div>

    @foreach ($facilities as $facility)
        @if ($facility->status === \App\Support\Status::FACILITY_INACTIVE)
            <div class="modal fade" id="activate-modal-{{ $facility->id }}" tabindex="-1" aria-labelledby="activate-title-{{ $facility->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('admin.facilities.activate', $facility) }}" class="modal-content">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="activate-title-{{ $facility->id }}">Aktifkan kembali {{ $facility->name }}?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">Fasilitas akan berstatus <strong>Aktif</strong> dan kembali dapat diajukan serta disetujui reservasinya.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Aktifkan kembali</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            @php $upcoming = $upcomingApproved->get($facility->id, collect()); @endphp
            <div class="modal fade" id="deactivate-modal-{{ $facility->id }}" tabindex="-1" aria-labelledby="deactivate-title-{{ $facility->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <form method="POST" action="{{ route('admin.facilities.deactivate', $facility) }}" class="modal-content">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="deactivate-title-{{ $facility->id }}">Nonaktifkan {{ $facility->name }}?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p>Fasilitas nonaktif tidak dapat menerima pengajuan atau persetujuan reservasi baru. Riwayat tetap tersimpan.</p>

                            @if ($upcoming->isEmpty())
                                <p class="mb-0 text-secondary">Tidak ada reservasi disetujui di masa depan untuk fasilitas ini.</p>
                            @else
                                <div class="alert alert-warning mb-2" role="alert">
                                    <strong>{{ $upcoming->count() }} reservasi disetujui di masa depan</strong>
                                    tidak dibatalkan otomatis. Petugas perlu meninjau dan membatalkannya secara manual.
                                </div>
                                <ul class="list-group list-group-flush small">
                                    @foreach ($upcoming as $reservation)
                                        <li class="list-group-item px-0">
                                            <span class="font-mono">{{ $reservation->start_time->format('d M Y, H:i') }}&ndash;{{ $reservation->end_time->format('H:i') }} WIB</span>
                                            <br>
                                            <span class="text-secondary">{{ $reservation->user->name ?? '-' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Nonaktifkan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
