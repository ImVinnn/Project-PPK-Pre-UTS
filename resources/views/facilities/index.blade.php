@extends('layouts.app')

@section('title', 'Katalog Fasilitas Kampus')

@section('content')
<section class="hero-band" style="background: var(--ink); color: #f5f3ed;">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="font-serif fw-semibold display-6 mb-3">Papan Ketersediaan Fasilitas</h1>
                <p class="mb-0" style="color: #c7cdd2; max-width: 52ch;">
                    Cek status, lokasi, dan kapasitas fasilitas kampus sebelum mengajukan
                    reservasi. Masuk sebagai pengguna untuk mengajukan peminjaman.
                </p>
            </div>
        </div>

        <div class="row g-3 mt-4">
            <div class="col-6 col-md-4">
                <div class="p-3" style="border: 1px solid #3a4a58; border-top: 2px solid var(--brass);">
                    <div class="font-mono fs-3 fw-semibold">{{ $stats['total'] ?? $facilities->total() }}</div>
                    <div class="small" style="color: #a9b2ba;">Total fasilitas</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="p-3" style="border: 1px solid #3a4a58; border-top: 2px solid var(--moss);">
                    <div class="font-mono fs-3 fw-semibold">{{ $stats['active'] ?? 0 }}</div>
                    <div class="small" style="color: #a9b2ba;">Aktif & Siap Digunakan</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="p-3" style="border: 1px solid #3a4a58; border-top: 2px solid var(--amber);">
                    <div class="font-mono fs-3 fw-semibold">{{ $stats['maintenance'] ?? 0 }}</div>
                    <div class="small" style="color: #a9b2ba;">Dalam perbaikan</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <form method="GET" action="{{ route('facilities.index') }}" class="row g-2 align-items-end mb-4 pb-4" style="border-bottom: 1px solid var(--line);">
        <div class="col-md-3">
            <label for="tipe" class="form-label small text-muted mb-1">Tipe Fasilitas</label>
            <select class="form-select" id="tipe" name="tipe">
                <option value="">Semua tipe</option>
                <option value="ruang_kelas" @selected(request('tipe') === 'ruang_kelas')>Ruang kelas</option>
                <option value="laboratorium" @selected(request('tipe') === 'laboratorium')>Laboratorium</option>
                <option value="aula" @selected(request('tipe') === 'aula')>Aula</option>
                <option value="alat" @selected(request('tipe') === 'alat')>Alat</option>
                <option value="lapangan" @selected(request('tipe') === 'lapangan')>Lapangan</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="lokasi" class="form-label small text-muted mb-1">Cari Nama / Lokasi / Gedung</label>
            <input type="text" class="form-control" id="lokasi" name="lokasi" value="{{ request('lokasi') }}" placeholder="Contoh: Gedung A / Proyektor">
        </div>
        <div class="col-md-2">
            <label for="kapasitas_min" class="form-label small text-muted mb-1">Kapasitas minimal</label>
            <input type="number" class="form-control" id="kapasitas_min" name="kapasitas_min" min="0" value="{{ request('kapasitas_min') }}" placeholder="0">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Terapkan filter</button>
            @if(request()->hasAny(['tipe', 'lokasi', 'kapasitas_min']))
                <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </div>
    </form>

    <div class="facility-list">
        @forelse($facilities as $facility)
            <div class="row py-3 align-items-center" style="border-bottom: 1px solid var(--line);">
                <div class="col-md-5">
                    <a href="{{ route('facilities.show', $facility) }}" class="fw-semibold text-decoration-none fs-5 d-block text-dark">
                        {{ $facility->name }}
                    </a>
                    <div class="small text-muted mt-1">
                        @php
                            $typeLabels = [
                                'ruang_kelas' => 'Ruang Kelas',
                                'laboratorium' => 'Laboratorium',
                                'aula' => 'Aula',
                                'alat' => 'Peralatan',
                                'lapangan' => 'Lapangan Olahraga',
                            ];
                        @endphp
                        <span class="badge bg-light text-dark border me-1">{{ $typeLabels[$facility->type] ?? ucfirst($facility->type) }}</span>
                        {{ $facility->location_detail }}
                        @if($facility->roomDetail)
                            &middot; No. {{ $facility->roomDetail->room_number }} (Lt. {{ $facility->roomDetail->floor ?? 1 }})
                        @endif
                    </div>
                </div>

                <div class="col-md-3 font-mono my-2 my-md-0">
                    @if($facility->isEquipment())
                        <span class="text-secondary small">Stok Total:</span>
                        <strong>{{ $facility->equipmentDetail->stock_total ?? 0 }} unit</strong>
                        @if(($facility->equipmentDetail->stock_unavailable ?? 0) > 0)
                            <div class="small text-danger font-sans">({{ $facility->equipmentDetail->stock_unavailable }} unit rusak)</div>
                        @endif
                    @else
                        <span class="text-secondary small">Kapasitas:</span>
                        <strong>{{ $facility->capacity }} orang</strong>
                    @endif
                </div>

                <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end gap-3">
                    <div>
                        @if($facility->status === \App\Support\Status::FACILITY_ACTIVE)
                            <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: var(--moss);"></span>
                            <span class="small fw-semibold text-success">Aktif</span>
                        @elseif($facility->status === \App\Support\Status::FACILITY_MAINTENANCE)
                            <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: var(--amber);"></span>
                            <span class="small fw-semibold text-warning">Perbaikan</span>
                        @else
                            <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #6c757d;"></span>
                            <span class="small fw-semibold text-muted">Nonaktif</span>
                        @endif
                    </div>

                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-sm btn-outline-dark">
                        Lihat Jadwal Slot &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-5 text-muted">
                <div class="fs-4 mb-2">Belum ada fasilitas ditemukan</div>
                <p class="small">Coba ubah kata kunci atau bersihkan filter pencarian Anda.</p>
                <a href="{{ route('facilities.index') }}" class="btn btn-sm btn-primary">Tampilkan Semua Fasilitas</a>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $facilities->links() }}
    </div>
</section>

<script src="{{ asset('js/facility-filter.js') }}"></script>
@endsection
