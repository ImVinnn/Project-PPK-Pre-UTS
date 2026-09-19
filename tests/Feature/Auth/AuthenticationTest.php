<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_open_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    #[DataProvider('roleDashboardProvider')]
    public function test_active_user_is_redirected_to_dashboard_for_their_role(
        string $role,
        string $routeName,
    ): void {
        $user = User::factory()->create([
            'email' => $role.'@example.com',
            'password' => 'rahasia123',
            'role' => $role,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => strtoupper($user->email),
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route($routeName));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function roleDashboardProvider(): array
    {
        return [
            'pengguna' => [Status::ROLE_USER, 'dashboard'],
            'petugas' => [Status::ROLE_OFFICER, 'officer.dashboard'],
            'admin' => [Status::ROLE_ADMIN, 'admin.dashboard'],
        ];
    }

    public function test_pending_account_cannot_log_in(): void
    {
        $user = User::factory()->pending()->create([
            'email' => 'pending@example.com',
            'password' => 'rahasia123',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_cannot_log_in_with_invalid_password(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-yang-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
