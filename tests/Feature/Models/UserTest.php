<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_factory_state_creates_an_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isActive());
        $this->assertTrue($admin->hasRole(Status::ROLE_ADMIN));
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => Status::ROLE_ADMIN,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);
    }

    public function test_pending_factory_state_creates_a_pending_regular_user(): void
    {
        $user = User::factory()->pending()->create();

        $this->assertFalse($user->isActive());
        $this->assertTrue($user->hasRole(Status::ROLE_USER));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => Status::ROLE_USER,
            'account_status' => Status::ACCOUNT_PENDING,
        ]);
    }
}
