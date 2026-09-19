<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login_from_protected_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('officer.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_regular_user_can_only_access_regular_user_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('officer.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_officer_can_only_access_officer_dashboard(): void
    {
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->get(route('officer.dashboard'))->assertOk();
        $this->actingAs($officer)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($officer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_only_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('officer.dashboard'))->assertForbidden();
    }

    public function test_inactive_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
