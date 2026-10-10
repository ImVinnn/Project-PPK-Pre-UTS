@extends('layouts.app')

@section('title', 'Ajukan Reservasi Fasilitas Kampus')

@section('content')
{{-- Breadcrumbs Minimalis Ala Fotel --}}
<nav class="border-bottom small py-3">
    <div class="fotel-container">
        <div class="d-flex align-items-center gap-2 text-muted">
            <a href="{{ route('facilities.index') }}" class="text-muted text-decoration-none">Katalog Fasilitas</a>
            <span>&bull;</span>
            <span class="text-dark fw-semibold">Pengajuan Reservasi</span>
        </div>
    </div>
</nav>

<section class="fotel-container py-5">
    {{-- Header Judul Editorial --}}
    <div class="mb-4 pb-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
        <div>
            <div class="small text-uppercase font-mono text-muted">Layanan Sivitas</div>
            <h1 class="fs-2 fw-bold text-dark mb-1 tracking-tight">Ajukan Reservasi Fasilitas</h1>
            <p class="text-secondary small mb-0">
                Pilih tanggal, tentukan rentang waktu kelipatan 30 menit (07.00 - 20.00 WIB), dan isi agenda kegiatan Anda.
            </p>
        </div>
        <div class="small font-mono text-muted">
            Status Pengajuan Awal: <span class="fotel-badge fotel-badge-amber">Menunggu Verifikasi</span>
        </div>
    </div>

    {{-- Error Alert --}}
    @if ($errors->any())
        <div class="alert alert-danger border mb-4 rounded" role="alert">
            <div class="fw-bold mb-1">Pengajuan reservasi tidak dapat diproses:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2-Column Architecture Ala Fotel --}}
    <div class="row g-5">
        {{-- Kolom Kiri: Ringkasan Fasilitas & Info Card Aturan --}}
        <div class="col-lg-5 order-lg-2">
            @if($selectedFacility)
                @php
                    $maxMinutes = \App\Support\Status::MAX_DURATION_MINUTES[$selectedFacility->type] ?? 180;
                @endphp
                <div class="p-4 bg-white border rounded mb-4">
                    <div class="small font-mono text-muted text-uppercase mb-1">Fasilitas Terpilih</div>
                    <h3 class="fs-4 fw-bold text-dark mb-1">{{ $selectedFacility->name }}</h3>
                    <div class="small text-secondary mb-3">
                        {{ $selectedFacility->location_detail }}
                        @if($selectedFacility->building)
                            &middot; {{ $selectedFacility->building->name }}
                        @endif
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fotel-badge fotel-badge-slate text-uppercase font-mono">
                                {{ ucfirst(str_replace('_', ' ', $selectedFacility->type)) }}
                            </span>
                        </div>
                        <div class="small font-mono text-muted">
                            Batas Durasi: <strong class="text-dark">{{ $maxMinutes / 60 }} Jam</strong>
                        </div>
                    </div>
                </div>

                {{-- Ketersediaan Slot Hari Terpilih --}}
                @if(count($slots) > 0)
                    <div class="p-4 bg-white border rounded mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0 fs-6 text-uppercase" style="letter-spacing: 0.05em;">
                                Ketersediaan Slot {{ $selectedDate }}
                            </h6>
                            <span class="small font-mono text-muted">26 Slot</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="max-height: 180px; overflow-y: auto;">
                            @foreach($slots as $s)
                                <span class="fotel-badge {{ $s['is_available'] ? 'fotel-badge-green' : 'fotel-badge-slate' }} font-mono" style="font-size: 0.68rem;">
                                    {{ $s['start_time'] }}-{{ $s['end_time'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="p-4 bg-light border rounded text-center text-muted mb-4">
                    <div class="fw-bold fs-6 mb-1">Belum Ada Fasilitas Dipilih</div>
                    <p class="small mb-0">Silakan pilih fasilitas pada formulir untuk melihat rincian batas durasi dan ketersediaan slot harian.</p>
                </div>
            @endif

            {{-- Info Card Kebijakan & Aturan Kampus --}}
            <div class="fotel-info-card mt-0">
                <div class="fotel-info-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>Ketentuan Peminjaman Kampus</span>
                </div>
                <ul class="fotel-info-list">
                    <li><strong>Jam Operasional:</strong> 07.00 - 20.00 WIB (26 slot tetap per hari).</li>
                    <li><strong>Kelipatan 30 Menit:</strong> Pemilihan waktu wajib tepat kelipatan :00 atau :30.</li>
                    <li><strong>Batas Rangkaian:</strong> Durasi dihitung akumulatif per fasilitas untuk akun yang sama.</li>
                    <li><strong>Batal Mandiri:</strong> Dapat dibatalkan mandiri paling lambat <strong>2 jam</strong> sebelum waktu mulai.</li>
                </ul>
            </div>
        </div>

        {{-- Kolom Kanan: Formulir Input Pengajuan --}}
        <div class="col-lg-7 order-lg-1">
            <div class="p-4 p-md-5 bg-white border rounded">
                <form method="POST" action="{{ route('reservations.store') }}">
                    @csrf

                    {{-- 1. Pilihan Fasilitas --}}
                    <div class="mb-4">
                        <label for="facility_id" class="form-label small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                            Pilih Fasilitas Kampus <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('facility_id') is-invalid @enderror" 
                                id="facility_id" name="facility_id" 
                                onchange="const url = '{{ route('reservations.create') }}?facility_id=' + encodeURIComponent(this.value) + '&date=' + encodeURIComponent(document.getElementById('date').value); window.SoraAjax ? SoraAjax.navigate(url) : window.location.assign(url)">
                            <option value="">-- Pilih Fasilitas Aktif --</option>
                            @foreach($facilities as $fac)
                                <option value="{{ $fac->id }}" @selected(old('facility_id', $selectedFacility?->id) == $fac->id)>
                                    {{ $fac->name }} ({{ ucfirst(str_replace('_', ' ', $fac->type)) }} &middot; {{ $fac->location_detail }})
                                </option>
                            @endforeach
                        </select>
                        @error('facility_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 2. Tanggal Peminjaman & Jumlah Unit --}}
                    <div class="row g-3 mb-4">
                        <div class="{{ ($selectedFacility && $selectedFacility->isEquipment()) ? 'col-md-6' : 'col-md-12' }}">
                            <label for="date" class="form-label small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                                Tanggal Peminjaman <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control font-mono @error('date') is-invalid @enderror" 
                                   id="date" name="date" 
                                   value="{{ old('date', $selectedDate) }}" 
                                   min="{{ $today }}" 
                                   max="{{ $maxDate }}"
                                   onchange="if(document.getElementById('facility_id').value) { const url = '{{ route('reservations.create') }}?facility_id=' + encodeURIComponent(document.getElementById('facility_id').value) + '&date=' + encodeURIComponent(this.value); window.SoraAjax ? SoraAjax.navigate(url) : window.location.assign(url); }">
                            <div class="form-text small">Maksimal pengajuan 90 hari ke depan.</div>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($selectedFacility && $selectedFacility->isEquipment())
                            <div class="col-md-6">
                                <label for="quantity" class="form-label small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                                    Jumlah Unit <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="fotel-stepper flex-grow-1">
                                        <button type="button" class="fotel-stepper-btn" onclick="decreaseFormQty()">-</button>
                                        <input type="number" class="fotel-stepper-input font-mono w-100 @error('quantity') is-invalid @enderror" 
                                               id="quantity" name="quantity" 
                                               value="{{ old('quantity', 1) }}" 
                                               min="1" 
                                               max="{{ max(1, ($selectedFacility->equipmentDetail->stock_total ?? 1) - ($selectedFacility->equipmentDetail->stock_unavailable ?? 0)) }}">
                                        <button type="button" class="fotel-stepper-btn" onclick="increaseFormQty()">+</button>
                                    </div>
                                </div>
                                <div class="form-text small font-mono">
                                    Siap pakai: {{ max(0, ($selectedFacility->equipmentDetail->stock_total ?? 0) - ($selectedFacility->equipmentDetail->stock_unavailable ?? 0)) }} unit.
                                </div>
                                @error('quantity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>

                    {{-- 3. Rentang Waktu: Mulai & Selesai --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="start_time" class="form-label small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                                Waktu Mulai <span class="text-danger">*</span>
                            </label>
                            <select class="form-select font-mono @error('start_time') is-invalid @enderror" id="start_time" name="start_time">
                                <option value="">-- Pilih Jam Mulai --</option>
                                @foreach($slotDefinitions as $def)
                                    <option value="{{ $def['start_time'] }}" @selected(old('start_time', request('start_time')) === $def['start_time'])>
                                        {{ $def['start_time'] }} WIB
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Kelipatan 30 menit (mulai 07.00).</div>
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="end_time" class="form-label small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                                Waktu Selesai <span class="text-danger">*</span>
                            </label>
                            <select class="form-select font-mono @error('end_time') is-invalid @enderror" id="end_time" name="end_time">
                                <option value="">-- Pilih Jam Selesai --</option>
                                @foreach($slotDefinitions as $def)
                                    <option value="{{ $def['end_time'] }}" @selected(old('end_time') === $def['end_time'])>
                                        {{ $def['end_time'] }} WIB
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Kelipatan 30 menit (selesai maks. 20.00).</div>
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Live Duration Bar Indicator --}}
                    <div class="p-3 mb-4 rounded bg-light border d-flex justify-content-between align-items-center" id="durationBox">
                        <div class="small text-secondary">
                            Durasi Terhitung: <strong class="text-dark font-mono" id="durationText">-</strong>
                        </div>
                        <div class="small font-mono text-muted" id="slotCountText">
                            -
                        </div>
                    </div>

                    {{-- 4. Tujuan & Agenda Kegiatan --}}
                    <div class="mb-4">
                        <label for="purpose" class="form-label small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                            Tujuan / Agenda Kegiatan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('purpose') is-invalid @enderror" 
                                  id="purpose" name="purpose" rows="3" 
                                  placeholder="Contoh: Perkuliahan Pengganti Praktikum Jaringan Komputer Kelas B">{{ old('purpose') }}</textarea>
                        <div class="form-text small">
                            Jelaskan agenda secara ringkas (5 - 255 karakter). Rincian ini bersifat rahasia dan hanya dapat diverifikasi oleh petugas fasilitas.
                        </div>
                        @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                        <a href="{{ route('facilities.index') }}" class="btn-fotel-secondary">
                            &larr; Batal
                        </a>
                        <button type="submit" class="btn-fotel-gold px-4 py-2">
                            Kirim Pengajuan Reservasi &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="{{ asset('js/slot-picker.js') }}"></script>
<script>
    function decreaseFormQty() {
        const input = document.getElementById('quantity');
        if (!input) return;
        let val = parseInt(input.value, 10);
        if (val > 1) {
            input.value = val - 1;
        }
    }
    function increaseFormQty() {
        const input = document.getElementById('quantity');
        if (!input) return;
        let val = parseInt(input.value, 10);
        let max = parseInt(input.max, 10) || 99;
        if (val < max) {
            input.value = val + 1;
        }
    }
</script>
@endsection
