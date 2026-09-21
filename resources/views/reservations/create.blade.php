@extends('layouts.app')

@section('title', 'Ajukan Reservasi Fasilitas')

@section('content')
<section class="hero-band" style="background: var(--ink); color: #f5f3ed;">
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}" style="color: var(--brass);">Katalog Fasilitas</a></li>
                <li class="breadcrumb-item active text-light" aria-current="page">Pengajuan Reservasi</li>
            </ol>
        </nav>
        <h1 class="font-serif fw-semibold display-6 mb-1">Ajukan Reservasi Fasilitas</h1>
        <p class="mb-0 text-muted" style="color: #c7cdd2 !important;">
            Silakan lengkapi tanggal, jam penggunaan kelipatan 30 menit, dan agenda kegiatan Anda.
        </p>
    </div>
</section>

<section class="container py-5">
    @if ($errors->any())
        <div class="alert alert-danger border-danger mb-4">
            <div class="fw-semibold mb-2">Pengajuan reservasi tidak dapat diproses karena:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="p-4 bg-white border rounded shadow-sm">
                <form method="POST" action="{{ route('reservations.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="facility_id" class="form-label fw-semibold">Pilih Fasilitas Kampus <span class="text-danger">*</span></label>
                        <select class="form-select @error('facility_id') is-invalid @enderror" id="facility_id" name="facility_id" onchange="window.location.href = '{{ route('reservations.create') }}?facility_id=' + this.value + '&date=' + document.getElementById('date').value">
                            <option value="">-- Pilih Fasilitas Aktif --</option>
                            @foreach($facilities as $fac)
                                <option value="{{ $fac->id }}" @selected(old('facility_id', $selectedFacility?->id) == $fac->id)>
                                    {{ $fac->name }} ({{ ucfirst($fac->type) }} &middot; {{ $fac->location_detail }})
                                </option>
                            @endforeach
                        </select>
                        @error('facility_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($selectedFacility)
                        <div class="p-3 mb-4 rounded bg-light border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge" style="background: var(--brass); color: #1b2a36;">{{ ucfirst($selectedFacility->type) }}</span>
                                    <strong class="ms-2">{{ $selectedFacility->name }}</strong>
                                </div>
                                @php
                                    $maxMinutes = \App\Support\Status::MAX_DURATION_MINUTES[$selectedFacility->type] ?? 180;
                                @endphp
                                <div class="small text-secondary">
                                    Batas durasi: <strong>{{ $maxMinutes / 60 }} jam</strong> ({{ $maxMinutes / 30 }} slot berurutan)
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="date" class="form-label fw-semibold">Tanggal Peminjaman <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                   id="date" name="date" 
                                   value="{{ old('date', $selectedDate) }}" 
                                   min="{{ $today }}" 
                                   max="{{ $maxDate }}"
                                   onchange="if(document.getElementById('facility_id').value) { window.location.href = '{{ route('reservations.create') }}?facility_id=' + document.getElementById('facility_id').value + '&date=' + this.value; }">
                            <div class="form-text small">Maksimal pengajuan 90 hari ke depan.</div>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($selectedFacility && $selectedFacility->isEquipment())
                            <div class="col-md-6">
                                <label for="quantity" class="form-label fw-semibold">Jumlah Unit <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('quantity') is-invalid @enderror" 
                                       id="quantity" name="quantity" 
                                       value="{{ old('quantity', 1) }}" 
                                       min="1" 
                                       max="{{ max(1, ($selectedFacility->equipmentDetail->stock_total ?? 1) - ($selectedFacility->equipmentDetail->stock_unavailable ?? 0)) }}">
                                <div class="form-text small">
                                    Stok siap pakai: {{ max(0, ($selectedFacility->equipmentDetail->stock_total ?? 0) - ($selectedFacility->equipmentDetail->stock_unavailable ?? 0)) }} unit.
                                </div>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="start_time" class="form-label fw-semibold">Waktu Mulai <span class="text-danger">*</span></label>
                            <select class="form-select @error('start_time') is-invalid @enderror font-mono" id="start_time" name="start_time">
                                <option value="">-- Pilih Jam Mulai --</option>
                                @foreach($slotDefinitions as $def)
                                    <option value="{{ $def['start_time'] }}" @selected(old('start_time') === $def['start_time'])>
                                        {{ $def['start_time'] }} WIB
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Kelipatan 30 menit (mulai paling awal 07.00).</div>
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="end_time" class="form-label fw-semibold">Waktu Selesai <span class="text-danger">*</span></label>
                            <select class="form-select @error('end_time') is-invalid @enderror font-mono" id="end_time" name="end_time">
                                <option value="">-- Pilih Jam Selesai --</option>
                                @foreach($slotDefinitions as $def)
                                    <option value="{{ $def['end_time'] }}" @selected(old('end_time') === $def['end_time'])>
                                        {{ $def['end_time'] }} WIB
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Kelipatan 30 menit (selesai paling akhir 20.00).</div>
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="purpose" class="form-label fw-semibold">Tujuan / Agenda Kegiatan <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('purpose') is-invalid @enderror" 
                                  id="purpose" name="purpose" rows="3" 
                                  placeholder="Contoh: Perkuliahan Pengganti Mata Kuliah Jaringan Komputer Kelas B">{{ old('purpose') }}</textarea>
                        <div class="form-text small">Jelaskan kegiatan secara ringkas dan jelas (5 - 255 karakter). Data ini rahasia dan hanya dapat dilihat petugas.</div>
                        @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            Kirim Pengajuan Reservasi &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="p-4 bg-white border rounded shadow-sm mb-4">
                <h5 class="font-serif fw-semibold mb-3">Ketentuan Reservasi Kampus</h5>
                <ul class="small text-secondary ps-3 mb-0" style="line-height: 1.7;">
                    <li><strong>Jam Operasional:</strong> 07.00 - 20.00 WIB (26 slot tetap per hari).</li>
                    <li><strong>Tanggal Sama:</strong> Waktu mulai dan selesai wajib berada di tanggal yang sama (tidak ada reservasi lintas hari).</li>
                    <li><strong>Kelipatan 30 Menit:</strong> Pemilihan waktu harus pas kelipatan slot 30 menit (:00 atau :30).</li>
                    <li><strong>Batas Rangkaian:</strong> Reservasi berurutan milik akun sendiri pada fasilitas yang sama dijumlahkan durasinya terhadap batas maksimal jenis fasilitas.</li>
                    <li><strong>Status Awal:</strong> Pengajuan akan berstatus <code>pending</code> dan menunggu verifikasi petugas.</li>
                    <li><strong>Pembatalan Mandiri:</strong> Pengguna dapat membatalkan reservasi paling lambat <strong>2 jam</strong> sebelum waktu mulai.</li>
                </ul>
            </div>

            @if($selectedFacility && count($slots) > 0)
                <div class="p-4 bg-white border rounded shadow-sm">
                    <h6 class="font-serif fw-semibold mb-2">Ringkasan Slot {{ $selectedDate }}</h6>
                    <div class="small text-muted mb-3">{{ $selectedFacility->name }}</div>
                    <div class="d-flex flex-wrap gap-1" style="max-height: 200px; overflow-y: auto;">
                        @foreach($slots as $s)
                            <span class="badge font-mono {{ $s['is_available'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary text-white' }}" style="font-size: 0.72rem;">
                                {{ $s['start_time'] }}-{{ $s['end_time'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<script src="{{ asset('js/slot-picker.js') }}"></script>
@endsection
