# Sistem Reservasi dan Pelaporan Fasilitas Kampus

Project PPK 2026 — Pra UTS · **Deadline: 11 Oktober 2026, 12.00 WIB via Kulon**

> **PRD v1.0 adalah sumber kebutuhan.** README ini tata cara kerja harian dan kontrak
> untuk AI. Jika keduanya bertentangan, PRD yang menang — laporkan konfliknya, jangan
> ubah aturan diam-diam.

Aplikasi web untuk mencari fasilitas kampus, mengajukan reservasi, melaporkan kerusakan, memproses antrean petugas, mengelola data master, dan membuat rekap. Satu sumber data agar ketersediaan reservasi ikut mempertimbangkan status fasilitas dan stok alat.

---

## Untuk AI Agent — baca dulu

**Sebelum menulis kode:**
1. Tanyakan domain pengguna (P1/P2/P3/P4) jika belum disebutkan
2. Baca migration, model, dan `app/Support/Status.php` yang sudah ada — jangan menebak skema
3. Kerjakan hanya file milik domain tersebut
4. Tentukan acceptance case dari PRD untuk fitur yang dikerjakan
5. Satu fitur per sesi

**Jangan pernah:**
- Menambah tabel, kolom, status, atau package tanpa alasan dan persetujuan tim
- Menulis kode untuk domain milik anggota lain
- Menyebar magic string — semua role, status, type, kategori, dan batas durasi ada di `Status.php`
- Memakai React, Vue, Inertia, Livewire, npm/build step, Breeze, Jetstream, localStorage, sessionStorage
- Membuat satu baris reservasi per slot 30 menit — satu rentang tetap satu baris
- Membuat paket tempat+alat otomatis — keduanya reservasi terpisah
- Mengandalkan tombol tersembunyi sebagai kontrol akses
- Mengambil `status`, `processed_by`, atau `cancelled_by` langsung dari request

**Selalu:**
- Validasi di server melalui Form Request; validasi client hanya membantu UX
- Periksa role, `account_status`, dan ownership
- Batasi kolom dari server untuk respons publik — bukan disembunyikan dengan CSS
- Bungkus aturan lintas baris dalam transaksi
- Setelah selesai, jelaskan file yang berubah, alur, query/transaction, aturan yang diterapkan, dan cara mengujinya

**Tanyakan hanya jika ada konflik nyata** yang mengubah hasil. Keputusan final di PRD tidak perlu dikonfirmasi ulang.

---

## 1. Stack

| Lapisan | Pilihan |
|---|---|
| Framework | Laravel (PHP 8.2+) |
| Database | MySQL/MariaDB, engine InnoDB |
| View | Blade + Bootstrap 5 CDN, UI bahasa Indonesia |
| Client | Vanilla JS + HTML5 |
| Auth | Session + `Hash::make`, tanpa Breeze/Jetstream |
| Otorisasi | Middleware role + policy/ownership |
| Validasi | Form Request + domain service |
| Upload | Storage facade — 1 foto, JPG/JPEG/PNG, maks 2 MB |
| Scheduler | Laravel Scheduler — auto-reject pending kedaluwarsa |
| Ekspor | CSV native + Maatwebsite Laravel Excel |
| Server lokal | Laragon/XAMPP, document root ke `public` |

`APP_TIMEZONE=Asia/Jakarta`. Semua perbandingan dan tampilan memakai WIB.

**Komponen yang disarankan:** `ReservationAvailabilityService` (overlap, stok, slot publik, durasi, rangkaian), `ReservationApprovalService` (transaksi, lock, recheck), `RecapService` (satu ruleset untuk halaman, CSV, XLSX), Console Command (auto-reject).

---

## 2. Pembagian tugas

| Kode | Domain | US | Tanggung jawab |
|---|---|---|---|
| **P1** | Account & Access + Master Data | auth, 13–15 | **Semua migration + seeder**, `Status.php`, `AuthController`, `Admin/AccountController`, middleware, model `User`, layout & navbar, kelola akun, kelola fakultas/gedung |
| **P2** | Facility & User Reservation | 1–5 | Model `Facility` & `Reservation`, `ReservationAvailabilityService`, `FacilityController`, `ReservationController`, halaman katalog & reservasi pengguna |
| **P3** | Issue & Maintenance | 6, 7, 11, 12 | Model `DamageReport`, `DamageReportController`, `Officer/ReportController`, upload foto, maintenance fasilitas, halaman laporan |
| **P4** | Reservation Ops & Reporting | 8–10, 16, 17 | `ReservationApprovalService`, dashboard petugas, `Admin/FacilityController`, `RecapService`, ekspor CSV/XLSX |

**Perubahan dari PRD bagian 16:** seluruh migration dipegang P1 (bukan tersebar), dan CRUD fakultas/gedung pindah dari P4 ke P1. Model tetap milik domain masing-masing — migration hanya struktur tabel.

**File bersama:**

| File | Pemilik |
|---|---|
| `routes/web.php` | P1 |
| `resources/views/layouts/app.blade.php` | P1 |
| `app/Support/Status.php` | P1 |
| Semua migration & seeder | P1 |
| Model & service `Facility`/`Reservation` | P2 (P3/P4 review integrasi) |

**Butuh kolom baru?** Lapor ke P1. Jangan mengedit migration yang sudah dijalankan orang lain.

---

## 3. Aturan bisnis kritis

Lengkapnya di PRD bagian 7. Ini yang paling sering keliru.

### Waktu dan durasi

- Jam penggunaan **07.00–20.00**, kelipatan 30 menit, `end_time > start_time`
- Mulai dan selesai **tanggal yang sama** — tidak ada reservasi lintas hari
- Maksimal **90 hari** ke depan, harus di masa depan
- **Tidak ada minimum lead time** — pukul 09.27 masih boleh memilih mulai 09.30
- Satu rentang multi-slot = **satu baris**, jumlah slot dihitung bukan disimpan

| Type | Maksimum | Slot |
|---|---|---|
| `ruang_kelas` | 180 menit | 6 |
| `aula` | 360 menit | 12 |
| `laboratorium` | 240 menit | 8 |
| `lapangan` | 120 menit | 4 |
| `alat` | 780 menit | 26 |

Reservasi bersebelahan untuk user+facility sama dihitung **kumulatif** terhadap batas durasi. Jeda memulai rangkaian baru. Rejected/cancelled tidak dihitung.

### Ketersediaan

Overlap = `start_baru < end_lama` AND `end_baru > start_lama`. Rentang 09.00–10.00 dan 10.00–11.00 **tidak** bertumpuk.

| Objek | Aturan |
|---|---|
| Tempat | Eksklusif. Tidak boleh ada approved overlap pada facility sama. Satu pengguna juga tidak boleh punya dua tempat approved yang overlap |
| Alat | Pooled stock. Tersedia = `stock_total − stock_unavailable − Σ quantity approved overlap` |
| Pending | **Tidak memblokir** slot/stok publik. Banyak pending boleh bersaing |
| Tempat + alat | Boleh waktu sama, disimpan terpisah, partial approval disengaja |
| Status fasilitas | Hanya `active` menerima pengajuan/approve |

### Approve wajib atomik

1. Buka transaksi
2. Lock `users.id` pemohon, lalu `facilities.id`, lalu `equipment_details` untuk alat — **urutan lock konsisten**
3. Baca ulang: reservasi target, status fasilitas, approved overlap, konflik tempat milik pemohon, stok alat
4. Jika aman → `approved` + isi `processed_by` dan `processed_at`. Jika tidak → jangan approve
5. Commit

Jangan mengandalkan pengecekan sebelum transaksi.

### Pembatalan dan kedaluwarsa

| Kondisi | Aturan |
|---|---|
| Pending oleh pengguna | Kapan saja selama `now < start_time` |
| Approved oleh pengguna | `now ≤ start_time − 2 jam`. Mulai 08.00 → batas 06.00 |
| Approved oleh petugas | Kapan saja untuk kondisi mendesak, `cancel_reason` wajib |
| Pending saat mulai | Scheduler ubah jadi `rejected`, `processed_by` NULL |
| Maintenance | Tidak mengubah reservasi otomatis — petugas membatalkan manual |

### Laporan kerusakan

- Kategori: `kerusakan_fisik`, `kelistrikan`, `kebersihan`, `perlengkapan`, `lainnya`
- Jika `lainnya` → `other_category` wajib (maks 100 karakter). Selain itu **harus NULL**
- Foto wajib satu, nama file dibuat acak server
- `resolution_note` wajib untuk `selesai`/`ditolak`
- **Tidak ada** `handled_by`/`handled_at`
- Status laporan dan status fasilitas **independen**

### Privasi

Respons publik tidak boleh memuat `user_id`, nama pemohon, email, atau `purpose`. Batasi kolom di query — bukan disembunyikan dengan CSS.

### Akun

- Registrasi publik **selalu** `role=pengguna` + `account_status=pending`. Input role dari client diabaikan
- Petugas tidak pernah registrasi mandiri
- Hanya `active` yang bisa login

---

## 4. Delapan tabel

```
users  faculties  buildings  facilities
                                 ├── room_details      (ruang_kelas, aula, laboratorium)
                                 └── equipment_details (alat)
reservations   damage_reports
```

**Urutan migration:** `users` → `faculties` → `buildings` → `facilities` → `room_details` & `equipment_details` → `reservations` → `damage_reports`

**Invariant penting:**
- `faculty_id` NULL = universitas (bukan data kosong). `building_id` NULL = luar gedung
- Jika `building_id` terisi, `faculty_id` fasilitas harus sama dengan `faculty_id` gedung
- `capacity` wajib >0 untuk tempat, NULL untuk alat
- `quantity` selalu 1 untuk tempat, ≥1 untuk alat
- Fasilitas/akun bersejarah **tidak dihapus** — pakai status `inactive`
- `type` tidak boleh diubah setelah ada reservasi/laporan

Detail kolom, constraint, dan index ada di PRD bagian 9.

---

## 5. Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Sesuaikan `.env`:
```
DB_CONNECTION=mysql
DB_DATABASE=ppk_reservasi
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
APP_TIMEZONE=Asia/Jakarta
```

Buat database di phpMyAdmin, lalu:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka `http://localhost:8000`. Nyalakan MySQL lewat control panel — Apache tidak perlu.

Untuk menguji auto-reject: `php artisan schedule:work` di terminal terpisah.

---

## 6. Alur kerja git

```bash
git checkout main && git pull
git checkout -b p2/availability-service
# ... kerjakan ...
git add . && git commit -m "Tambah perhitungan ketersediaan slot"
git push -u origin p2/availability-service
# pull request → review → merge
```

Setelah pull, jalankan `php artisan migrate` jika P1 menambah migration.

- Commit wajib dari semua anggota, satu commit satu perubahan logis
- Jangan commit `.env`, vendor, atau `storage/app/public/reports`
- Setiap integrasi lintas domain sebutkan dampak route, model, migration, dan test

---

## 7. Cara memakai AI

**Prompt yang baik:**
```
Baca README.md dan PRD bagian 7.2 dulu. Saya P2. Buat
ReservationAvailabilityService untuk menghitung slot tersedia,
termasuk cabang pooled stock untuk alat. Jangan sentuh controller.
```

**Prompt yang buruk:**
```
Buatkan sistem reservasi lengkap
```

**Aturan:**
- Baca kode sebelum menerima — jangan setuju tanpa membaca
- Kalau tidak paham, minta AI menjelaskan sampai paham
- Jangan minta AI mengerjakan domain orang lain
- Commit atas nama sendiri; kode hasil AI tetap tanggung jawab kamu

**Kenapa penting:** presentasi UTS punya 10–15 menit tanya jawab. Pertanyaan yang paling mungkin:

1. Kenapa validasi harus di server?
2. Bagaimana sistem mencegah double booking saat dua petugas approve bersamaan?
3. Bagaimana sistem menyembunyikan detail pemohon dari pengunjung?
4. Bagaimana sistem mencegah orang mendaftar sebagai petugas?
5. Kenapa pending tidak memblokir slot?

Setiap anggota harus bisa menjelaskan bagiannya sendiri.

---

## 8. Checklist sebelum push

- [ ] `php artisan serve` jalan tanpa error
- [ ] Diuji dengan akun role yang sesuai
- [ ] Akses lewat URL langsung dengan role salah → ditolak
- [ ] Ganti ID di URL ke milik orang lain → 403/404
- [ ] Kirim data tidak valid ke server → ditolak
- [ ] Untuk fitur approve: diuji dua request bersamaan
- [ ] Tidak ada `dd()`, `dump()`, `console.log` tertinggal
- [ ] `.env` tidak ter-commit
- [ ] Screenshot fitur sudah diambil

---

## 9. Yang masih perlu diputuskan tim

Angkat ke grup, jangan putuskan sendiri.

| Hal | Catatan |
|---|---|
| **Tier inti vs peningkatan** | PRD menandai semuanya wajib. Dengan sisa waktu terbatas, tim perlu menyepakati apa yang boleh dilepas tanpa kehilangan user story. Kandidat peningkatan: pooled stock alat, normalisasi fakultas/gedung, XLSX, automated test |
| **Penamaan status campur** | Reservasi/akun/fasilitas pakai Inggris (`active`, `approved`, `cancelled`), laporan pakai Indonesia (`baru`, `diproses`, `selesai`). Samakan untuk mengurangi salah ketik |
| **Scheduler butuh cron** | Dosen tidak akan menjalankan `schedule:work` saat menguji. Pertimbangkan fallback sweep sebagai mekanisme utama |
| **Durasi alat 780 menit** | PRD bagian 7.1 — pastikan ini disengaja, bukan salah ketik |
| **CSV dan XLSX sekaligus** | US17 menulis "CSV/Excel/PDF" yang berarti pilih salah satu. XLSX adalah pekerjaan opsional |

Perubahan pada tabel, status, jam operasional, batas durasi, formula rekap, atau lifecycle **wajib menaikkan versi PRD** dengan alasan, dampak migration, dampak pengujian, dan siapa yang menyetujui.

### Riwayat perubahan

| Tanggal | Yang berubah | Diputuskan oleh |
|---|---|---|
| 17 Sep 2026 | PRD v1.0 — baseline final | Tim |
| | Semua migration dipegang P1; CRUD fakultas/gedung pindah ke P1 | |

---

## 10. Akun testing

Dibuat oleh seeder.

| Role | Email | Password |
|---|---|---|
| Admin | _(isi)_ | |
| Petugas | | |
| Pengguna (active) | | |
| Pengguna (pending) | | |

Akun pending dipakai menguji bahwa login menolaknya.

---

## 11. Kalau error

| Gejala | Penyebab |
|---|---|
| `could not find driver` | `DB_CONNECTION` belum `mysql` → perbaiki `.env`, `php artisan config:clear` |
| Halaman error padahal cuma view | MySQL belum nyala, atau `SESSION_DRIVER` belum `file` |
| 404 padahal file Blade ada | Route belum didaftarkan. URL ditentukan `routes/web.php`, bukan nama file |
| Foto laporan tidak muncul | `php artisan storage:link` belum dijalankan |
| Migration error setelah pull | Jalankan `php artisan migrate` |
| Pending tidak auto-reject | Scheduler belum jalan → `php artisan schedule:work` |
| Waktu meleset 7 jam | `APP_TIMEZONE` belum `Asia/Jakarta` |

---

## 12. Pengumpulan

Satu file Word: nama dan NIM anggota, pembagian tugas, link Google Drive (source code + `.sql` + pendukung), informasi setting, informasi login tiap aktor, screenshot antarmuka dan penjelasan tiap fitur.

Laravel memakai migration, tapi pengumpulan tetap meminta file `.sql` — export lewat phpMyAdmin sebelum submit.

**Uji seluruh instruksi setup di laptop yang belum pernah dipakai mengerjakan proyek ini.**

Presentasi UTS: 10 menit presentasi + 10–15 menit tanya jawab. Cakupan: latar belakang, fitur utama, demo sistem, kendala yang dihadapi. PRD bagian 17 (Kelemahan dan ruang berkembang) adalah bahan yang tepat untuk bagian kendala.

---

## 13. Dokumen terkait

| Dokumen | Isi |
|---|---|
| PRD v1.0 | Sumber kebutuhan — aturan bisnis, data dictionary, alur, acceptance |
| Project PPK 2026.pdf | Ketentuan tugas dari dosen — batas terluar |
| `README.md` | Tata cara kerja harian dan kontrak AI (file ini) |
| `composer.json` / `composer.lock` | Sumber versi package yang benar |
