@extends('layouts.app')

@section('title', 'Fasilitas Kampus')

@section('content')
<section class="hero-band" style="background: var(--ink); color: #f5f3ed;">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="font-serif fw-semibold display-6 mb-3">Papan Ketersediaan Fasilitas</h1>
                <p class="mb-0" style="color: #c7cdd2; max-width: 52ch;">
                    Cek status, lokasi, dan kapasitas fasilitas kampus sebelum mengajukan
                    reservasi. Masuk untuk mengajukan peminjaman atau melaporkan kerusakan.
                </p>
            </div>
        </div>

        <div class="row g-3 mt-4">
            <div class="col-6 col-md-3">
                <div class="p-3" style="border: 1px solid #3a4a58; border-top: 2px solid var(--brass);">
                    <div class="font-mono fs-3 fw-semibold">5</div>
                    <div class="small" style="color: #a9b2ba;">Total fasilitas</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3" style="border: 1px solid #3a4a58; border-top: 2px solid var(--moss);">
                    <div class="font-mono fs-3 fw-semibold">3</div>
                    <div class="small" style="color: #a9b2ba;">Aktif</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3" style="border: 1px solid #3a4a58; border-top: 2px solid var(--amber);">
                    <div class="font-mono fs-3 fw-semibold">2</div>
                    <div class="small" style="color: #a9b2ba;">Dalam perbaikan</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <form class="row g-2 align-items-end mb-4 pb-4" style="border-bottom: 1px solid var(--line);">
        <div class="col-md-3">
            <label for="tipe" class="form-label small text-muted mb-1">Tipe</label>
            <select class="form-select" id="tipe" name="tipe">
                <option value="">Semua tipe</option>
                <option value="ruang_kelas">Ruang kelas</option>
                <option value="laboratorium">Laboratorium</option>
                <option value="aula">Aula</option>
                <option value="alat">Alat</option>
                <option value="lapangan">Lapangan</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="lokasi" class="form-label small text-muted mb-1">Lokasi</label>
            <input type="text" class="form-control" id="lokasi" name="lokasi" placeholder="Contoh: Gedung B">
        </div>
        <div class="col-md-3">
            <label for="kapasitas_min" class="form-label small text-muted mb-1">Kapasitas minimal</label>
            <input type="number" class="form-control" id="kapasitas_min" name="kapasitas_min" min="0" placeholder="0">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Terapkan filter</button>
        </div>
    </form>

    <div class="facility-list">
        <div class="row py-3 align-items-center" style="border-bottom: 1px solid var(--line);">
            <div class="col-md-4">
                <div class="fw-semibold">Ruang Kelas A101</div>
                <div class="small text-muted">Ruang kelas &middot; Gedung A, Lantai 1</div>
            </div>
            <div class="col-4 col-md-3 font-mono">40 orang</div>
            <div class="col-8 col-md-5 text-md-end">
                <span class="d-inline-block rounded-circle me-2" style="width: 8px; height: 8px; background: var(--moss);"></span>
                Aktif
            </div>
        </div>
        <div class="row py-3 align-items-center" style="border-bottom: 1px solid var(--line);">
            <div class="col-md-4">
                <div class="fw-semibold">Lab Komputer 1</div>
                <div class="small text-muted">Laboratorium &middot; Gedung B, Lantai 2</div>
            </div>
            <div class="col-4 col-md-3 font-mono">30 orang</div>
            <div class="col-8 col-md-5 text-md-end">
                <span class="d-inline-block rounded-circle me-2" style="width: 8px; height: 8px; background: var(--moss);"></span>
                Aktif
            </div>
        </div>
        <div class="row py-3 align-items-center" style="border-bottom: 1px solid var(--line);">
            <div class="col-md-4">
                <div class="fw-semibold">Aula Serbaguna</div>
                <div class="small text-muted">Aula &middot; Gedung C, Lantai 1</div>
            </div>
            <div class="col-4 col-md-3 font-mono">250 orang</div>
            <div class="col-8 col-md-5 text-md-end">
                <span class="d-inline-block rounded-circle me-2" style="width: 8px; height: 8px; background: var(--amber);"></span>
                Perbaikan
            </div>
        </div>
        <div class="row py-3 align-items-center" style="border-bottom: 1px solid var(--line);">
            <div class="col-md-4">
                <div class="fw-semibold">Lapangan Basket</div>
                <div class="small text-muted">Lapangan &middot; Area Timur</div>
            </div>
            <div class="col-4 col-md-3 font-mono">100 orang</div>
            <div class="col-8 col-md-5 text-md-end">
                <span class="d-inline-block rounded-circle me-2" style="width: 8px; height: 8px; background: var(--moss);"></span>
                Aktif
            </div>
        </div>
        <div class="row py-3 align-items-center">
            <div class="col-md-4">
                <div class="fw-semibold">Lab Jaringan</div>
                <div class="small text-muted">Laboratorium &middot; Gedung B, Lantai 3</div>
            </div>
            <div class="col-4 col-md-3 font-mono">25 orang</div>
            <div class="col-8 col-md-5 text-md-end">
                <span class="d-inline-block rounded-circle me-2" style="width: 8px; height: 8px; background: var(--amber);"></span>
                Perbaikan
            </div>
        </div>
    </div>
</section>
@endsection
