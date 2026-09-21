<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Facility;
use App\Models\Faculty;
use App\Support\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fti = Faculty::query()->where('code', 'FTI')->first();
        $fik = Faculty::query()->where('code', 'FIK')->first();

        $buildingFtiA = Building::query()->where('code', 'FTI-A')->first();
        $buildingFikA = Building::query()->where('code', 'FIK-A')->first();
        $buildingRkt = Building::query()->where('code', 'RKT')->first();
        $buildingGku = Building::query()->where('code', 'GKU')->first();

        $facilities = [
            [
                'facility' => [
                    'faculty_id' => $fti?->id,
                    'building_id' => $buildingFtiA?->id,
                    'name' => 'Ruang Kelas A101',
                    'type' => Status::FACILITY_CLASSROOM,
                    'capacity' => 40,
                    'location_detail' => 'Gedung A FTI Lantai 1',
                    'description' => 'Ruang kelas dengan papan tulis dan pendingin ruangan.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
                'room' => [
                    'room_number' => 'A101',
                    'floor' => 1,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => $fti?->id,
                    'building_id' => $buildingFtiA?->id,
                    'name' => 'Ruang Kelas A102',
                    'type' => Status::FACILITY_CLASSROOM,
                    'capacity' => 40,
                    'location_detail' => 'Gedung A FTI Lantai 1',
                    'description' => 'Ruang kelas untuk perkuliahan teori dan diskusi kelompok.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
                'room' => [
                    'room_number' => 'A102',
                    'floor' => 1,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => $fti?->id,
                    'building_id' => $buildingFtiA?->id,
                    'name' => 'Lab Komputer 1',
                    'type' => Status::FACILITY_LABORATORY,
                    'capacity' => 30,
                    'location_detail' => 'Gedung A FTI Lantai 2',
                    'description' => 'Laboratorium komputasi dengan 30 PC terkoneksi internet.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
                'room' => [
                    'room_number' => 'LAB-01',
                    'floor' => 2,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => $fti?->id,
                    'building_id' => $buildingFtiA?->id,
                    'name' => 'Lab Jaringan Komputer',
                    'type' => Status::FACILITY_LABORATORY,
                    'capacity' => 25,
                    'location_detail' => 'Gedung A FTI Lantai 3',
                    'description' => 'Laboratorium riset infrastruktur dan jaringan (sedang pemeliharaan kabel fiber).',
                    'status' => Status::FACILITY_MAINTENANCE,
                ],
                'room' => [
                    'room_number' => 'LAB-02',
                    'floor' => 3,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => null,
                    'building_id' => $buildingRkt?->id,
                    'name' => 'Aula Utama Rektorat',
                    'type' => Status::FACILITY_HALL,
                    'capacity' => 250,
                    'location_detail' => 'Gedung Rektorat Lantai 1',
                    'description' => 'Aula besar untuk seminar kampus, lokakarya, dan wisuda.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
                'room' => [
                    'room_number' => 'AULA-01',
                    'floor' => 1,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => null,
                    'building_id' => $buildingGku?->id,
                    'name' => 'Aula Barat GKU',
                    'type' => Status::FACILITY_HALL,
                    'capacity' => 150,
                    'location_detail' => 'Gedung Kuliah Umum Lantai 1',
                    'description' => 'Aula barat kapasitas menengah untuk kegiatan kemahasiswaan.',
                    'status' => Status::FACILITY_MAINTENANCE,
                ],
                'room' => [
                    'room_number' => 'AULA-B',
                    'floor' => 1,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => null,
                    'building_id' => null,
                    'name' => 'Lapangan Futsal',
                    'type' => Status::FACILITY_FIELD,
                    'capacity' => 30,
                    'location_detail' => 'Area Olahraga Barat Kampus',
                    'description' => 'Lapangan futsal outdoor beralaskan rumput sintetis standar.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => null,
                    'building_id' => null,
                    'name' => 'Lapangan Basket',
                    'type' => Status::FACILITY_FIELD,
                    'capacity' => 50,
                    'location_detail' => 'Area Olahraga Timur Kampus',
                    'description' => 'Lapangan basket outdoor dengan tribun penonton kecil.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => $fti?->id,
                    'building_id' => $buildingFtiA?->id,
                    'name' => 'Proyektor Portable Epson',
                    'type' => Status::FACILITY_EQUIPMENT,
                    'capacity' => null,
                    'location_detail' => 'Ruang Perlengkapan FTI Lantai 1',
                    'description' => 'Proyektor HDMI/VGA untuk presentasi luar kelas.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
                'equipment' => [
                    'brand' => 'Epson',
                    'model' => 'EB-X500',
                    'stock_total' => 10,
                    'stock_unavailable' => 2,
                ],
            ],
            [
                'facility' => [
                    'faculty_id' => $fik?->id,
                    'building_id' => $buildingFikA?->id,
                    'name' => 'Kamera Mirrorless Liputan',
                    'type' => Status::FACILITY_EQUIPMENT,
                    'capacity' => null,
                    'location_detail' => 'Studio FIK Lantai 2',
                    'description' => 'Kamera perekaman video dan dokumentasi acara resmi.',
                    'status' => Status::FACILITY_ACTIVE,
                ],
                'equipment' => [
                    'brand' => 'Sony',
                    'model' => 'Alpha 7 IV',
                    'stock_total' => 5,
                    'stock_unavailable' => 0,
                ],
            ],
        ];

        foreach ($facilities as $item) {
            $facility = Facility::query()->updateOrCreate(
                ['name' => $item['facility']['name']],
                $item['facility']
            );

            if (isset($item['room'])) {
                $facility->roomDetail()->updateOrCreate([], $item['room']);
            }

            if (isset($item['equipment'])) {
                $facility->equipmentDetail()->updateOrCreate([], $item['equipment']);
            }
        }
    }
}
