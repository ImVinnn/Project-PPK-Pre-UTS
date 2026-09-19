<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_open_registration_page(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_registration_creates_pending_regular_user_and_ignores_privileged_input(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '  Budi Santoso  ',
            'email' => '  BUDI@EXAMPLE.COM ',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => Status::ROLE_ADMIN,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user = User::query()->where('email', 'budi@example.com')->firstOrFail();

        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame(Status::ROLE_USER, $user->role);
        $this->assertSame(Status::ACCOUNT_PENDING, $user->account_status);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_registration_rejects_duplicate_normalized_email(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Budi Lain',
            'email' => ' BUDI@EXAMPLE.COM ',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }
}
