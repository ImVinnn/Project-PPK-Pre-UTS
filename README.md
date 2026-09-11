# Sistem Reservasi & Pelaporan Fasilitas Kampus

Aplikasi web untuk mengelola penggunaan fasilitas kampus — ruang kelas, aula,
laboratorium, alat, dan lapangan. Pengguna dapat mengecek ketersediaan,
mengajukan reservasi, dan melaporkan kerusakan fasilitas. Petugas dan admin
memproses kedua alur secara terpusat dalam satu sistem.

Project mata kuliah Pemrograman Perangkat Lunak Berbasis Web (PPK) 2026.

---

## Aktor

| Aktor | Hak akses |
|---|---|
| **Pengunjung** | Melihat daftar fasilitas dan ketersediaan slot, tanpa login |
| **Pengguna** | Mengajukan reservasi, membatalkan reservasi sendiri, melaporkan kerusakan |
| **Petugas** | Memproses antrian reservasi & laporan, mengubah status fasilitas |
| **Admin** | Mengelola data fasilitas, akun, dan rekap lintas fasilitas |

---

## Tech Stack

| Bagian | Pilihan |
|---|---|
| Framework | Laravel |
| Bahasa | PHP 8.2+ |
| Database | MySQL |
| ORM | Eloquent |
| Skema DB | Migration |
| UI | Blade + Bootstrap 5 (CDN) |
| Auth | Session + bcrypt (bawaan Laravel) |
| Guard role | Middleware |
| Validasi server | Form Request |
| Validasi client | Vanilla JS + atribut HTML5 |
| Upload foto | Storage facade |
| Ekspor rekap | CSV |

Rendering: **server-side rendering** dengan Blade. Tanpa build step.

---

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru
- Composer
- MySQL / MariaDB
- Laragon atau XAMPP (untuk MySQL)

---

## Cara Menjalankan

```bash
# 1. Clone repository
git clone <url-repo>
cd project-ppk

# 2. Install dependensi
composer install

# 3. Siapkan file konfigurasi
cp .env.example .env
php artisan key:generate

# 4. Buat database bernama 'ppk_reservasi' di phpMyAdmin,
#    lalu sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env

# 5. Buat tabel dan isi data awal
php artisan migrate --seed

# 6. Buat symbolic link untuk foto laporan
php artisan storage:link

# 7. Jalankan
php artisan serve
```

Buka `http://localhost:8000`.

Pastikan **MySQL sudah dinyalakan** lewat control panel Laragon/XAMPP.
Apache tidak perlu dijalankan.

---

## Akun Demo

| Role | Email | Password |
|---|---|---|
| Admin | _(isi)_ | _(isi)_ |
| Petugas | _(isi)_ | _(isi)_ |
| Pengguna | _(isi)_ | _(isi)_ |
| Pengguna (pending) | _(isi)_ | _(isi)_ |

---

## Aturan Bisnis

**Jam operasional:** 07.00–20.00

**Slot reservasi:** tetap 30 menit (07.00–07.30, 07.30–08.00, dst), 26 slot per hari

**Validasi reservasi** dilakukan di sisi server:
- `start_time` dan `end_time` kelipatan 30 menit
- Keduanya berada dalam jam operasional
- `end_time` lebih besar dari `start_time`
- Fasilitas berstatus aktif

**Deteksi bentrok** dijalankan saat petugas menyetujui reservasi, bukan saat
pengguna mengajukan. Dua pengguna boleh mengajukan slot yang sama; petugas
yang menentukan.

**Privasi:** pengunjung hanya melihat status tersedia/tidak tersedia. Nama
pemohon dan tujuan penggunaan tidak dikirim ke browser. Pemilik reservasi
dapat melihat detail lengkap miliknya sendiri.

**Pendaftaran akun:** petugas tidak melakukan registrasi mandiri dalam kondisi
apa pun. Pengguna dapat mendaftar sendiri, tetapi akun harus diverifikasi
admin sebelum dapat digunakan untuk login.

---

## Status

| Entitas | Nilai |
|---|---|
| Reservasi | `pending`, `approved`, `rejected`, `cancelled_user`, `cancelled_officer` |
| Laporan | `baru`, `diproses`, `selesai`, `ditolak` |
| Fasilitas | `aktif`, `perbaikan`, `nonaktif` |
| Akun | `pending`, `aktif`, `ditolak` |

---

## Asumsi

Asumsi tambahan yang disepakati tim:

1. Batas waktu pembatalan mandiri: _(isi — misal minimal 2 jam sebelum mulai)_
2. Reservasi berstatus `pending` memblokir slot di tampilan publik: _(ya/tidak)_
3. Satu reservasi boleh mencakup beberapa slot berurutan: _(ya/tidak)_
4. Reservasi `approved` pada fasilitas yang mendadak masuk perbaikan: _(isi)_

---

## Struktur Folder

```
app/Http/Controllers/   logika per domain
app/Http/Middleware/    guard role
app/Http/Requests/      validasi server
app/Models/             Eloquent model
app/Support/Status.php  konstanta status
routes/web.php          definisi route
database/migrations/    struktur tabel
database/seeders/       data awal
resources/views/        halaman Blade
public/js/              validasi & interaksi client
```
