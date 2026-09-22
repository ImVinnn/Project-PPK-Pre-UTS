<?php

namespace Tests\Feature\Officer;

use App\Models\DamageReport;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeOfficer(): User
    {
        return User::factory()->officer()->create();
    }

    private function makeRoomFacility(array $overrides = []): Facility
    {
        return Facility::query()->create(array_merge([
            'name' => 'Ruang Kelas A101',
            'type' => Status::FACILITY_CLASSROOM,
            'capacity' => 40,
            'location_detail' => 'Gedung A',
            'status' => Status::FACILITY_ACTIVE,
        ], $overrides));
    }

    private function makeReservation(
        User $user,
        Facility $facility,
        string $start,
        string $end,
        string $status = Status::RESERVATION_PENDING,
    ): Reservation {
        return Reservation::query()->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'quantity' => 1,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => 'Uji coba',
            'status' => $status,
        ]);
    }

    /**
     * DamageReport::user_id dan ::status sengaja tidak ada di $fillable (lihat
     * DamageReportController::store()) — diisi lewat assignment properti
     * langsung, bukan mass-assignment, supaya cocok dengan pola yang sama.
     */
    private function makeReport(User $user, Facility $facility, string $status): DamageReport
    {
        $report = new DamageReport([
            'facility_id' => $facility->id,
            'category' => Status::REPORT_PHYSICAL_DAMAGE,
            'description' => 'Uji coba laporan',
            'photo_path' => 'reports/dummy.jpg',
        ]);
        $report->user_id = $user->id;
        $report->status = $status;
        $report->save();

        return $report;
    }

    // -- Akses --------------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('officer.dashboard'))->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('officer.dashboard'))->assertForbidden();
    }

    public function test_admin_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('officer.dashboard'))->assertForbidden();
    }

    public function test_officer_can_view_dashboard(): void
    {
        $officer = $this->makeOfficer();

        $this->actingAs($officer)->get(route('officer.dashboard'))->assertOk();
    }

    // -- Kartu ringkasan ------------------------------------------------------------

    public function test_summary_cards_show_correct_counts(): void
    {
        $officer = $this->makeOfficer();
        $applicant = User::factory()->create();
        $facility = $this->makeRoomFacility();

        $this->makeReservation($applicant, $facility, '2026-10-05 09:00:00', '2026-10-05 10:00:00', Status::RESERVATION_PENDING);
        $this->makeReservation($applicant, $facility, '2026-10-06 09:00:00', '2026-10-06 10:00:00', Status::RESERVATION_PENDING);
        $this->makeReservation($applicant, $facility, '2026-10-07 09:00:00', '2026-10-07 10:00:00', Status::RESERVATION_APPROVED);

        $this->makeReport($applicant, $facility, Status::REPORT_NEW);
        $this->makeReport($applicant, $facility, Status::REPORT_IN_PROGRESS);
        $this->makeReport($applicant, $facility, Status::REPORT_IN_PROGRESS);
        $this->makeReport($applicant, $facility, Status::REPORT_COMPLETED);

        $response = $this->actingAs($officer)->get(route('officer.dashboard'));

        $response->assertOk();
        $response->assertViewHas('pendingReservationsCount', 2);
        $response->assertViewHas('newReportsCount', 1);
        $response->assertViewHas('inProgressReportsCount', 2);
    }

    // -- Daftar reservasi pending ------------------------------------------------------

    public function test_pending_reservations_are_ordered_oldest_first_and_exclude_other_statuses(): void
    {
        $officer = $this->makeOfficer();
        $applicant = User::factory()->create();
        $facility = $this->makeRoomFacility();

        $this->travelTo(Carbon::parse('2026-09-20 08:00:00'));
        $older = $this->makeReservation($applicant, $facility, '2026-10-05 09:00:00', '2026-10-05 10:00:00');

        $this->travelTo(Carbon::parse('2026-09-20 09:00:00'));
        $newer = $this->makeReservation($applicant, $facility, '2026-10-06 09:00:00', '2026-10-06 10:00:00');

        // Bukan pending — tidak boleh muncul di daftar.
        $this->makeReservation($applicant, $facility, '2026-10-07 09:00:00', '2026-10-07 10:00:00', Status::RESERVATION_APPROVED);

        $response = $this->actingAs($officer)->get(route('officer.dashboard'));

        $ids = collect($response->viewData('pendingReservations'))->pluck('id')->all();

        $this->assertSame([$older->id, $newer->id], $ids);
    }

    // -- Daftar laporan ----------------------------------------------------------------

    public function test_completed_and_rejected_reports_do_not_appear(): void
    {
        $officer = $this->makeOfficer();
        $applicant = User::factory()->create();
        $facility = $this->makeRoomFacility();

        $new = $this->makeReport($applicant, $facility, Status::REPORT_NEW);
        $inProgress = $this->makeReport($applicant, $facility, Status::REPORT_IN_PROGRESS);
        $this->makeReport($applicant, $facility, Status::REPORT_COMPLETED);
        $this->makeReport($applicant, $facility, Status::REPORT_REJECTED);

        $response = $this->actingAs($officer)->get(route('officer.dashboard'));

        $ids = collect($response->viewData('queuedReports'))->pluck('id')->sort()->values()->all();

        $this->assertSame([$new->id, $inProgress->id], $ids);
    }

    // -- Penanda mendesak -----------------------------------------------------------------

    public function test_reservation_starting_within_24_hours_gets_segera_label(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 00:00:00'));

        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $this->makeReservation(User::factory()->create(), $facility, '2026-10-05 12:00:00', '2026-10-05 13:00:00');

        $response = $this->actingAs($officer)->get(route('officer.dashboard'));

        $response->assertOk();
        $response->assertSee('Segera');
        $response->assertDontSee('Kedaluwarsa');
    }

    public function test_reservation_past_start_time_gets_kedaluwarsa_label(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 12:00:00'));

        $officer = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        // start_time sudah lewat, tapi statusnya sengaja masih pending —
        // scheduler yang bertugas mengubah status, dashboard cukup menampilkan.
        $reservation = $this->makeReservation(User::factory()->create(), $facility, '2026-10-05 09:00:00', '2026-10-05 10:00:00');

        $response = $this->actingAs($officer)->get(route('officer.dashboard'));

        $response->assertOk();
        $response->assertSee('Kedaluwarsa');
        $this->assertSame(Status::RESERVATION_PENDING, $reservation->fresh()->status);
    }

    // -- Keadaan kosong --------------------------------------------------------------------

    public function test_empty_dashboard_renders_without_error(): void
    {
        $officer = $this->makeOfficer();

        $response = $this->actingAs($officer)->get(route('officer.dashboard'));

        $response->assertOk();
        $response->assertViewHas('pendingReservationsCount', 0);
        $response->assertViewHas('newReportsCount', 0);
        $response->assertViewHas('inProgressReportsCount', 0);
        $response->assertSee('Antrean bersih', escape: false);
    }
}
