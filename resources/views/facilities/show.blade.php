@extends('layouts.app')

@section('title', $facility->name . ' - Ketersediaan Jadwal')

@section('content')
<section class="hero-band" style="background: var(--ink); color: #f5f3ed;">
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}" style="color: var(--brass);">Katalog Fasilitas</a></li>
                <li class="breadcrumb-item active text-light" aria-current="page">{{ $facility->name }}</li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    @php
                        $typeLabels = [
                            'ruang_kelas' => 'Ruang Kelas',
                            'laboratorium' => 'Laboratorium',
                            'aula' => 'Aula',
                            'alat' => 'Peralatan',
                            'lapangan' => 'Lapangan Olahraga',
                        ];
                    @endphp
                    <span class="badge" style="background: var(--brass); color: #1b2a36;">{{ $typeLabels[$facility->type] ?? ucfirst($facility->type) }}</span>

                    @if($facility->status === \App\Support\Status::FACILITY_ACTIVE)
                        <span class="badge bg-success">Siap Digunakan</span>
                    @elseif($facility->status === \App\Support\Status::FACILITY_MAINTENANCE)
                        <span class="badge bg-warning text-dark">Dalam Perbaikan</span>
                    @else
                        <span class="badge bg-secondary">Nonaktif</span>
                    @endif
                </div>
                <h1 class="font-serif fw-semibold display-6 mb-1">{{ $facility->name }}</h1>
                <p class="mb-0 text-muted" style="color: #c7cdd2 !important;">
                    {{ $facility->location_detail }}
                    @if($facility->building)
                        &middot; {{ $facility->building->name }}
                    @endif
                    @if($facility->faculty)
                        ({{ $facility->faculty->name }})
                    @endif
                </p>
            </div>

            <div class="d-flex flex-column align-items-md-end gap-2">
                @if($facility->isActive())
                    @auth
                        @if(auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                            <a href="{{ route('reservations.create', ['facility_id' => $facility->id, 'date' => $selectedDate]) }}" class="btn btn-primary px-4 py-2 fw-semibold">
                                Ajukan Reservasi Fasilitas &rarr;
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-light px-3">
                            Masuk untuk Reservasi
                        </a>
                    @endauth
                @else
                    <button class="btn btn-secondary px-4 py-2" disabled>
                        Fasilitas Tidak Tersedia
                    </button>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4 mb-5">
        <div class="col-md-8">
            <div class="p-4 bg-white border rounded">
                <h5 class="font-serif fw-semibold mb-3">Informasi Fasilitas</h5>
                <p class="text-secondary mb-4">{{ $facility->description ?: 'Tidak ada deskripsi tambahan.' }}</p>

                <div class="row g-3">
                    @if($facility->isEquipment())
                        <div class="col-sm-4">
                            <div class="small text-muted">Merk / Model</div>
                            <div class="fw-semibold">{{ $facility->equipmentDetail->brand ?? '-' }} {{ $facility->equipmentDetail->model ?? '' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Total Unit Kampus</div>
                            <div class="fw-semibold font-mono">{{ $facility->equipmentDetail->stock_total ?? 0 }} unit</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Unit Kondisi Rusak</div>
                            <div class="fw-semibold font-mono text-danger">{{ $facility->equipmentDetail->stock_unavailable ?? 0 }} unit</div>
                        </div>
                    @else
                        <div class="col-sm-4">
                            <div class="small text-muted">Kapasitas Maksimal</div>
                            <div class="fw-semibold font-mono">{{ $facility->capacity }} orang</div>
                        </div>
                        @if($facility->roomDetail)
                            <div class="col-sm-4">
                                <div class="small text-muted">Nomor Ruangan</div>
                                <div class="fw-semibold">{{ $facility->roomDetail->room_number }}</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="small text-muted">Posisi Lantai</div>
                                <div class="fw-semibold">Lantai {{ $facility->roomDetail->floor ?? 1 }}</div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="mt-4 pt-3 border-top small text-muted">
                    <strong>Batas Durasi Maksimal:</strong> {{ $maxDurationMinutes / 60 }} jam ({{ $maxDurationMinutes / 30 }} slot berurutan per hari).
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-4 bg-white border rounded h-100 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="font-serif fw-semibold mb-3">Pilih Tanggal Penggunaan</h5>
                    <p class="small text-muted mb-3">Pilih tanggal untuk melihat ketersediaan slot (07.00 - 20.00 WIB).</p>

                    <form method="GET" action="{{ route('facilities.show', $facility) }}">
                        <div class="mb-3">
                            <label for="date" class="form-label small fw-semibold">Tanggal (Maks. 90 Hari ke Depan):</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="{{ $selectedDate }}" 
                                   min="{{ $today }}" 
                                   max="{{ $maxDate }}" 
                                   onchange="this.form.submit()">
                        </div>
                        <noscript>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Cek Jadwal</button>
                        </noscript>
                    </form>
                </div>

                <div class="pt-3 border-top">
                    <div class="d-flex align-items-center gap-2 mb-2 small">
                        <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background: var(--moss);"></span>
                        <span>Hijau: <strong>Tersedia</strong> untuk reservasi</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small text-muted">
                        <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background: #6c757d;"></span>
                        <span>Abu-abu / Merah: <strong>Terpakai</strong> / <strong>Stok Habis</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($facility->status === \App\Support\Status::FACILITY_MAINTENANCE)
        <div class="alert alert-warning border-warning d-flex align-items-center mb-4">
            <div>
                <strong>Pemberitahuan Perbaikan:</strong> Fasilitas ini saat ini berstatus <em>Dalam Perbaikan</em> berdasarkan laporan petugas. Seluruh pengajuan baru dan persetujuan slot otomatis diblokir sampai perbaikan selesai.
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="font-serif fw-semibold mb-0">
                Papan 26 Slot Harian &middot; {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
            </h5>
            <span class="badge bg-light text-dark border">Jam Operasional: 07.00 - 20.00</span>
        </div>

        <div class="card-body p-4">
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
                @foreach($slots as $slot)
                    <div class="col">
                        <div class="p-3 text-center rounded border h-100 d-flex flex-column justify-content-between"
                             style="{{ $slot['is_available'] ? 'background: #f0f7f2; border-color: #b7dfc4 !important;' : 'background: #f8f9fa; border-color: #dee2e6 !important;' }}">
                            <div>
                                <div class="small text-muted font-mono mb-1">#{{ $slot['index'] }}</div>
                                <div class="fw-bold font-mono text-dark">{{ $slot['start_time'] }}</div>
                                <div class="small text-secondary font-mono">{{ $slot['end_time'] }}</div>
                            </div>

                            <div class="mt-2 pt-2 border-top">
                                @if($slot['is_available'])
                                    <span class="badge" style="background: var(--moss); color: #fff;">
                                        Tersedia
                                    </span>
                                    @if($facility->isEquipment())
                                        <div class="small text-muted font-mono mt-1">Sisa: {{ $slot['available_quantity'] }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">
                                        {{ $slot['reason'] }}
                                    </span>
                                    @if($facility->isEquipment())
                                        <div class="small text-danger font-mono mt-1">Habis</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 pt-3 border-top small text-muted text-center">
                <em>*Catatan Privasi: Informasi pemesan dan agenda kegiatan bersifat rahasia dan tidak ditampilkan pada jadwal publik.</em>
            </div>
        </div>
    </div>
</section>
@endsection
