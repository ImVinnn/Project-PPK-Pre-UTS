<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_accounts_and_create_form(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = User::factory()->pending()->create();

        $this->actingAs($admin)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee($pending->name)
            ->assertSee($pending->email);

        $this->actingAs($admin)
            ->get(route('admin.accounts.create'))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_account_management(): void
    {
        $user = User::factory()->create();
        $officer = User::factory()->officer()->create();

        $this->actingAs($user)
            ->get(route('admin.accounts.index'))
            ->assertForbidden();

        $this->actingAs($officer)
            ->get(route('admin.accounts.index'))
            ->assertForbidden();
    }

    #[DataProvider('creatableRoleProvider')]
    public function test_admin_can_create_an_active_account(string $role): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.accounts.store'), [
            'name' => '  Akun Baru  ',
            'email' => '  NEW-'.$role.'@EXAMPLE.COM ',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => $role,
            'account_status' => Status::ACCOUNT_REJECTED,
        ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $response->assertSessionHas('success');

        $account = User::query()->where('email', 'new-'.$role.'@example.com')->firstOrFail();

        $this->assertSame('Akun Baru', $account->name);
        $this->assertSame($role, $account->role);
        $this->assertSame(Status::ACCOUNT_ACTIVE, $account->account_status);
        $this->assertTrue(Hash::check('rahasia123', $account->password));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function creatableRoleProvider(): array
    {
        return [
            'pengguna' => [Status::ROLE_USER],
            'petugas' => [Status::ROLE_OFFICER],
        ];
    }

    public function test_admin_cannot_create_another_admin_through_account_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.accounts.create'))
            ->post(route('admin.accounts.store'), [
                'name' => 'Admin Baru',
                'email' => 'admin-baru@example.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => Status::ROLE_ADMIN,
            ]);

        $response->assertRedirect(route('admin.accounts.create'));
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'admin-baru@example.com']);
    }

    #[DataProvider('manageableStatusProvider')]
    public function test_admin_can_update_non_admin_account_status(string $status): void
    {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->pending()->create();

        $response = $this->actingAs($admin)->patch(
            route('admin.accounts.status.update', $account),
            ['account_status' => $status],
        );

        $response->assertRedirect(route('admin.accounts.index'));
        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'account_status' => $status,
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function manageableStatusProvider(): array
    {
        return [
            'aktif' => [Status::ACCOUNT_ACTIVE],
            'ditolak' => [Status::ACCOUNT_REJECTED],
            'nonaktif' => [Status::ACCOUNT_INACTIVE],
        ];
    }

    public function test_admin_account_status_is_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.accounts.status.update', $otherAdmin), [
                'account_status' => Status::ACCOUNT_INACTIVE,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);
    }
}
