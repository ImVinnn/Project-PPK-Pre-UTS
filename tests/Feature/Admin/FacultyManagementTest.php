<?php

namespace Tests\Feature\Admin;

use App\Models\Building;
use App\Models\Faculty;
use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacultyManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_faculty_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.faculties.index'))
            ->assertOk()
            ->assertSee($faculty->code)
            ->assertSee($faculty->name);

        $this->actingAs($admin)
            ->get(route('admin.faculties.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.faculties.edit', $faculty))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_faculty_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.faculties.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_faculty_with_normalized_data(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.faculties.store'), [
            'code' => '  fti  ',
            'name' => '  Fakultas Teknologi Informasi  ',
        ]);

        $response->assertRedirect(route('admin.faculties.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('faculties', [
            'code' => 'FTI',
            'name' => 'Fakultas Teknologi Informasi',
        ]);
    }

    public function test_faculty_code_must_be_unique_after_normalization(): void
    {
        $admin = User::factory()->admin()->create();
        Faculty::factory()->create(['code' => 'FSM']);

        $response = $this->actingAs($admin)
            ->from(route('admin.faculties.create'))
            ->post(route('admin.faculties.store'), [
                'code' => ' fsm ',
                'name' => 'Fakultas Baru',
            ]);

        $response->assertRedirect(route('admin.faculties.create'));
        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('faculties', 1);
    }

    public function test_admin_can_update_faculty_and_keep_its_code(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create([
            'code' => 'FSM',
            'name' => 'Nama Lama',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.faculties.update', $faculty),
            [
                'code' => 'fsm',
                'name' => '  Fakultas Sains dan Matematika  ',
            ],
        );

        $response->assertRedirect(route('admin.faculties.index'));
        $this->assertDatabaseHas('faculties', [
            'id' => $faculty->id,
            'code' => 'FSM',
            'name' => 'Fakultas Sains dan Matematika',
        ]);
    }

    public function test_admin_can_delete_unused_faculty(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.faculties.destroy', $faculty));

        $response->assertRedirect(route('admin.faculties.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('faculties', ['id' => $faculty->id]);
    }

    public function test_faculty_used_by_building_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();
        Building::factory()->for($faculty)->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.faculties.destroy', $faculty));

        $response->assertRedirect(route('admin.faculties.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }

    public function test_faculty_used_directly_by_facility_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        DB::table('facilities')->insert([
            'faculty_id' => $faculty->id,
            'building_id' => null,
            'name' => 'Lapangan Fakultas',
            'type' => Status::FACILITY_FIELD,
            'capacity' => 100,
            'location_detail' => 'Area fakultas',
            'description' => null,
            'status' => Status::FACILITY_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.faculties.destroy', $faculty));

        $response->assertRedirect(route('admin.faculties.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }
}
