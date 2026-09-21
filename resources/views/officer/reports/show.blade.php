@extends('layouts.app')

@section('title', 'Detail & Tindak Lanjut Laporan — Petugas')

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
    .detail-label {
        font-weight: 600;
        color: #56636d;
        width: 180px;
    }
</style>

<!-- Header Navy -->
<div class="hero-header py-4 text-white mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Tindak Lanjut Laporan #{{ $report->id }}</h2>
            <p class="mb-0 text-light opacity-75">Periksa bukti kerusakan dan perbarui status penanganan.</p>
        </div>
        <a href="{{ route('officer.reports.index') }}" class="btn btn-outline-light btn-sm px-3">
            ← Kembali ke Antrean
        </a>
    </div>
</div>

<div class="container mb-5">

    {{-- Flash message sukses --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Global alert errors --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Kolom Kiri: Detail Laporan & Foto Bukti -->
        <div class="col-lg-7">
            <div class="card card-custom shadow-sm rounded-3">
                <div class="card-header card-header-custom py-3 rounded-top d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Informasi Laporan</h5>
                    <span class="badge {{ $report->statusBadge() }} fs-6">{{ $report->statusLabel() }}</span>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-4">
                        <tbody>
                            <tr>
                                <td class="detail-label">Fasilitas</td>
                                <td class="fw-semibold">{{ $report->facility->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="detail-label">Pelapor</td>
                                <td>
                                    <strong>{{ $report->user->name ?? '-' }}</strong>
                                    <div class="text-muted small">{{ $report->user->email ?? '-' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label">Tanggal Masuk</td>
                                <td>{{ $report->created_at->format('d M Y, H:i') }} WIB</td>
                            </tr>
                            <tr>
                                <td class="detail-label">Kategori</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $report->categoryLabel() }}</span>
                                    @if ($report->category === 'lainnya' && $report->other_category)
                                        <span class="ms-1 text-muted">&mdash; {{ $report->other_category }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label">Deskripsi</td>
                                <td class="text-break">{{ $report->description }}</td>
                            </tr>
                            <tr>
                                <td class="detail-label">Catatan Saat Ini</td>
                                <td>
                                    @if ($report->resolution_note)
                                        <div class="p-2 bg-light border rounded small">{{ $report->resolution_note }}</div>
                                    @else
                                        <em class="text-muted small">Belum ada catatan resolusi.</em>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Foto Bukti -->
                    <div>
                        <h6 class="fw-semibold mb-2">Foto Bukti Kerusakan</h6>
                        @if ($report->photo_path)
                            <div class="border rounded p-2 bg-light text-center">
                                <a href="{{ Storage::url($report->photo_path) }}" target="_blank" title="Klik untuk memperbesar">
                                    <img src="{{ Storage::url($report->photo_path) }}"
                                         alt="Foto Bukti Kerusakan"
                                         class="img-fluid rounded"
                                         style="max-height: 380px; object-fit: contain;">
                                </a>
                                <div class="small text-muted mt-1">Klik gambar untuk melihat ukuran penuh</div>
                            </div>
                        @else
                            <p class="text-muted small"><em>Tidak ada foto bukti terlampir.</em></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Tindak Lanjut Petugas (US12) -->
        <div class="col-lg-5">
            <div class="card card-custom shadow-sm rounded-3 sticky-top" style="top: 20px;">
                <div class="card-header card-header-custom py-3 rounded-top">
                    <h5 class="mb-0 fw-semibold">Tindak Lanjut Petugas</h5>
                </div>
                <div class="card-body p-4">

                    <form action="{{ route('officer.reports.status.update', $report) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <!-- Pilih Status Baru -->
                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Perbarui Status Laporan</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="baru" @selected(old('status', $report->status) === 'baru')>
                                    Baru (Menunggu Tindak Lanjut)
                                </option>
                                <option value="diproses" @selected(old('status', $report->status) === 'diproses')>
                                    Diproses (Sedang Ditangani)
                                </option>
                                <option value="selesai" @selected(old('status', $report->status) === 'selesai')>
                                    Selesai (Kerusakan Teratasi)
                                </option>
                                <option value="ditolak" @selected(old('status', $report->status) === 'ditolak')>
                                    Ditolak (Tidak Valid / Dibatalkan)
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Catatan Resolusi -->
                        <div class="mb-4">
                            <label for="resolution_note" class="form-label fw-semibold">
                                Catatan Resolusi
                                <span class="text-danger" id="resolution-required-star" style="display: none;">*</span>
                            </label>
                            <textarea class="form-control @error('resolution_note') is-invalid @enderror"
                                      id="resolution_note"
                                      name="resolution_note"
                                      rows="5"
                                      placeholder="Tuliskan keterangan penanganan atau alasan penolakan laporan...">{{ old('resolution_note', $report->resolution_note) }}</textarea>
                            <small class="text-muted d-block mt-1" id="resolution-hint">
                                * Wajib diisi apabila status diubah menjadi <strong>Selesai</strong> atau <strong>Ditolak</strong>.
                            </small>
                            @error('resolution_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-gold py-2 fw-semibold">
                                Simpan Perubahan Status
                            </button>
                            <a href="{{ route('officer.reports.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusSelect = document.getElementById('status');
        const resolutionStar = document.getElementById('resolution-required-star');
        const resolutionHint = document.getElementById('resolution-hint');
        const resolutionTextarea = document.getElementById('resolution_note');

        function updateRequirement() {
            const val = statusSelect.value;
            if (val === 'selesai' || val === 'ditolak') {
                resolutionStar.style.display = 'inline';
                resolutionHint.classList.add('text-danger');
                resolutionHint.classList.remove('text-muted');
                resolutionTextarea.setAttribute('required', 'required');
            } else {
                resolutionStar.style.display = 'none';
                resolutionHint.classList.remove('text-danger');
                resolutionHint.classList.add('text-muted');
                resolutionTextarea.removeAttribute('required');
            }
        }

        statusSelect.addEventListener('change', updateRequirement);
        updateRequirement();
    });
</script>
@endsection

