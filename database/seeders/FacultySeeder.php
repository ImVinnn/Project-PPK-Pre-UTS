<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculties = [
            ['code' => 'FTI', 'name' => 'Fakultas Teknologi Informasi'],
            ['code' => 'FEB', 'name' => 'Fakultas Ekonomi dan Bisnis'],
            ['code' => 'FIK', 'name' => 'Fakultas Ilmu Komunikasi'],
        ];

        foreach ($faculties as $faculty) {
            Faculty::query()->updateOrCreate(
                ['code' => $faculty['code']],
                ['name' => $faculty['name']],
            );
        }
    }
}
