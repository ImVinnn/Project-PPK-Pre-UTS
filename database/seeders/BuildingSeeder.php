<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Faculty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ftiId = Faculty::query()->where('code', 'FTI')->sole()->getKey();
        $febId = Faculty::query()->where('code', 'FEB')->sole()->getKey();
        $fikId = Faculty::query()->where('code', 'FIK')->sole()->getKey();

        $buildings = [
            ['faculty_id' => null, 'code' => 'RKT', 'name' => 'Gedung Rektorat'],
            ['faculty_id' => null, 'code' => 'GKU', 'name' => 'Gedung Kuliah Umum'],
            [
                'faculty_id' => $ftiId,
                'code' => 'FTI-A',
                'name' => 'Gedung A FTI',
            ],
            [
                'faculty_id' => $febId,
                'code' => 'FEB-A',
                'name' => 'Gedung A FEB',
            ],
            [
                'faculty_id' => $fikId,
                'code' => 'FIK-A',
                'name' => 'Gedung A FIK',
            ],
        ];

        foreach ($buildings as $building) {
            Building::query()->updateOrCreate(
                [
                    'faculty_id' => $building['faculty_id'],
                    'code' => $building['code'],
                ],
                ['name' => $building['name']],
            );
        }
    }
}
