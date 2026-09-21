<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Laporan Saya</title>
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
    </style>
</head>
<body>

<!-- Header Navy -->
<div class="hero-header py-4 text-white mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Status Laporan Kerusakan</h2>
            <p class="mb-0 text-light opacity-75">Pantau status penanganan laporan yang telah kamu ajukan.</p>
        </div>
        <a href="#" class="btn btn-gold px-3">+ Buat Laporan Baru</a>
    </div>
</div>

<div class="container mb-5">
    <div class="card card-custom shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 ps-4">No</th>
                            <th class="py-3">Fasilitas</th>
                            <th class="py-3">Kategori</th>
                            <th class="py-3">Deskripsi</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 pe-4">Catatan Resolusi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-4">1</td>
                            <td class="fw-semibold">Laboratorium Komputer 1</td>
                            <td><span class="badge bg-secondary">Elektronik</span></td>
                            <td>PC meja 04 mati total dan bau sangit</td>
                            <td>14 Sep 2026</td>
                            <td><span class="badge bg-warning text-dark">Baru</span></td>
                            <td class="pe-4 text-muted small"><em>Belum ditangani</em></td>
                        </tr>
                        <tr>
                            <td class="ps-4">2</td>
                            <td class="fw-semibold">Aula Utama Gedung A</td>
                            <td><span class="badge bg-secondary">Kelistrikan</span></td>
                            <td>AC bagian timur meneteskan air deras</td>
                            <td>10 Sep 2026</td>
                            <td><span class="badge bg-info text-dark">Diproses</span></td>
                            <td class="pe-4 text-muted small">Teknisi sedang mengecek kompresor AC.</td>
                        </tr>
                        <tr>
                            <td class="ps-4">3</td>
                            <td class="fw-semibold">Ruang Kelas 301</td>
                            <td><span class="badge bg-secondary">Fisik</span></td>
                            <td>Gagang pintu patah</td>
                            <td>05 Sep 2026</td>
                            <td><span class="badge bg-success">Selesai</span></td>
                            <td class="pe-4 text-muted small">Gagang pintu sudah diganti baru.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>