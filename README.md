<p align="center">
  <img src="public/images/brand/sora-icon.png" alt="SORA" width="96">
</p>

<h1 align="center">SORA</h1>

<p align="center">
  <strong>Sistem Operasional Reservasi dan Aduan Fasilitas Kampus</strong>
</p>

---

**SORA** adalah aplikasi web terpusat untuk memesan fasilitas kampus sekaligus melaporkan kerusakannya. Pengunjung dapat melihat fasilitas dan ketersediaan slot waktunya, pengguna dapat mengajukan reservasi dan melaporkan masalah, sementara petugas dan admin memproses kedua alur itu dalam satu sistem — dari pengajuan sampai penanganan.

Fasilitas yang dikelola mencakup ruang kelas, aula, laboratorium, lapangan, dan alat.

> Proyek ini dikembangkan untuk memenuhi tugas **Project PPK 2026 — Web Platform Pra-UTS**.

## Kontributor

| Nama | NIM | Profil GitHub |
|---|---|---|
| Haydar Rafi' Sultansyah | 24060124120023 | [HydraFish](https://github.com/HydraFish) |
| Syair Adharian | 24060124140172 | [szqiel](https://github.com/szqiel) |
| Marco Falias Pangkado | 24060124130112 | [Moco1206](https://github.com/Moco1206) |
| Banyuputra | 24060124140193 | [ImVinnn](https://github.com/ImVinnn) |

## Teknologi yang Digunakan

- **Bahasa pemrograman:** PHP 8.2+
- **Framework:** Laravel dengan arsitektur MVC
- **Template engine:** Blade (server-side rendering)
- **Basis data:** MySQL / MariaDB
- **ORM dan migrasi:** Eloquent dan Laravel Migration
- **Autentikasi:** Session Laravel dengan hash bcrypt
- **Frontend:** HTML, CSS, JavaScript murni, dan Bootstrap 5 melalui CDN
- **Interaksi:** AJAX berbasis `fetch` tanpa build step
- **Pengujian:** PHPUnit dan Node test runner

Tidak ada npm atau build step — aplikasi berjalan cukup dengan PHP, Composer, dan MySQL.

## Fitur Aplikasi

### Autentikasi dan Akun

- Registrasi mandiri untuk pengguna, dengan akun menunggu verifikasi admin.
- Login dan logout dengan sesi; hanya akun aktif yang dapat masuk.
- Admin mendaftarkan akun petugas dan pengguna secara langsung.
- Admin memverifikasi atau menolak akun hasil registrasi mandiri.
- Petugas tidak dapat mendaftar sendiri dalam kondisi apa pun.
- Hak akses berdasarkan peran: pengunjung, pengguna, petugas, dan admin.

### Fasilitas dan Ketersediaan

- Daftar fasilitas beserta ketersediaan per slot 30 menit.
- Pencarian berdasarkan tipe, lokasi, dan kapasitas.
- Detail pemohon dan tujuan penggunaan tidak pernah dikirim ke halaman publik.
- Stok alat dikelola sebagai jumlah unit, termasuk unit yang sedang rusak.
- Admin menambah, mengubah, menonaktifkan, dan mengaktifkan kembali fasilitas.

### Reservasi

- Pengajuan reservasi pada rentang slot berurutan dengan tujuan penggunaan.
- Validasi di sisi server dan client untuk jam operasional, kelipatan 30 menit, dan batas durasi.
- Persetujuan dan penolakan oleh petugas, dengan pengecekan bentrok jadwal di dalam transaksi database.
- Pembatalan oleh pengguna sebelum batas waktu, dan pembatalan darurat oleh petugas dengan alasan.
- Riwayat, detail, dan status reservasi milik pengguna.

### Pelaporan Kerusakan dan Kondisi Fasilitas

- Laporan kerusakan dengan kategori, deskripsi, dan foto.
- Pemantauan status laporan milik sendiri.
- Petugas memproses laporan: baru, diproses, selesai, atau ditolak, dengan catatan resolusi.
- Laporan yang sudah selesai atau ditolak terkunci dan tidak dapat diubah.
- Petugas menandai fasilitas dalam perbaikan dan mengembalikannya ke aktif.
- Daftar reservasi yang terdampak ditampilkan saat fasilitas masuk perbaikan.

### Dashboard, Rekap, dan Ekspor

- Dashboard antrean reservasi dan laporan untuk petugas.
- Dashboard ringkasan akun dan fasilitas untuk admin.
- Rekap okupansi tempat, pemakaian alat, dan frekuensi laporan kerusakan.
- Filter rekap berdasarkan periode, fakultas, dan gedung.
- Ekspor rekap dalam format CSV.

## Peran Pengguna

| Peran | Akses utama |
|---|---|
| Pengunjung | Melihat fasilitas dan ketersediaan slot tanpa login. |
| Pengguna | Mengajukan dan membatalkan reservasi sendiri, membuat laporan kerusakan, serta melihat riwayat dan statusnya. |
| Petugas | Memproses reservasi dan laporan, membatalkan reservasi darurat, dan mengelola kondisi fasilitas. |
| Admin | Mengelola akun, fakultas, gedung, dan fasilitas, serta melihat dan mengekspor rekap. |

## Aturan Reservasi

- Jam operasional **07.00–20.00 WIB** dengan slot tetap **30 menit**.
- Waktu mulai dan selesai harus kelipatan 30 menit pada tanggal yang sama.
- Setiap jenis fasilitas memiliki batas durasi maksimal; reservasi bersambung milik pengguna yang sama dihitung sebagai satu rangkaian.
- Reservasi yang masih menunggu tidak mengunci slot. Bentrok dicegah saat petugas menyetujui.
- Pembatalan mandiri paling lambat **2 jam** sebelum waktu mulai.
- Fasilitas dalam perbaikan atau nonaktif tidak menerima pengajuan maupun persetujuan baru.

## Arsitektur

Aplikasi menerapkan pola **MVC** Laravel dengan pemisahan tanggung jawab berikut:

- **Route dan Middleware** — menentukan alamat halaman dan membatasi akses per peran.
- **Controller** — menerima permintaan dan mengatur alur.
- **Form Request** — memvalidasi seluruh input di sisi server.
- **Service** — menjalankan aturan bisnis lintas tabel, seperti ketersediaan slot, persetujuan reservasi, dan rekap.
- **Model** — mengakses data melalui Eloquent.
- **View** — menghasilkan halaman HTML dengan Blade.
- **Konfigurasi** — koneksi basis data diatur di `config/database.php` dan `.env`, terpisah dari logika dan tampilan.

## Struktur Folder Proyek

```text
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Controller umum, Officer/, dan Admin/
│   │   ├── Middleware/         # Pembatasan akses per peran
│   │   └── Requests/           # Validasi input sisi server
│   ├── Models/                 # Model Eloquent
│   ├── Services/               # Ketersediaan, persetujuan, dan rekap
│   └── Support/Status.php      # Konstanta status, peran, dan batas durasi
├── config/                     # Konfigurasi aplikasi dan basis data
├── database/
│   ├── migrations/             # Struktur tabel
│   └── seeders/                # Data awal
├── lang/id/                    # Pesan validasi bahasa Indonesia
├── public/
│   ├── css/                    # Tema tampilan
│   ├── js/                     # Validasi client dan interaksi AJAX
│   └── images/brand/           # Logo SORA
├── resources/views/            # Halaman Blade
├── routes/web.php              # Definisi route
└── tests/                      # Pengujian PHP dan JavaScript
```

## Menjalankan Proyek

### Prasyarat

- PHP 8.2 atau lebih baru
- Composer
- MySQL atau MariaDB, misalnya melalui XAMPP atau Laragon

### Langkah instalasi

Pastikan layanan MySQL berjalan, lalu jalankan perintah berikut satu per satu:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Buat basis data `ppk_reservasi`, lalu sesuaikan pengaturan berikut di `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=ppk_reservasi
DB_USERNAME=root
DB_PASSWORD=
```

Siapkan tabel dan data awal:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka `http://localhost:8000`.

> Pada PowerShell versi lama, jalankan perintah satu per satu karena `&&` tidak didukung.

### Akun demo

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@kampus.test` | `Password123!` |
| Petugas | `petugas@kampus.test` | `Password123!` |
| Pengguna | `pengguna@kampus.test` | `Password123!` |
| Pengguna (menunggu verifikasi) | `pending@kampus.test` | `Password123!` |

Akun menunggu verifikasi sengaja tidak dapat login sampai disetujui admin. Akun-akun ini hanya untuk pengembangan lokal.
