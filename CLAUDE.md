# Sistem Reservasi & Pelaporan Fasilitas Kampus

Aplikasi web untuk mengelola penggunaan fasilitas kampus (ruang kelas, aula,
laboratorium, alat, lapangan). Pengguna mengajukan reservasi dan melaporkan
kerusakan; petugas dan admin memproses keduanya dalam satu sistem.

Project mata kuliah PPK 2026, dikerjakan 4 orang, deadline 11 Oktober 2026.

## Stack (sudah final, jangan diubah)

- Laravel + PHP 8.2+, MySQL, Eloquent, Migration
- Blade + Bootstrap 5 via CDN (tanpa npm, tanpa build step)
- Auth: session + `Hash::make()` bawaan Laravel
- Guard role: Middleware
- Validasi server: Form Request
- Validasi client: vanilla JS di `public/js` + atribut HTML5
- Upload foto: Storage facade
- Ekspor rekap: CSV via Controller

Server-side rendering. Tidak pakai React, Vue, Inertia, atau Livewire.

## Aktor & role

| Aktor | Role di DB |
|---|---|
| Pengunjung | tanpa login |
| Pengguna (mahasiswa/dosen/staf) | `pengguna` |
| Petugas | `petugas` |
| Admin | `admin` |

## Aturan wajib

**Validasi selalu di dua sisi.** Setiap aturan di `public/js` wajib punya
kembarannya di Form Request. Validasi client hanya kenyamanan; server yang
menegakkan.

**Reservasi:** jam operasional 07.00–20.00, slot tetap 30 menit (26 slot/hari).
`start_time` dan `end_time` wajib kelipatan 30 menit, dalam rentang operasional,
dan `end_time > start_time`. Validasi ini WAJIB di server.

**Deteksi bentrok dijalankan saat APPROVE, bukan saat pengajuan.** Dua pengguna
boleh mengajukan slot sama; petugas yang memilih. Kondisi bentrok:
`facility_id` sama AND status approved AND `start_baru < end_lama` AND
`end_baru > start_lama`.

**Privasi:** pengunjung hanya boleh melihat status tersedia/tidak tersedia.
Nama pemohon dan tujuan penggunaan TIDAK BOLEH ikut terkirim ke browser —
jangan disembunyikan dengan CSS, tapi jangan diambil sejak awal di query.
Pemilik reservasi boleh melihat detail lengkap miliknya sendiri.

**Registrasi:** form registrasi TIDAK BOLEH punya dropdown role. Role
di-hardcode `pengguna` di controller, status `pending`. Petugas tidak pernah
registrasi mandiri — hanya didaftarkan admin.

**Cek kepemilikan.** Setiap aksi terhadap satu baris data harus memeriksa
pemiliknya, bukan hanya role. Pengguna hanya boleh membatalkan reservasi
miliknya sendiri.

**Pembatalan oleh petugas** wajib menyimpan alasan.

**Upload foto:** verifikasi tipe dan ukuran di server, simpan dengan nama acak.
Jangan pakai nama file asli dari client.

## Status

| Entitas | Nilai |
|---|---|
| Reservasi | `pending`, `approved`, `rejected`, `cancelled_user`, `cancelled_officer` |
| Laporan | `baru`, `diproses`, `selesai`, `ditolak` |
| Fasilitas | `aktif`, `perbaikan`, `nonaktif` |
| Akun | `pending`, `aktif`, `ditolak` |

Semua status disimpan sebagai konstanta di `app/Support/Status.php`.
Jangan pernah mengetik string status langsung di controller atau Blade.

## Konvensi

- Nama tabel dan kolom: bahasa Inggris, snake_case
- Teks antarmuka: bahasa Indonesia
- Waktu reservasi: tipe `DATETIME`, jangan string
- Satu Controller per domain, dipisah namespace `Officer/` dan `Admin/`

## Jangan

- Jangan pakai Breeze/Jetstream — auth bawaannya mengaktifkan akun langsung,
  padahal butuh verifikasi admin
- Jangan pasang `Login` sebagai `<<include>>` di semua use case
- Jangan commit `.env` atau folder upload
