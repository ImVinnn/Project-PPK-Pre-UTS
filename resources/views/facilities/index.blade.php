@extends('layouts.app')

@section('title', 'Katalog Fasilitas Kampus - Peminjaman & Ketersediaan')

@section('content')
{{-- 1. Hero Banner Bergaya Fotel (Gambar 3) --}}
<section class="fotel-hero" style="background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80');">
    <div class="fotel-hero-overlay"></div>
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="fotel-hero-content">
                    <div class="fotel-badge fotel-badge-gold mb-3">Portal Peminjaman Terpadu</div>
                    <h1 class="fotel-hero-title">Ruang & Fasilitas untuk Produktivitas Sivitas Kampus</h1>
                    <p class="fotel-hero-text">
                        Cek ketersediaan 26 slot waktu harian secara langsung, pantau spesifikasi kapasitas, dan ajukan peminjaman ruang kelas, lab, aula, serta peralatan dengan verifikasi transparan.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#daftar-fasilitas" class="btn-fotel-outline text-white border-white">
                            Lihat Semua Fasilitas &darr;
                        </a>
                        @auth
                            @if(auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                                <a href="{{ route('reservations.create') }}" class="btn-fotel-gold">
                                    + Ajukan Reservasi
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-fotel-gold">
                                Masuk untuk Reservasi
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

            {{-- Stat Box Kanan --}}
            <div class="col-lg-5 d-none d-lg-block">
                <div class="fotel-hero-stat-box">
                    <div class="stat-title font-mono">
                        Ringkasan Inventaris Kampus
                    </div>
                    <div class="row g-0 text-center align-items-center">
                        <div class="col-4 stat-divider px-2">
                            <div class="stat-number font-mono text-white" data-counter="{{ $stats['total'] ?? $facilities->total() }}">
                                {{ $stats['total'] ?? $facilities->total() }}
                            </div>
                            <div class="stat-label">Total Fasilitas</div>
                        </div>
                        <div class="col-4 stat-divider px-2">
                            <div class="stat-number font-mono" style="color: #4ADE80;" data-counter="{{ $stats['active'] ?? 0 }}">
                                {{ $stats['active'] ?? 0 }}
                            </div>
                            <div class="stat-label">Siap Digunakan</div>
                        </div>
                        <div class="col-4 px-2">
                            <div class="stat-number font-mono" style="color: #FBBF24;" data-counter="{{ $stats['maintenance'] ?? 0 }}">
                                {{ $stats['maintenance'] ?? 0 }}
                            </div>
                            <div class="stat-label">Dalam Perbaikan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 2. Bento Category Grid (5 Tipe Fasilitas - Gambar 3) --}}
<section class="border-bottom">
    <div class="fotel-bento-grid">
        {{-- Ruang Kelas --}}
        <a href="{{ route('facilities.index', ['tipe' => 'ruang_kelas']) }}" class="fotel-bento-cell fotel-reveal fotel-stagger-1 {{ request('tipe') === 'ruang_kelas' ? 'bg-light' : '' }}">
            <div class="fotel-bento-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path>
                    <path d="M6 6h10"></path>
                    <path d="M6 10h10"></path>
                </svg>
            </div>
            <div>
                <div class="fotel-bento-label">Ruang Kelas</div>
                <div class="fotel-bento-sub">Teori & Diskusi</div>
            </div>
        </a>

        {{-- Laboratorium --}}
        <a href="{{ route('facilities.index', ['tipe' => 'laboratorium']) }}" class="fotel-bento-cell fotel-reveal fotel-stagger-2 {{ request('tipe') === 'laboratorium' ? 'bg-light' : '' }}">
            <div class="fotel-bento-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                    <line x1="8" x2="16" y1="21" y2="21"></line>
                    <line x1="12" x2="12" y1="17" y2="21"></line>
                </svg>
            </div>
            <div>
                <div class="fotel-bento-label">Laboratorium</div>
                <div class="fotel-bento-sub">Riset & Komputasi</div>
            </div>
        </a>

        {{-- Aula & Auditorium --}}
        <a href="{{ route('facilities.index', ['tipe' => 'aula']) }}" class="fotel-bento-cell fotel-reveal fotel-stagger-3 {{ request('tipe') === 'aula' ? 'bg-light' : '' }}">
            <div class="fotel-bento-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div>
                <div class="fotel-bento-label">Aula</div>
                <div class="fotel-bento-sub">Seminar & Wisuda</div>
            </div>
        </a>

        {{-- Peralatan Kampus --}}
        <a href="{{ route('facilities.index', ['tipe' => 'alat']) }}" class="fotel-bento-cell fotel-reveal fotel-stagger-4 {{ request('tipe') === 'alat' ? 'bg-light' : '' }}">
            <div class="fotel-bento-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="12" x="3" y="6" rx="2"></rect>
                    <circle cx="9" cy="12" r="2"></circle>
                    <path d="M15 12h2"></path>
                </svg>
            </div>
            <div>
                <div class="fotel-bento-label">Peralatan</div>
                <div class="fotel-bento-sub">Audio & Multimedia</div>
            </div>
        </a>

        {{-- Lapangan Olahraga --}}
        <a href="{{ route('facilities.index', ['tipe' => 'lapangan']) }}" class="fotel-bento-cell fotel-reveal fotel-stagger-5 {{ request('tipe') === 'lapangan' ? 'bg-light' : '' }}">
            <div class="fotel-bento-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                    <path d="M2 12h20"></path>
                </svg>
            </div>
            <div>
                <div class="fotel-bento-label">Lapangan</div>
                <div class="fotel-bento-sub">Olahraga & Outdoor</div>
            </div>
        </a>
    </div>
</section>

{{-- 3. Dark Horizontal Quick-Filter Strip (Gambar 2) --}}
<div class="fotel-dark-strip">
    <div class="container-fluid px-4">
        <ul class="fotel-strip-list">
            <li class="fotel-strip-item">
                <a href="{{ route('facilities.index') }}" class="{{ !request('tipe') ? 'active-strip' : '' }}">
                    <span>Semua Kategori</span>
                </a>
            </li>
            <li class="fotel-strip-item">
                <a href="{{ route('facilities.index', ['tipe' => 'ruang_kelas']) }}" class="{{ request('tipe') === 'ruang_kelas' ? 'active-strip' : '' }}">
                    <span>Ruang Kelas</span>
                </a>
            </li>
            <li class="fotel-strip-item">
                <a href="{{ route('facilities.index', ['tipe' => 'laboratorium']) }}" class="{{ request('tipe') === 'laboratorium' ? 'active-strip' : '' }}">
                    <span>Laboratorium</span>
                </a>
            </li>
            <li class="fotel-strip-item">
                <a href="{{ route('facilities.index', ['tipe' => 'aula']) }}" class="{{ request('tipe') === 'aula' ? 'active-strip' : '' }}">
                    <span>Aula</span>
                </a>
            </li>
            <li class="fotel-strip-item">
                <a href="{{ route('facilities.index', ['tipe' => 'alat']) }}" class="{{ request('tipe') === 'alat' ? 'active-strip' : '' }}">
                    <span>Peralatan</span>
                </a>
            </li>
            <li class="fotel-strip-item">
                <a href="{{ route('facilities.index', ['tipe' => 'lapangan']) }}" class="{{ request('tipe') === 'lapangan' ? 'active-strip' : '' }}">
                    <span>Lapangan</span>
                </a>
            </li>
        </ul>
    </div>
</div>

{{-- 4. Search Form & Catalog Listing Grid (Fotel Editorial Inset Container) --}}
<section class="fotel-container py-5" id="daftar-fasilitas">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="small text-uppercase font-mono text-muted">Inventaris Kampus</div>
            <h2 class="fs-3 fw-bold text-dark mb-0">Katalog Fasilitas</h2>
        </div>
        <div class="text-muted small">
            Menampilkan <strong>{{ $facilities->total() }}</strong> fasilitas tersedia
        </div>
    </div>

    {{-- Filter Search Form --}}
    <form method="GET" action="{{ route('facilities.index') }}" id="catalog-filter-form" class="row g-2 align-items-end mb-5 p-3 rounded bg-light border">
        <div class="col-md-3">
            <label for="tipe" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Tipe Fasilitas</label>
            <select class="form-select form-select-sm" id="tipe" name="tipe">
                <option value="">Semua tipe</option>
                <option value="ruang_kelas" @selected(request('tipe') === 'ruang_kelas')>Ruang kelas</option>
                <option value="laboratorium" @selected(request('tipe') === 'laboratorium')>Laboratorium</option>
                <option value="aula" @selected(request('tipe') === 'aula')>Aula</option>
                <option value="alat" @selected(request('tipe') === 'alat')>Alat</option>
                <option value="lapangan" @selected(request('tipe') === 'lapangan')>Lapangan</option>
            </select>
        </div>

        <div class="col-md-4">
            <label for="lokasi" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Cari Nama / Lokasi / Gedung</label>
            <input type="text" class="form-control form-control-sm" id="lokasi" name="lokasi" value="{{ request('lokasi') }}" placeholder="Contoh: Gedung A / Multimedia / Proyektor">
        </div>

        <div class="col-md-2">
            <label for="kapasitas_min" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Kapasitas Minimal</label>
            <input type="number" class="form-control form-control-sm font-mono" id="kapasitas_min" name="kapasitas_min" min="0" value="{{ request('kapasitas_min') }}" placeholder="0 orang">
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn-fotel-gold w-100 py-1">
                Terapkan Filter
            </button>
            @if(request()->hasAny(['tipe', 'lokasi', 'kapasitas_min']))
                <a href="{{ route('facilities.index') }}" class="btn-fotel-secondary py-1 px-3">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- Skeleton Placeholder State (The Waiting State) --}}
    <div id="catalog-skeleton" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4" style="display: none;" aria-hidden="true">
        @for($i = 0; $i < 8; $i++)
            <div class="col">
                <div class="fotel-skeleton-card">
                    <div class="fotel-skeleton fotel-skeleton-img"></div>
                    <div class="fotel-skeleton fotel-skeleton-line short"></div>
                    <div class="fotel-skeleton fotel-skeleton-line medium mb-3"></div>
                    <div class="fotel-skeleton fotel-skeleton-line long"></div>
                    <div class="fotel-skeleton fotel-skeleton-line short mt-auto"></div>
                </div>
            </div>
        @endfor
    </div>

    {{-- Product Grid 4 Kolom (Fotel Product Cards) --}}
    <div id="catalog-products" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
        @forelse($facilities as $facility)
            <div class="col fotel-reveal fotel-stagger-{{ ($loop->index % 4) + 1 }}">
                <div class="fotel-product-card">
                    {{-- Visual Image Box --}}
                    <div class="fotel-card-image-box">
                        @php
                            $typeLabels = [
                                'ruang_kelas' => 'Ruang Kelas',
                                'laboratorium' => 'Laboratorium',
                                'aula' => 'Aula',
                                'alat' => 'Peralatan',
                                'lapangan' => 'Lapangan',
                            ];
                        @endphp

                        @if($facility->type === 'ruang_kelas')
                            <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.2">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path>
                                <path d="M6 6h10"></path>
                                <path d="M6 10h10"></path>
                            </svg>
                        @elseif($facility->type === 'laboratorium')
                            <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.2">
                                <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                                <line x1="8" x2="16" y1="21" y2="21"></line>
                                <line x1="12" x2="12" y1="17" y2="21"></line>
                            </svg>
                        @elseif($facility->type === 'aula')
                            <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.2">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        @elseif($facility->type === 'alat')
                            <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.2">
                                <rect width="18" height="12" x="3" y="6" rx="2"></rect>
                                <circle cx="9" cy="12" r="2"></circle>
                                <path d="M15 12h2"></path>
                            </svg>
                        @else
                            <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="1.2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                                <path d="M2 12h20"></path>
                            </svg>
                        @endif

                        {{-- Floating Status Pill --}}
                        <div class="position-absolute top-0 end-0 m-2">
                            @if($facility->status === \App\Support\Status::FACILITY_ACTIVE)
                                <span class="fotel-badge fotel-badge-green">Siap Pakai</span>
                            @elseif($facility->status === \App\Support\Status::FACILITY_MAINTENANCE)
                                <span class="fotel-badge fotel-badge-amber">Perbaikan</span>
                            @else
                                <span class="fotel-badge fotel-badge-slate">Nonaktif</span>
                            @endif
                        </div>
                    </div>

                    {{-- Metadata & Judul --}}
                    <div class="fotel-card-eyebrow">{{ $typeLabels[$facility->type] ?? ucfirst($facility->type) }}</div>
                    <a href="{{ route('facilities.show', $facility) }}" class="fotel-card-title text-decoration-none">
                        {{ $facility->name }}
                    </a>

                    <div class="fotel-card-meta">
                        <div>{{ $facility->location_detail }}</div>
                        @if($facility->roomDetail)
                            <div class="small text-muted">Ruang {{ $facility->roomDetail->room_number }} (Lt. {{ $facility->roomDetail->floor ?? 1 }})</div>
                        @endif
                    </div>

                    {{-- Kapasitas & Jam Operasional --}}
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center mb-3">
                        <div class="font-mono small">
                            @if($facility->isEquipment())
                                <span class="text-muted">Stok Total:</span>
                                <strong>{{ $facility->equipmentDetail->stock_total ?? 0 }} unit</strong>
                            @else
                                <span class="text-muted">Kapasitas:</span>
                                <strong>{{ $facility->capacity }} orang</strong>
                            @endif
                        </div>
                        <span class="small font-mono text-muted">26 Slot</span>
                    </div>

                    {{-- Tombol Emas Fotel --}}
                    <a href="{{ route('facilities.show', $facility) }}" class="btn-fotel-gold text-center text-decoration-none w-100">
                        Lihat Jadwal Slot &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12 py-5 text-center">
                <div class="p-5 border rounded bg-white" style="max-width: 540px; margin: 0 auto;">
                    <div class="fs-4 fw-bold mb-2">Fasilitas Tidak Ditemukan</div>
                    <p class="text-muted small mb-4">Coba sesuaikan kata kunci pencarian, ubah filter tipe, atau kosongkan kapasitas minimal.</p>
                    <a href="{{ route('facilities.index') }}" class="btn-fotel-gold">
                        Tampilkan Semua Fasilitas
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-5 d-flex justify-content-center">
        {{ $facilities->links() }}
    </div>
</section>

<script src="{{ asset('js/facility-filter.js') }}"></script>
@endsection
