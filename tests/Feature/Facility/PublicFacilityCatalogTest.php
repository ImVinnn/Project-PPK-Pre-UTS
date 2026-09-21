<?php

namespace Tests\Feature\Facility;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFacilityCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_facility_catalog(): void
    {
        Facility::query()->create([
            'name' => 'Ruang Teori 101',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 45,
            'location_detail' => 'Gedung A Lantai 1',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        $response = $this->get(route('facilities.index'));

        $response->assertOk();
        $response->assertSee('Ruang Teori 101');
        $response->assertSee('Katalog Fasilitas');
    }

    public function test_can_filter_facilities_by_type_location_and_capacity(): void
    {
        $classA = Facility::query()->create([
            'name' => 'Kelas Mawar',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 30,
            'location_detail' => 'Gedung FTI',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        $hallB = Facility::query()->create([
            'name' => 'Aula Melati',
            'type' => Status::FACILITY_HALL,
            'capacity' => 200,
            'location_detail' => 'Gedung Rektorat',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        // Filter by type
        $resType = $this->get(route('facilities.index', ['tipe' => 'aula']));
        $resType->assertOk();
        $resType->assertSee('Aula Melati');
        $resType->assertDontSee('Kelas Mawar');

        // Filter by location
        $resLoc = $this->get(route('facilities.index', ['lokasi' => 'Rektorat']));
        $resLoc->assertOk();
        $resLoc->assertSee('Aula Melati');
        $resLoc->assertDontSee('Kelas Mawar');

        // Filter by min capacity
        $resCap = $this->get(route('facilities.index', ['kapasitas_min' => 100]));
        $resCap->assertOk();
        $resCap->assertSee('Aula Melati');
        $resCap->assertDontSee('Kelas Mawar');
    }

    public function test_guest_can_view_facility_detail_with_26_slots(): void
    {
        $facility = Facility::query()->create([
            'name' => 'Lab Multimedia',
            'type' => Status::FACILITY_LABORATORY,
            'capacity' => 25,
            'location_detail' => 'Gedung FIK',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        $response = $this->get(route('facilities.show', $facility));

        $response->assertOk();
        $response->assertSee('Lab Multimedia');
        $response->assertSee('#1');
        $response->assertSee('#26');
        $response->assertSee('07:00');
        $response->assertSee('20:00');
    }

    public function test_public_slot_grid_does_not_leak_applicant_name_or_purpose(): void
    {
        $secretUser = User::factory()->create([
            'name' => 'Budi Rahasia Penting',
            'email' => 'budirahasia@kampus.id',
        ]);

        $facility = Facility::query()->create([
            'name' => 'Aula Terbuka',
            'type' => Status::FACILITY_HALL,
            'capacity' => 100,
            'location_detail' => 'Gedung Pusat',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        $date = now()->addDays(2)->toDateString();

        Reservation::query()->create([
            'user_id' => $secretUser->id,
            'facility_id' => $facility->id,
            'start_time' => "{$date} 08:00:00",
            'end_time' => "{$date} 10:00:00",
            'purpose' => 'Rapat Rahasia Evaluasi Rektorat',
            'status' => Status::RESERVATION_APPROVED,
        ]);

        $response = $this->get(route('facilities.show', ['facility' => $facility->id, 'date' => $date]));

        $response->assertOk();
        $response->assertSee('Terpakai');

        // Privacy assertion: MUST NOT contain applicant name, email, or purpose string!
        $response->assertDontSee('Budi Rahasia Penting');
        $response->assertDontSee('budirahasia@kampus.id');
        $response->assertDontSee('Rapat Rahasia Evaluasi Rektorat');
    }
}
