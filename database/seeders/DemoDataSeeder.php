<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\DamageReport;
use App\Models\Facility;
use App\Models\Faculty;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Data presentasi tambahan. Tidak menghapus atau mengubah baris yang sudah ada.
 * Identitas [DEMO] menjaga agar seeder ini aman dijalankan lebih dari sekali.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $ari = $this->user('demo.ari@kampus.test', 'Ari Pratama (Demo)', Status::ROLE_USER, Status::ACCOUNT_ACTIVE);
        $nisa = $this->user('demo.nisa@kampus.test', 'Nisa Rahma (Demo)', Status::ROLE_USER, Status::ACCOUNT_ACTIVE);
        $officer = $this->user('demo.petugas@kampus.test', 'Petugas Tambahan (Demo)', Status::ROLE_OFFICER, Status::ACCOUNT_ACTIVE);
        $this->user('demo.ditolak@kampus.test', 'Akun Ditolak (Demo)', Status::ROLE_USER, Status::ACCOUNT_REJECTED);
        $this->user('demo.nonaktif@kampus.test', 'Akun Nonaktif (Demo)', Status::ROLE_USER, Status::ACCOUNT_INACTIVE);

        $faculty = Faculty::query()->firstOrCreate(
            ['code' => 'FIS'],
            ['name' => 'Fakultas Ilmu Sosial']
        );
        $building = Building::query()->firstOrCreate(
            ['code' => 'FIS-A', 'faculty_id' => $faculty->id],
            ['name' => 'Gedung A FIS']
        );

        $discussionRoom = Facility::query()->firstOrCreate(
            ['name' => 'Ruang Diskusi FIS D201 [DEMO]'],
            [
                'faculty_id' => $faculty->id,
                'building_id' => $building->id,
                'type' => Status::FACILITY_CLASSROOM,
                'capacity' => 24,
                'location_detail' => 'Gedung A FIS Lantai 2',
                'description' => 'Ruang diskusi untuk simulasi reservasi kelompok mahasiswa.',
                'status' => Status::FACILITY_ACTIVE,
            ]
        );
        $discussionRoom->roomDetail()->firstOrCreate([], ['room_number' => 'D201', 'floor' => 2]);

        $retiredRoom = Facility::query()->firstOrCreate(
            ['name' => 'Ruang Seminar Lama FIS [DEMO]'],
            [
                'faculty_id' => $faculty->id,
                'building_id' => $building->id,
                'type' => Status::FACILITY_HALL,
                'capacity' => 80,
                'location_detail' => 'Gedung A FIS Lantai 1',
                'description' => 'Contoh fasilitas nonaktif yang hanya dapat diaktifkan kembali oleh admin.',
                'status' => Status::FACILITY_INACTIVE,
            ]
        );
        $retiredRoom->roomDetail()->firstOrCreate([], ['room_number' => 'S101', 'floor' => 1]);

        $microphone = Facility::query()->firstOrCreate(
            ['name' => 'Mikrofon Nirkabel FIS [DEMO]'],
            [
                'faculty_id' => $faculty->id,
                'building_id' => $building->id,
                'type' => Status::FACILITY_EQUIPMENT,
                'capacity' => null,
                'location_detail' => 'Ruang Perlengkapan Gedung A FIS',
                'description' => 'Perangkat audio contoh untuk demonstrasi reservasi alat dan stok.',
                'status' => Status::FACILITY_ACTIVE,
            ]
        );
        $microphone->equipmentDetail()->firstOrCreate([], [
            'brand' => 'SORA Demo',
            'model' => 'Wireless M-1',
            'stock_total' => 6,
            'stock_unavailable' => 1,
        ]);

        $a101 = Facility::query()->where('name', 'Ruang Kelas A101')->sole();
        $a102 = Facility::query()->where('name', 'Ruang Kelas A102')->sole();
        $aula = Facility::query()->where('name', 'Aula Utama Rektorat')->sole();
        $lab = Facility::query()->where('name', 'Lab Jaringan Komputer')->sole();
        $camera = Facility::query()->where('name', 'Kamera Mirrorless Liputan')->sole();

        $day = CarbonImmutable::today(config('app.timezone'))->addDays(7);
        $this->reservation($ari, $discussionRoom, '[DEMO] Diskusi persiapan seminar', $day->setTime(9, 0), $day->setTime(10, 30), Status::RESERVATION_PENDING);
        $this->reservation($nisa, $aula, '[DEMO] Seminar organisasi mahasiswa', $day->addDays(2)->setTime(9, 0), $day->addDays(2)->setTime(12, 0), Status::RESERVATION_APPROVED, $officer);
        $this->reservation($ari, $a101, '[DEMO] Pengajuan ruang yang ditolak', $day->addDays(3)->setTime(13, 0), $day->addDays(3)->setTime(14, 0), Status::RESERVATION_REJECTED, $officer);
        $this->reservation($nisa, $a102, '[DEMO] Kegiatan yang dibatalkan pengguna', $day->addDays(4)->setTime(10, 0), $day->addDays(4)->setTime(11, 0), Status::RESERVATION_CANCELLED, $officer, $nisa);
        $this->reservation($ari, $camera, '[DEMO] Peminjaman kamera untuk dokumentasi', $day->addDays(5)->setTime(8, 0), $day->addDays(5)->setTime(17, 0), Status::RESERVATION_APPROVED, $officer, null, 2);
        $this->reservation($nisa, $microphone, '[DEMO] Peminjaman mikrofon untuk diskusi', $day->addDays(6)->setTime(9, 0), $day->addDays(6)->setTime(12, 0), Status::RESERVATION_PENDING);

        $this->report($ari, $discussionRoom, 'lampu-ruang', Status::REPORT_ELECTRICAL, Status::REPORT_NEW, 'Lampu di sisi kiri ruang berkedip ketika dinyalakan.');
        $this->report($nisa, $lab, 'kabel-lab', Status::REPORT_PHYSICAL_DAMAGE, Status::REPORT_IN_PROGRESS, 'Pelindung kabel jaringan di dekat meja instruktur terlepas.', 'Pemeriksaan kabel dan pengamanan area sedang berlangsung.');
        $this->report($ari, $aula, 'kursi-aula', Status::REPORT_EQUIPMENT, Status::REPORT_COMPLETED, 'Salah satu kursi aula memiliki sandaran yang longgar.', 'Sandaran kursi telah diperbaiki dan diuji kembali.');
        $this->report($nisa, $a101, 'kelas-bersih', Status::REPORT_CLEANLINESS, Status::REPORT_REJECTED, 'Ada debu pada meja setelah kegiatan berlangsung.', 'Laporan ditolak karena kondisi sudah dibersihkan sebelum pemeriksaan.');
        $this->report($ari, $microphone, 'mikrofon-lainnya', Status::REPORT_OTHER, Status::REPORT_NEW, 'Label inventaris pada mikrofon mulai pudar.', null, 'Label inventaris');
    }

    private function user(string $email, string $name, string $role, string $status): User
    {
        return User::query()->firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make(UserSeeder::TEST_PASSWORD),
            'role' => $role,
            'account_status' => $status,
        ]);
    }

    private function reservation(
        User $user,
        Facility $facility,
        string $purpose,
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $status,
        ?User $officer = null,
        ?User $canceller = null,
        int $quantity = 1
    ): void {
        Reservation::query()->firstOrCreate(
            ['user_id' => $user->id, 'purpose' => $purpose],
            [
                'facility_id' => $facility->id,
                'quantity' => $quantity,
                'start_time' => $start,
                'end_time' => $end,
                'status' => $status,
                'processed_by' => $officer?->id,
                'processed_at' => $officer ? now() : null,
                'rejection_reason' => $status === Status::RESERVATION_REJECTED ? 'Waktu tidak sesuai dengan agenda fasilitas.' : null,
                'cancelled_by' => $canceller?->id,
                'cancelled_at' => $canceller ? now() : null,
                'cancel_reason' => $canceller ? 'Jadwal kegiatan berubah.' : null,
            ]
        );
    }

    private function report(
        User $user,
        Facility $facility,
        string $key,
        string $category,
        string $status,
        string $description,
        ?string $resolutionNote = null,
        ?string $otherCategory = null
    ): void {
        $description = '[DEMO] '.$description;
        $path = 'reports/demo-seeder-'.$key.'.png';
        $this->ensurePhoto($path, strtoupper(str_replace('-', ' ', $key)));

        if (DamageReport::query()->where('user_id', $user->id)->where('description', $description)->exists()) {
            return;
        }

        $report = new DamageReport([
            'facility_id' => $facility->id,
            'category' => $category,
            'other_category' => $otherCategory,
            'description' => $description,
            'photo_path' => $path,
        ]);
        $report->user_id = $user->id;
        $report->status = $status;
        $report->resolution_note = $resolutionNote;
        $report->save();
    }

    private function ensurePhoto(string $path, string $label): void
    {
        // Jangan menghasilkan berkas sungguhan saat rangkaian tes membuat database sementara.
        if (app()->environment('testing')) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            return;
        }

        if (function_exists('imagecreatetruecolor')) {
            $image = imagecreatetruecolor(640, 360);
            $background = imagecolorallocate($image, 233, 238, 243);
            $accent = imagecolorallocate($image, 39, 83, 105);
            $text = imagecolorallocate($image, 25, 39, 50);
            imagefilledrectangle($image, 0, 0, 640, 360, $background);
            imagefilledrectangle($image, 0, 0, 640, 72, $accent);
            imagestring($image, 5, 28, 27, 'SORA - ILUSTRASI DATA DEMO', $background);
            imagestring($image, 5, 28, 155, $label, $text);
            imagestring($image, 3, 28, 305, 'Bukan foto kejadian nyata', $text);
            ob_start();
            imagepng($image);
            $contents = ob_get_clean();
            imagedestroy($image);
            $disk->put($path, $contents);

            return;
        }

        $fallback = public_path('images/brand/sora-icon.png');
        if (! is_file($fallback)) {
            throw new RuntimeException('Foto demo tidak dapat dibuat: ekstensi GD dan ikon SORA tidak ditemukan.');
        }
        $disk->put($path, file_get_contents($fallback));
    }
}
