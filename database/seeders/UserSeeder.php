<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public const string TEST_PASSWORD = 'Password123!';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Administrator',
                'email' => 'admin@kampus.test',
                'role' => Status::ROLE_ADMIN,
                'account_status' => Status::ACCOUNT_ACTIVE,
            ],
            [
                'name' => 'Petugas Kampus',
                'email' => 'petugas@kampus.test',
                'role' => Status::ROLE_OFFICER,
                'account_status' => Status::ACCOUNT_ACTIVE,
            ],
            [
                'name' => 'Pengguna Aktif',
                'email' => 'pengguna@kampus.test',
                'role' => Status::ROLE_USER,
                'account_status' => Status::ACCOUNT_ACTIVE,
            ],
            [
                'name' => 'Pengguna Pending',
                'email' => 'pending@kampus.test',
                'role' => Status::ROLE_USER,
                'account_status' => Status::ACCOUNT_PENDING,
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    'password' => Hash::make(self::TEST_PASSWORD),
                ],
            );
        }
    }
}
