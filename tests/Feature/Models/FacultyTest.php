<?php

namespace Tests\Feature\Models;

use App\Models\Building;
use App\Models\Faculty;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FacultyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_exposes_its_buildings(): void
    {
        $faculty = Faculty::factory()->create();
        $building = Building::factory()->for($faculty)->create();

        $this->assertTrue($faculty->buildings->contains($building));
    }

    public function test_university_building_has_no_faculty(): void
    {
        $building = Building::factory()->university()->create();

        $this->assertNull($building->faculty);
        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'faculty_id' => null,
        ]);
    }
}
