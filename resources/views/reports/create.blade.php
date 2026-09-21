@extends('layouts.app')

@section('title', 'Pelaporan Kerusakan Fasilitas')

@section('content')
<style>
    .hero-header {
        background-color: #1e2a38;
        border-bottom: 3px solid #ab7a2c;
    }
    .btn-gold {
        background-color: #ab7a2c;
        color: #ffffff;
        border: none;
    }
    .btn-gold:hover {
        background-color: #926622;
        color: #ffffff;
    }
    .card-custom {
        border: 1px solid #e2ded4;
    }
    .card-header-custom {
        background-color: #1e2a38;
        color: #ffffff;
    }
</style>

<!-- Header Navy -->
<div class="hero-header py-4 text-white mb-5">
    <div class="container">
        <h2 class="fw-bold mb-1">Pelaporan Kerusakan Fasilitas</h2>
        <p class="mb-0 text-light opacity-75">Sampaikan kendala fasilitas kampus agar segera ditangani oleh petugas.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-custom shadow-sm rounded-3">
                <div class="card-header card-header-custom py-3 rounded-top">
                    <h5 class="mb-0 fw-semibold">Form Laporan Kerusakan</h5>
                </div>
                <div class="card-body p-4">

                    {{-- Flash / global error --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('reports.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Pilihan Fasilitas -->
                        <div class="mb-3">
                            <label for="facility_id" class="form-label fw-semibold">Fasilitas yang Rusak</label>
                            <select class="form-select @error('facility_id') is-invalid @enderror" id="facility_id" name="facility_id" required>
                                <option value="" disabled {{ old('facility_id') ? '' : 'selected' }}>-- Pilih Fasilitas --</option>
                                @foreach ($facilities as $facility)
                                    <option value="{{ $facility->id }}" @selected(old('facility_id') == $facility->id)>
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('facility_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pilihan Kategori -->
                        <div class="mb-3">
                            <label for="category" class="form-label fw-semibold">Kategori Kerusakan</label>
                            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                <option value="" disabled {{ old('category') ? '' : 'selected' }}>-- Pilih Kategori --</option>
                                <option value="kerusakan_fisik" @selected(old('category') === 'kerusakan_fisik')>Kerusakan Fisik (Meja, Kursi, Pintu)</option>
                                <option value="kelistrikan" @selected(old('category') === 'kelistrikan')>Kelistrikan / AC / Lampu</option>
                                <option value="kebersihan" @selected(old('category') === 'kebersihan')>Kebersihan / Sanitasi</option>
                                <option value="perlengkapan" @selected(old('category') === 'perlengkapan')>Perlengkapan / Perangkat</option>
                                <option value="lainnya" @selected(old('category') === 'lainnya')>Lainnya</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Keterangan Kategori Lainnya -->
                        <div class="mb-3" id="other-category-wrapper" style="{{ old('category') === 'lainnya' ? '' : 'display:none;' }}">
                            <label for="other_category" class="form-label fw-semibold">Keterangan Kategori Lainnya</label>
                            <input type="text" class="form-control @error('other_category') is-invalid @enderror"
                                   id="other_category" name="other_category"
                                   value="{{ old('other_category') }}"
                                   maxlength="100"
                                   placeholder="Jelaskan kategori kerusakan...">
                            @error('other_category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Deskripsi Detail Kerusakan</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Jelaskan detail kerusakan yang kamu temukan..." required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Upload Foto -->
                        <div class="mb-3">
                            <label for="photo-input" class="form-label fw-semibold">Unggah Foto Bukti</label>
                            <input class="form-control @error('photo') is-invalid @enderror" type="file" id="photo-input" name="photo" accept="image/jpeg,image/png" required>
                            <small class="text-muted">Format: JPG, JPEG, PNG (Maksimal 2MB)</small>
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pratinjau Foto -->
                        <div class="mb-3 text-center d-none" id="preview-container">
                            <p class="mb-1 text-muted small">Pratinjau Foto:</p>
                            <img id="photo-preview" src="#" alt="Pratinjau Foto" class="img-thumbnail" style="max-height: 200px;">
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Kembali</a>
                            <button type="submit" class="btn btn-gold px-4">Kirim Laporan</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/photo-preview.js') }}"></script>
<script>
    // Toggle other_category field visibility based on selected category
    document.getElementById('category').addEventListener('change', function () {
        const wrapper = document.getElementById('other-category-wrapper');
        const input = document.getElementById('other_category');
        if (this.value === 'lainnya') {
            wrapper.style.display = '';
            input.setAttribute('required', 'required');
        } else {
            wrapper.style.display = 'none';
            input.removeAttribute('required');
            input.value = '';
        }
    });
</script>
@endsection