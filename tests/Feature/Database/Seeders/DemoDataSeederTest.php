<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\DamageReport;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_data_covers_domain_tables_and_does_not_duplicate_on_second_run(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $before = [];
        foreach (['users', 'faculties', 'buildings', 'facilities', 'room_details', 'equipment_details', 'reservations', 'damage_reports'] as $table) {
            $before[$table] = DB::table($table)->count();
            $this->assertGreaterThan(0, $before[$table], $table);
        }

        $this->seed(DemoDataSeeder::class);

        foreach ($before as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), $table);
        }

        $this->assertDatabaseHas('users', ['email' => 'admin@kampus.test', 'role' => Status::ROLE_ADMIN]);
        $this->assertDatabaseHas('users', ['email' => 'demo.ari@kampus.test', 'role' => Status::ROLE_USER]);
        $this->assertDatabaseHas('faculties', ['code' => 'FTI']);
        $this->assertDatabaseHas('faculties', ['code' => 'FIS']);
        $this->assertDatabaseHas('buildings', ['code' => 'FIS-A']);
        $this->assertDatabaseHas('facilities', ['name' => 'Ruang Diskusi FIS D201 [DEMO]']);
        $this->assertDatabaseHas('facilities', ['name' => 'Mikrofon Nirkabel FIS [DEMO]']);

        foreach (Status::RESERVATION_STATUSES as $status) {
            $this->assertTrue(Reservation::query()->where('status', $status)->exists(), $status);
        }
        foreach (Status::REPORT_STATUSES as $status) {
            $this->assertTrue(DamageReport::query()->where('status', $status)->exists(), $status);
        }
        foreach (Status::REPORT_CATEGORIES as $category) {
            $this->assertTrue(DamageReport::query()->where('category', $category)->exists(), $category);
        }

        $demoUser = User::query()->where('email', 'demo.ari@kampus.test')->sole();
        $demoUser->update(['name' => 'Nama Disunting Saat Demo']);
        $this->seed(DemoDataSeeder::class);
        $this->assertSame('Nama Disunting Saat Demo', $demoUser->fresh()->name);
    }
}
