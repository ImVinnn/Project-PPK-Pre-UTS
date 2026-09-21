<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporkan Kerusakan Fasilitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark-navy: #1e2a38;
            --accent-gold: #ab7a2c;
            --bg-cream: #f7f5f0;
        }
        body {
            background-color: var(--bg-cream);
        }
        .hero-header {
            background-color: var(--bg-dark-navy);
            border-bottom: 3px solid var(--accent-gold);
        }
        .btn-gold {
            background-color: var(--accent-gold);
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
            background-color: var(--bg-dark-navy);
            color: #ffffff;
        }
    </style>
</head>
<body>

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
                    
                    <form action="#" method="POST" enctype="multipart/form-data">
                        
                        <!-- Pilihan Fasilitas -->
                        <div class="mb-3">
                            <label for="facility_id" class="form-label fw-semibold">Fasilitas yang Rusak</label>
                            <select class="form-select" id="facility_id" name="facility_id" required>
                                <option value="">-- Pilih Fasilitas --</option>
                                <option value="1">Aula Utama Gedung A</option>
                                <option value="2">Laboratorium Komputer 1</option>
                                <option value="3">Ruang Kelas 301</option>
                            </select>
                        </div>

                        <!-- Pilihan Kategori -->
                        <div class="mb-3">
                            <label for="category" class="form-label fw-semibold">Kategori Kerusakan</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="kelistrikan">Kelistrikan / AC / Lampu</option>
                                <option value="fisik">Fasilitas Fisik (Meja, Kursi, Pintu)</option>
                                <option value="elektronik">Perangkat Komputer / Proyektor</option>
                                <option value="sanitasi">Sanitasi / Kebersihan</option>
                                <option value="lainnya">Lain-lain</option>
                            </select>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Deskripsi Detail Kerusakan</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Jelaskan detail kerusakan yang kamu temukan..." required></textarea>
                        </div>

                        <!-- Upload Foto -->
                        <div class="mb-3">
                            <label for="photo-input" class="form-label fw-semibold">Unggah Foto Bukti</label>
                            <input class="form-control" type="file" id="photo-input" name="photo" accept="image/*" required>
                            <small class="text-muted">Format: JPG, PNG, WEBP (Maksimal 2MB)</small>
                        </div>

                        <!-- Pratinjau Foto -->
                        <div class="mb-3 text-center d-none" id="preview-container">
                            <p class="mb-1 text-muted small">Pratinjau Foto:</p>
                            <img id="photo-preview" src="#" alt="Pratinjau Foto" class="img-thumbnail" style="max-height: 200px;">
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="#" class="btn btn-outline-secondary">Kembali</a>
                            <button type="submit" class="btn btn-gold px-4">Kirim Laporan</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/photo-preview.js') }}"></script>
</body>
</html>