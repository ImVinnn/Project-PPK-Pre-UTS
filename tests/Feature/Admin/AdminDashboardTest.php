<?php

namespace Tests\Feature\Admin;

use App\Models\Facility;
use App\Models\User;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 08:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function makeFacility(string $status): Facility
    {
        return Facility::query()->create([
            'name' => 'Fasilitas '.fake()->unique()->word(),
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 40,
            'location_detail' => 'Lantai 1',
            'status' => $status,
        ]);
    }

    public function test_guest_officer_and_user_are_rejected(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->officer()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'rahasia123']);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_card_counts_match_data(): void
    {
        User::factory()->pending()->count(3)->create();
        User::factory()->rejected()->create();

        foreach (range(1, 4) as $i) {
            $this->makeFacility(Status::FACILITY_ACTIVE);
        }
        foreach (range(1, 2) as $i) {
            $this->makeFacility(Status::FACILITY_MAINTENANCE);
        }
        $this->makeFacility(Status::FACILITY_INACTIVE);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('pendingAccountsCount', 3)
            ->assertViewHas('facilityCounts', fn ($counts) => $counts[Status::FACILITY_ACTIVE] == 4
                && $counts[Status::FACILITY_MAINTENANCE] == 2
                && $counts[Status::FACILITY_INACTIVE] == 1)
            ->assertSee('Dalam Perbaikan')
            ->assertSee(route('admin.facilities.index', ['status' => Status::FACILITY_MAINTENANCE]), false)
            ->assertSee(route('admin.recaps.index'), false);
    }

    public function test_pending_list_is_oldest_first_and_limited_to_five(): void
    {
        Carbon::setLocale('id');

        $names = [];
        foreach (range(1, 7) as $day) {
            $names[$day] = User::factory()->pending()->create([
                'name' => "Pendaftar Hari {$day}",
                'created_at' => Carbon::parse("2026-08-{$day} 10:00:00"),
            ])->name;
        }
        // Dibuat paling akhir tapi mendaftar paling lama — harus tetap di urutan pertama.
        $oldest = User::factory()->pending()->create([
            'name' => 'Pendaftar Paling Lama',
            'created_at' => Carbon::parse('2026-07-15 09:00:00'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertViewHas('pendingAccounts', fn ($accounts) => $accounts->count() === 5
                && $accounts->first()->is($oldest))
            ->assertSeeInOrder([$oldest->name, $names[1], $names[2], $names[3], $names[4]])
            ->assertDontSee($names[5])
            ->assertSee('15 Juli 2026');
    }

    public function test_empty_dashboard_renders_without_error(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada akun yang menunggu verifikasi.')
            ->assertViewHas('pendingAccountsCount', 0);
    }
}
