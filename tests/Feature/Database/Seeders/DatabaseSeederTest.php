<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Faculty;
use App\Models\User;
use App\Support\Status;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_required_testing_accounts_and_master_data_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $expectedAccounts = [
            'admin@kampus.test' => [Status::ROLE_ADMIN, Status::ACCOUNT_ACTIVE],
            'petugas@kampus.test' => [Status::ROLE_OFFICER, Status::ACCOUNT_ACTIVE],
            'pengguna@kampus.test' => [Status::ROLE_USER, Status::ACCOUNT_ACTIVE],
            'pending@kampus.test' => [Status::ROLE_USER, Status::ACCOUNT_PENDING],
        ];

        $this->assertSame(
            count($expectedAccounts),
            User::query()->whereIn('email', array_keys($expectedAccounts))->count(),
        );

        foreach ($expectedAccounts as $email => [$role, $accountStatus]) {
            $account = User::query()->where('email', $email)->sole();

            $this->assertSame($role, $account->role);
            $this->assertSame($accountStatus, $account->account_status);
            $this->assertTrue(Hash::check(UserSeeder::TEST_PASSWORD, $account->password));
        }

        $this->assertDatabaseHas('faculties', [
            'code' => 'FTI',
            'name' => 'Fakultas Teknologi Informasi',
        ]);
        $this->assertDatabaseHas('buildings', [
            'faculty_id' => null,
            'code' => 'GKU',
            'name' => 'Gedung Kuliah Umum',
        ]);

        $faculty = Faculty::query()->where('code', 'FTI')->sole();

        $this->assertDatabaseHas('buildings', [
            'faculty_id' => $faculty->id,
            'code' => 'FTI-A',
            'name' => 'Gedung A FTI',
        ]);
    }
}
