<?php

namespace Tests\Feature\Reservation;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_reservation_lifecycle(): void
    {
        // 1. Setup facility
        $facility = Facility::query()->create([
            'name' => 'Aula Nusantara',
            'type' => Status::FACILITY_HALL,
            'capacity' => 150,
            'location_detail' => 'Gedung Pusat Lantai 1',
            'status' => Status::FACILITY_ACTIVE,
        ]);

        $user = User::factory()->create([
            'name' => 'Siswa Pengguna',
            'role' => Status::ROLE_USER,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);

        // 2. Guest visits catalog
        $resCatalog = $this->get(route('facilities.index'));
        $resCatalog->assertOk();
        $resCatalog->assertSee('Aula Nusantara');

        // 3. Guest visits facility detail (26 slots)
        $resShowFacility = $this->get(route('facilities.show', $facility));
        $resShowFacility->assertOk();
        $resShowFacility->assertSee('Aula Nusantara');

        // 4. Authenticated user opens reservation form
        $resForm = $this->actingAs($user)->get(route('reservations.create', ['facility_id' => $facility->id]));
        $resForm->assertOk();
        $resForm->assertSee('Ajukan Reservasi Fasilitas');

        // 5. User submits reservation for tomorrow 09:00 - 12:00 (3 hours = 6 slots <= 6 hours hall limit)
        $date = Carbon::tomorrow(config('app.timezone', 'Asia/Jakarta'))->toDateString();
        $resSubmit = $this->actingAs($user)->post(route('reservations.store'), [
            'facility_id' => $facility->id,
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'purpose' => 'Seminar Teknologi Kampus Mahasiswa',
        ]);

        $resSubmit->assertRedirect(route('reservations.index'));
        $resSubmit->assertSessionHas('success');

        // 6. Verify reservation exists as pending
        $reservation = Reservation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($reservation);
        $this->assertSame(Status::RESERVATION_PENDING, $reservation->status);
        $this->assertSame('Seminar Teknologi Kampus Mahasiswa', $reservation->purpose);

        // 7. User visits index page, sees their pending reservation
        $resHistory = $this->actingAs($user)->get(route('reservations.index'));
        $resHistory->assertOk();
        $resHistory->assertSee('Aula Nusantara');
        $resHistory->assertSee('Menunggu Petugas');

        // 8. User visits reservation detail
        $resDetail = $this->actingAs($user)->get(route('reservations.show', $reservation));
        $resDetail->assertOk();
        $resDetail->assertSee('Seminar Teknologi Kampus Mahasiswa');
        $resDetail->assertSee('Menunggu Verifikasi Petugas');

        // 9. User cancels reservation (tomorrow > 2 hours away)
        $resCancel = $this->actingAs($user)->patch(route('reservations.cancel', $reservation), [
            'cancel_reason' => 'Perubahan jadwal panitia seminar',
        ]);

        $resCancel->assertRedirect(route('reservations.index'));
        $resCancel->assertSessionHas('success');

        // 10. Verify reservation is now cancelled
        $reservation->refresh();
        $this->assertSame(Status::RESERVATION_CANCELLED, $reservation->status);
        $this->assertSame($user->id, $reservation->cancelled_by);
        $this->assertSame('Perubahan jadwal panitia seminar', $reservation->cancel_reason);
    }
}
