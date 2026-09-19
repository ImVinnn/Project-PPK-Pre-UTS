<?php

namespace Tests\Feature\Admin;

use App\Models\Building;
use App\Models\Faculty;
use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuildingManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_building_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $building = Building::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.buildings.index'))
            ->assertOk()
            ->assertSee($building->code)
            ->assertSee($building->name);

        $this->actingAs($admin)
            ->get(route('admin.buildings.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.buildings.edit', $building))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_building_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.buildings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_faculty_building_with_normalized_data(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.buildings.store'), [
            'faculty_id' => (string) $faculty->id,
            'code' => '  fti-b  ',
            'name' => '  Gedung B FTI  ',
        ]);

        $response->assertRedirect(route('admin.buildings.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('buildings', [
            'faculty_id' => $faculty->id,
            'code' => 'FTI-B',
            'name' => 'Gedung B FTI',
        ]);
    }

    public function test_admin_can_create_university_building(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.buildings.store'), [
            'faculty_id' => '',
            'code' => 'GKU',
            'name' => 'Gedung Kuliah Umum',
        ])->assertRedirect(route('admin.buildings.index'));

        $this->assertDatabaseHas('buildings', [
            'faculty_id' => null,
            'code' => 'GKU',
        ]);
    }

    public function test_building_code_must_be_unique_for_same_owner(): void
    {
        $admin = User::factory()->admin()->create();
        Building::factory()->university()->create(['code' => 'GKU']);

        $response = $this->actingAs($admin)
            ->from(route('admin.buildings.create'))
            ->post(route('admin.buildings.store'), [
                'faculty_id' => '',
                'code' => ' gku ',
                'name' => 'Gedung Duplikat',
            ]);

        $response->assertRedirect(route('admin.buildings.create'));
        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('buildings', 1);
    }

    public function test_same_building_code_is_allowed_for_different_faculties(): void
    {
        $admin = User::factory()->admin()->create();
        $firstFaculty = Faculty::factory()->create();
        $secondFaculty = Faculty::factory()->create();
        Building::factory()->for($firstFaculty)->create(['code' => 'A']);

        $response = $this->actingAs($admin)->post(route('admin.buildings.store'), [
            'faculty_id' => $secondFaculty->id,
            'code' => 'A',
            'name' => 'Gedung A Fakultas Kedua',
        ]);

        $response->assertRedirect(route('admin.buildings.index'));
        $this->assertDatabaseHas('buildings', [
            'faculty_id' => $secondFaculty->id,
            'code' => 'A',
        ]);
    }

    public function test_admin_can_update_building_and_keep_its_code(): void
    {
        $admin = User::factory()->admin()->create();
        $building = Building::factory()->university()->create([
            'code' => 'RKT',
            'name' => 'Nama Lama',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.buildings.update', $building),
            [
                'faculty_id' => '',
                'code' => 'rkt',
                'name' => '  Gedung Rektorat  ',
            ],
        );

        $response->assertRedirect(route('admin.buildings.index'));
        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'faculty_id' => null,
            'code' => 'RKT',
            'name' => 'Gedung Rektorat',
        ]);
    }

    public function test_admin_can_delete_unused_building(): void
    {
        $admin = User::factory()->admin()->create();
        $building = Building::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.buildings.destroy', $building));

        $response->assertRedirect(route('admin.buildings.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
    }

    public function test_building_used_by_facility_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $building = Building::factory()->create();

        DB::table('facilities')->insert([
            'faculty_id' => $building->faculty_id,
            'building_id' => $building->id,
            'name' => 'Ruang Kelas A101',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 40,
            'location_detail' => 'Lantai 1',
            'description' => null,
            'status' => Status::FACILITY_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.buildings.destroy', $building));

        $response->assertRedirect(route('admin.buildings.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('buildings', ['id' => $building->id]);
    }
}
