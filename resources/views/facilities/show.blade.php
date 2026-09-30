@extends('layouts.app')

@section('title', $facility->name . ' - Detail & Ketersediaan Jadwal')

@section('content')
@php
    $typeLabels = [
        'ruang_kelas' => 'Ruang Kelas',
        'laboratorium' => 'Laboratorium',
        'aula' => 'Aula',
        'alat' => 'Peralatan',
        'lapangan' => 'Lapangan Olahraga',
    ];
    $typeLabel = $typeLabels[$facility->type] ?? ucfirst($facility->type);
@endphp

{{-- Breadcrumbs Minimalis Ala Fotel --}}
<nav class="border-bottom small py-3">
    <div class="fotel-container">
        <div class="d-flex align-items-center gap-2 text-muted">
            <a href="{{ route('facilities.index') }}" class="text-muted text-decoration-none">Katalog Fasilitas</a>
            <span>&bull;</span>
            <a href="{{ route('facilities.index', ['tipe' => $facility->type]) }}" class="text-muted text-decoration-none">{{ $typeLabel }}</a>
            <span>&bull;</span>
            <span class="text-dark fw-semibold">{{ $facility->name }}</span>
        </div>
    </div>
</nav>

<section class="fotel-container py-5">
    {{-- Banner Peringatan Jika Fasilitas Dalam Perbaikan --}}
    @if($facility->status === \App\Support\Status::FACILITY_MAINTENANCE)
        <div class="alert alert-warning border mb-4 d-flex align-items-center gap-3" role="alert">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <div>
                <strong>Pemberitahuan Perbaikan:</strong> Fasilitas ini saat ini berstatus <em>Dalam Perbaikan</em>. Seluruh pengajuan baru dan persetujuan slot otomatis diblokir sampai perbaikan selesai.
            </div>
        </div>
    @endif

    {{-- Tata Letak 2 Kolom Fotel (Gambar 1) --}}
    <div class="row g-5 mb-5">
        {{-- Kolom Kiri: Galeri Foto, Stage, & Deskripsi Editorial --}}
        <div class="col-lg-7">
            {{-- Stage Gambar Utama --}}
            <div class="fotel-detail-stage">
                <button type="button" class="fotel-stage-arrow left" aria-label="Foto sebelumnya" onclick="prevFacilityImage()">
                    &lsaquo;
                </button>

                <div class="text-center p-4" id="mainFacilityStage">
                    @if($facility->type === 'ruang_kelas')
                        <svg width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="0.9">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path>
                            <path d="M6 6h10"></path>
                            <path d="M6 10h10"></path>
                        </svg>
                    @elseif($facility->type === 'laboratorium')
                        <svg width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="0.9">
                            <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                            <line x1="8" x2="16" y1="21" y2="21"></line>
                            <line x1="12" x2="12" y1="17" y2="21"></line>
                        </svg>
                    @elseif($facility->type === 'aula')
                        <svg width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="0.9">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    @elseif($facility->type === 'alat')
                        <svg width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="0.9">
                            <rect width="18" height="12" x="3" y="6" rx="2"></rect>
                            <circle cx="9" cy="12" r="2"></circle>
                            <path d="M15 12h2"></path>
                        </svg>
                    @else
                        <svg width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="#141820" stroke-width="0.9">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                            <path d="M2 12h20"></path>
                        </svg>
                    @endif
                </div>

                <button type="button" class="fotel-stage-arrow right" aria-label="Foto berikutnya" onclick="nextFacilityImage()">
                    &rsaquo;
                </button>
            </div>

            {{-- Thumbnail Strip 5 Kotak --}}
            <div class="fotel-thumbnail-strip">
                <div class="fotel-thumb active">
                    <span class="small font-mono fw-bold">01</span>
                </div>
                <div class="fotel-thumb">
                    <span class="small font-mono text-muted">02</span>
                </div>
                <div class="fotel-thumb">
                    <span class="small font-mono text-muted">03</span>
                </div>
                <div class="fotel-thumb">
                    <span class="small font-mono text-muted">04</span>
                </div>
                <div class="fotel-thumb">
                    <span class="small font-mono text-muted">05</span>
                </div>
            </div>

            {{-- Deskripsi Editorial Paragraf --}}
            <div class="pt-2">
                <h5 class="fw-bold mb-3 fs-6 text-uppercase tracking-wider">Deskripsi & Kelengkapan Ruang</h5>
                <p class="text-secondary leading-relaxed mb-0" style="line-height: 1.8;">
                    {{ $facility->description ?: 'Fasilitas ini dikelola oleh pihak universitas untuk mendukung kegiatan perkuliahan, riset, seminar, serta workshop mahasiswa dan dosen. Dilengkapi pencahayaan alami, sirkulasi udara optimal, serta infrastruktur kelistrikan dan jaringan kampus yang stabil.' }}
                </p>
            </div>
        </div>

        {{-- Kolom Kanan: Detail Spesifikasi, Kontrol Pemesanan, & Info Card --}}
        <div class="col-lg-5">
            {{-- Eyebrow & Judul --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-uppercase text-muted font-mono" style="letter-spacing: 0.1em;">
                    {{ $typeLabel }}
                </span>
                <span class="small font-mono text-muted">
                    ID: #{{ str_pad($facility->id, 4, '0', STR_PAD_LEFT) }}
                </span>
            </div>

            <h1 class="fw-bold fs-2 text-dark mb-2 tracking-tight">
                {{ $facility->name }}
            </h1>

            <p class="text-muted small mb-4">
                {{ $facility->location_detail }}
                @if($facility->building)
                    &middot; {{ $facility->building->name }}
                @endif
                @if($facility->faculty)
                    ({{ $facility->faculty->name }})
                @endif
            </p>

            {{-- Status & Batas Durasi Strip --}}
            <div class="p-3 mb-4 rounded bg-light border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        @if($facility->status === \App\Support\Status::FACILITY_ACTIVE)
                            <span class="fotel-badge fotel-badge-green">Siap Digunakan</span>
                        @elseif($facility->status === \App\Support\Status::FACILITY_MAINTENANCE)
                            <span class="fotel-badge fotel-badge-amber">Dalam Perbaikan</span>
                        @else
                            <span class="fotel-badge fotel-badge-slate">Nonaktif</span>
                        @endif
                    </div>
                    <div class="small font-mono text-muted">
                        Jam: 07.00 - 20.00 WIB
                    </div>
                </div>
                <div class="small text-secondary">
                    Batas durasi maksimal: <strong class="text-dark">{{ $maxDurationMinutes / 60 }} jam</strong> ({{ $maxDurationMinutes / 30 }} slot berurutan per hari).
                </div>
            </div>

            {{-- Form Pemilih Tanggal --}}
            <form method="GET" action="{{ route('facilities.show', $facility) }}" class="mb-4">
                <label for="date" class="form-label small fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    Pilih Tanggal Penggunaan (Maks. 90 Hari):
                </label>
                <div class="d-flex gap-2">
                    <input type="date" class="form-control font-mono" id="date" name="date" 
                           value="{{ $selectedDate }}" 
                           min="{{ $today }}" 
                           max="{{ $maxDate }}" 
                           onchange="this.form.submit()">
                    <button type="submit" class="btn-fotel-secondary px-3">
                        Cek
                    </button>
                </div>
            </form>

            {{-- Stepper Unit & Tombol Aksi --}}
            <div class="d-flex flex-column gap-3 mb-4">
                @if($facility->isEquipment())
                    <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                        <div>
                            <div class="small fw-bold text-uppercase" style="font-size: 0.72rem;">Jumlah Unit Diajukan</div>
                            <div class="small text-muted font-mono">Stok: {{ $facility->equipmentDetail->stock_total ?? 0 }} unit</div>
                        </div>
                        <div class="fotel-stepper">
                            <button type="button" class="fotel-stepper-btn" onclick="decreaseQty()">-</button>
                            <input type="text" class="fotel-stepper-input font-mono" id="displayQty" value="1" readonly>
                            <button type="button" class="fotel-stepper-btn" onclick="increaseQty()">+</button>
                        </div>
                    </div>
                @endif

                @if($facility->isActive())
                    @auth
                        @if(auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                            <a href="{{ route('reservations.create', ['facility_id' => $facility->id, 'date' => $selectedDate]) }}" class="btn-fotel-gold py-3 text-center fs-6 text-decoration-none">
                                Ajukan Reservasi Fasilitas &rarr;
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-fotel-gold py-3 text-center fs-6 text-decoration-none">
                            Masuk untuk Reservasi &rarr;
                        </a>
                    @endauth
                @else
                    <button class="btn-fotel-secondary py-3 text-center text-muted" disabled>
                        Fasilitas Tidak Tersedia untuk Dipinjam
                    </button>
                @endif

                <a href="{{ route('facilities.index') }}" class="btn-fotel-outline text-center text-decoration-none">
                    Cek Fasilitas Lain
                </a>
            </div>

            {{-- Accordion Disclosures Ala Fotel --}}
            <div class="border-top mb-4">
                <div class="fotel-accordion-item">
                    <button class="fotel-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#accDetail" aria-expanded="true">
                        <span>Detail Fasilitas</span>
                        <span>&darr;</span>
                    </button>
                    <div class="collapse show fotel-accordion-content" id="accDetail">
                        <div class="row g-2">
                            @if($facility->isEquipment())
                                <div class="col-6">
                                    <div class="small text-muted">Merk / Brand</div>
                                    <div class="fw-bold">{{ $facility->equipmentDetail->brand ?? '-' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Model / Seri</div>
                                    <div class="fw-bold">{{ $facility->equipmentDetail->model ?? '-' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Unit Kondisi Rusak</div>
                                    <div class="fw-bold text-danger font-mono">{{ $facility->equipmentDetail->stock_unavailable ?? 0 }} unit</div>
                                </div>
                            @else
                                <div class="col-6">
                                    <div class="small text-muted">Kapasitas Maksimal</div>
                                    <div class="fw-bold font-mono">{{ $facility->capacity }} orang</div>
                                </div>
                                @if($facility->roomDetail)
                                    <div class="col-6">
                                        <div class="small text-muted">Nomor Ruangan</div>
                                        <div class="fw-bold">No. {{ $facility->roomDetail->room_number }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Posisi Lantai</div>
                                        <div class="fw-bold">Lantai {{ $facility->roomDetail->floor ?? 1 }}</div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <div class="fotel-accordion-item">
                    <button class="fotel-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#accRules" aria-expanded="false">
                        <span>Ketentuan Peminjaman</span>
                        <span>&darr;</span>
                    </button>
                    <div class="collapse fotel-accordion-content" id="accRules">
                        <ul class="ps-3 mb-0" style="line-height: 1.7;">
                            <li>Waktu mulai dan selesai wajib berada dalam rentang operasional 07.00 - 20.00 WIB.</li>
                            <li>Pemilihan waktu harus presisi kelipatan slot 30 menit (:00 atau :30).</li>
                            <li>Batas pembatalan mandiri oleh pengguna paling lambat <strong>2 jam</strong> sebelum waktu mulai.</li>
                            <li>Persetujuan reservasi diproses oleh petugas fasilitas secara berurutan.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Care Instructions Box (Fotel Info Card Biru Muda) --}}
            <div class="fotel-info-card">
                <div class="fotel-info-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>Petunjuk Penggunaan & Kebersihan Ruang</span>
                </div>
                <ul class="fotel-info-list">
                    <li><strong>Kebersihan:</strong> Kembalikan kondisi ruangan tetap bersih dan rapi setelah kegiatan selesai.</li>
                    <li><strong>Listrik & AC:</strong> Matikan pendingin ruangan, proyektor, dan saklar lampu sebelum mengunci ruangan.</li>
                    <li><strong>Kunci & Akses:</strong> Pengambilan dan penyerahan kunci dilakukan di pos petugas operasional lantai 1.</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Papan 26 Slot Harian (Area Bawah - Pengganti Carousel Rekomendasi) --}}
    <div class="pt-5 border-top" id="papan-slot">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <div class="small text-uppercase font-mono text-muted">Jadwal Harian</div>
                <h3 class="fs-4 fw-bold mb-0">
                    Papan 26 Slot Harian &middot; {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
                </h3>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-1 small">
                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #2E7D32;"></span>
                    <span class="text-secondary">Tersedia</span>
                </div>
                <div class="d-flex align-items-center gap-1 small text-muted">
                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #78909C;"></span>
                    <span>Terpakai / Habis</span>
                </div>
            </div>
        </div>

        {{-- 26 Slot Grid --}}
        <div class="fotel-slot-grid">
            @foreach($slots as $slot)
                <div class="fotel-slot-card fotel-reveal fotel-stagger-{{ ($slot['index'] % 6) + 1 }} {{ $slot['is_available'] ? 'available' : 'occupied' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fotel-slot-num font-mono">#{{ $slot['index'] }}</span>
                        @if($slot['is_available'])
                            <span class="fotel-badge fotel-badge-green">Tersedia</span>
                        @else
                            <span class="fotel-badge fotel-badge-slate">{{ $slot['reason'] }}</span>
                        @endif
                    </div>

                    <div class="my-2">
                        <div class="fotel-slot-time font-mono">
                            {{ $slot['start_time'] }}
                        </div>
                        <div class="small text-muted font-mono" style="font-size: 0.75rem;">
                            sampai {{ $slot['end_time'] }}
                        </div>
                    </div>

                    <div class="pt-2 border-top">
                        @if($facility->isEquipment())
                            @if($slot['is_available'])
                                <span class="small font-mono text-success fw-bold">Sisa: {{ $slot['available_quantity'] }}</span>
                            @else
                                <span class="small font-mono text-danger">Habis</span>
                            @endif
                        @else
                            @if($slot['is_available'])
                                @auth
                                    @if(auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                                        <a href="{{ route('reservations.create', ['facility_id' => $facility->id, 'date' => $selectedDate, 'start_time' => $slot['start_time']]) }}" class="small text-decoration-none fw-bold" style="color: #2E7D32;">
                                            Pilih Slot &rarr;
                                        </a>
                                    @else
                                        <span class="small text-muted">Bisa Dipesan</span>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="small text-decoration-none text-muted">
                                        Pesan &rarr;
                                    </a>
                                @endauth
                            @else
                                <span class="small text-muted font-mono">Tutup</span>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 p-3 bg-light rounded text-center small text-muted">
            <em>*Catatan Privasi: Informasi pemesan dan agenda kegiatan bersifat rahasia dan tidak ditampilkan pada jadwal publik.</em>
        </div>
    </div>
</section>

<script>
    function decreaseQty() {
        const input = document.getElementById('displayQty');
        let val = parseInt(input.value, 10);
        if (val > 1) {
            input.value = val - 1;
        }
    }
    function increaseQty() {
        const input = document.getElementById('displayQty');
        let val = parseInt(input.value, 10);
        input.value = val + 1;
    }
    function prevFacilityImage() {
        // Visual indicator transition
        const stage = document.getElementById('mainFacilityStage');
        stage.style.opacity = '0.5';
        setTimeout(() => { stage.style.opacity = '1'; }, 150);
    }
    function nextFacilityImage() {
        const stage = document.getElementById('mainFacilityStage');
        stage.style.opacity = '0.5';
        setTimeout(() => { stage.style.opacity = '1'; }, 150);
    }
</script>
@endsection
