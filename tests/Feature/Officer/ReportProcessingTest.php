<?php

namespace Tests\Feature\Officer;

use App\Models\DamageReport;
use App\Models\Facility;
use App\Models\User;
use App\Support\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportProcessingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeOfficer(): User
    {
        return User::factory()->officer()->create();
    }

    private function makeUser(): User
    {
        return User::factory()->create(['role' => Status::ROLE_USER]);
    }

    private function makeAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function makeRoomFacility(array $overrides = []): Facility
    {
        return Facility::query()->create(array_merge([
            'name'            => 'Ruang Kelas 101',
            'type'            => Status::FACILITY_CLASSROOM,
            'capacity'        => 30,
            'location_detail' => 'Gedung A',
            'status'          => Status::FACILITY_ACTIVE,
        ], $overrides));
    }

    private function makeEquipmentFacility(int $stockTotal = 10, int $stockUnavailable = 2, array $overrides = []): Facility
    {
        $facility = Facility::query()->create(array_merge([
            'name'            => 'Proyektor Epson',
            'type'            => Status::FACILITY_EQUIPMENT,
            'capacity'        => null,
            'location_detail' => 'Ruang Alat',
            'status'          => Status::FACILITY_ACTIVE,
        ], $overrides));

        $facility->equipmentDetail()->create([
            'brand'             => 'Epson',
            'model'             => 'EB-X500',
            'stock_total'       => $stockTotal,
            'stock_unavailable' => $stockUnavailable,
        ]);

        return $facility;
    }

    private function makeReport(
        User $user,
        Facility $facility,
        string $status = Status::REPORT_NEW,
        ?string $note = null
    ): DamageReport {
        $report = new DamageReport([
            'facility_id' => $facility->id,
            'category'    => Status::REPORT_PHYSICAL_DAMAGE,
            'description' => 'Kerusakan meja patah',
            'photo_path'  => 'reports/test.jpg',
        ]);
        $report->user_id        = $user->id;
        $report->status         = $status;
        $report->resolution_note = $note;
        $report->save();

        return $report;
    }

    // -- 1. Otorisasi Akses -----------------------------------------------------

    public function test_guest_cannot_access_officer_report_actions(): void
    {
        $facility = $this->makeRoomFacility();
        $user     = $this->makeUser();
        $report   = $this->makeReport($user, $facility);

        $this->get(route('officer.reports.index'))->assertRedirect(route('login'));
        $this->get(route('officer.reports.show', $report))->assertRedirect(route('login'));
        $this->patch(route('officer.reports.status.update', $report), ['status' => Status::REPORT_IN_PROGRESS])
            ->assertRedirect(route('login'));
        $this->patch(route('officer.reports.facility.update', $report), ['facility_status' => Status::FACILITY_MAINTENANCE])
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_officer_report_actions(): void
    {
        $user     = $this->makeUser();
        $facility = $this->makeRoomFacility();
        $report   = $this->makeReport($user, $facility);

        $this->actingAs($user);

        $this->get(route('officer.reports.index'))->assertForbidden();
        $this->get(route('officer.reports.show', $report))->assertForbidden();
        $this->patch(route('officer.reports.status.update', $report), ['status' => Status::REPORT_IN_PROGRESS])
            ->assertForbidden();
        $this->patch(route('officer.reports.facility.update', $report), ['facility_status' => Status::FACILITY_MAINTENANCE])
            ->assertForbidden();
    }

    public function test_admin_cannot_access_officer_report_actions(): void
    {
        $admin    = $this->makeAdmin();
        $facility = $this->makeRoomFacility();
        $user     = $this->makeUser();
        $report   = $this->makeReport($user, $facility);

        $this->actingAs($admin);

        $this->get(route('officer.reports.index'))->assertForbidden();
        $this->get(route('officer.reports.show', $report))->assertForbidden();
        $this->patch(route('officer.reports.status.update', $report), ['status' => Status::REPORT_IN_PROGRESS])
            ->assertForbidden();
        $this->patch(route('officer.reports.facility.update', $report), ['facility_status' => Status::FACILITY_MAINTENANCE])
            ->assertForbidden();
    }

    // -- 2. Validasi Kondisi Fasilitas ------------------------------------------

    public function test_facility_status_besides_active_and_maintenance_is_rejected(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility(['status' => Status::FACILITY_ACTIVE]);
        $report   = $this->makeReport($this->makeUser(), $facility);

        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.facility.update', $report), ['facility_status' => 'inactive'])
            ->assertSessionHasErrors('facility_status');
        $this->assertSame(Status::FACILITY_ACTIVE, $facility->fresh()->status);

        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.facility.update', $report), ['facility_status' => 'rusak_berat'])
            ->assertSessionHasErrors('facility_status');
        $this->assertSame(Status::FACILITY_ACTIVE, $facility->fresh()->status);
    }

    public function test_inactive_facility_rejects_any_update_from_officer(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility(['status' => Status::FACILITY_INACTIVE]);
        $report   = $this->makeReport($this->makeUser(), $facility);

        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.facility.update', $report), ['facility_status' => Status::FACILITY_ACTIVE])
            ->assertSessionHasErrors('facility_status');
        $this->assertSame(Status::FACILITY_INACTIVE, $facility->fresh()->status);

        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.facility.update', $report), ['facility_status' => Status::FACILITY_MAINTENANCE])
            ->assertSessionHasErrors('facility_status');
        $this->assertSame(Status::FACILITY_INACTIVE, $facility->fresh()->status);
    }

    public function test_stock_unavailable_on_non_equipment_facility_is_rejected(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility(['status' => Status::FACILITY_ACTIVE]);
        $report   = $this->makeReport($this->makeUser(), $facility);

        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.facility.update', $report), [
                'facility_status'   => Status::FACILITY_MAINTENANCE,
                'stock_unavailable' => 3,
            ])
            ->assertSessionHasErrors('stock_unavailable');
        $this->assertSame(Status::FACILITY_ACTIVE, $facility->fresh()->status);
    }

    public function test_stock_unavailable_exceeding_stock_total_is_rejected_and_db_remains_unchanged(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeEquipmentFacility(stockTotal: 10, stockUnavailable: 2);
        $report   = $this->makeReport($this->makeUser(), $facility);

        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.facility.update', $report), [
                'facility_status'   => Status::FACILITY_ACTIVE,
                'stock_unavailable' => 15,
            ])
            ->assertSessionHasErrors('stock_unavailable');

        $this->assertSame(2, $facility->equipmentDetail->fresh()->stock_unavailable);
        $this->assertSame(Status::FACILITY_ACTIVE, $facility->fresh()->status);
    }

    public function test_officer_can_update_facility_status_and_stock_within_valid_range(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeEquipmentFacility(stockTotal: 10, stockUnavailable: 2);
        $report   = $this->makeReport($this->makeUser(), $facility);

        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.facility.update', $report), [
                'facility_status'   => Status::FACILITY_MAINTENANCE,
                'stock_unavailable' => 5,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('officer.reports.show', $report));

        $this->assertSame(Status::FACILITY_MAINTENANCE, $facility->fresh()->status);
        $this->assertSame(5, $facility->equipmentDetail->fresh()->stock_unavailable);
    }

    // -- 3. Transisi Status Laporan ---------------------------------------------

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $user     = $this->makeUser();

        // baru → selesai (tidak sah)
        $reportNew = $this->makeReport($user, $facility, Status::REPORT_NEW);
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $reportNew))
            ->patch(route('officer.reports.status.update', $reportNew), [
                'status'          => Status::REPORT_COMPLETED,
                'resolution_note' => 'Langsung diselesaikan',
            ])
            ->assertSessionHasErrors('status');
        $this->assertSame(Status::REPORT_NEW, $reportNew->fresh()->status);

        // diproses → baru (mundur, tidak sah)
        $reportInProgress = $this->makeReport($user, $facility, Status::REPORT_IN_PROGRESS);
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $reportInProgress))
            ->patch(route('officer.reports.status.update', $reportInProgress), [
                'status' => Status::REPORT_NEW,
            ])
            ->assertSessionHasErrors('status');
        $this->assertSame(Status::REPORT_IN_PROGRESS, $reportInProgress->fresh()->status);

        // selesai → diproses (status final, tidak boleh diubah)
        $reportCompleted = $this->makeReport($user, $facility, Status::REPORT_COMPLETED, 'Sudah selesai diperbaiki');
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $reportCompleted))
            ->patch(route('officer.reports.status.update', $reportCompleted), [
                'status' => Status::REPORT_IN_PROGRESS,
            ])
            ->assertSessionHasErrors('status');
        $this->assertSame(Status::REPORT_COMPLETED, $reportCompleted->fresh()->status);

        // ditolak → diproses (status final, tidak boleh diubah)
        $reportRejected = $this->makeReport($user, $facility, Status::REPORT_REJECTED, 'Ditolak');
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $reportRejected))
            ->patch(route('officer.reports.status.update', $reportRejected), [
                'status' => Status::REPORT_IN_PROGRESS,
            ])
            ->assertSessionHasErrors('status');
        $this->assertSame(Status::REPORT_REJECTED, $reportRejected->fresh()->status);
    }

    public function test_same_status_allowed_to_update_note_for_non_final_reports(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $user     = $this->makeUser();

        // diproses → diproses untuk update catatan: diizinkan
        $report = $this->makeReport($user, $facility, Status::REPORT_IN_PROGRESS, 'Teknisi sudah dihubungi');
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.status.update', $report), [
                'status'          => Status::REPORT_IN_PROGRESS,
                'resolution_note' => 'Suku cadang sedang dikirim dari gudang',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('officer.reports.show', $report));
        $this->assertSame('Suku cadang sedang dikirim dari gudang', $report->fresh()->resolution_note);

        // selesai → selesai: DITOLAK (status final)
        $completedReport = $this->makeReport($user, $facility, Status::REPORT_COMPLETED, 'Sudah selesai');
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $completedReport))
            ->patch(route('officer.reports.status.update', $completedReport), [
                'status'          => Status::REPORT_COMPLETED,
                'resolution_note' => 'Catatan baru',
            ])
            ->assertSessionHasErrors('status');
        $this->assertSame('Sudah selesai', $completedReport->fresh()->resolution_note);
    }

    public function test_valid_transitions_work_properly(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $user     = $this->makeUser();
        $report   = $this->makeReport($user, $facility, Status::REPORT_NEW);

        // baru → diproses
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.status.update', $report), [
                'status'          => Status::REPORT_IN_PROGRESS,
                'resolution_note' => 'Sedang diperiksa',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame(Status::REPORT_IN_PROGRESS, $report->fresh()->status);

        // diproses → selesai (wajib catatan)
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.status.update', $report), [
                'status'          => Status::REPORT_COMPLETED,
                'resolution_note' => 'Meja telah diperbaiki.',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame(Status::REPORT_COMPLETED, $report->fresh()->status);
        $this->assertSame('Meja telah diperbaiki.', $report->fresh()->resolution_note);
    }

    public function test_resolution_note_required_when_marking_completed_or_rejected(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $user     = $this->makeUser();

        // diproses → selesai tanpa catatan → ditolak
        $report = $this->makeReport($user, $facility, Status::REPORT_IN_PROGRESS);
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $report))
            ->patch(route('officer.reports.status.update', $report), [
                'status'          => Status::REPORT_COMPLETED,
                'resolution_note' => '',
            ])
            ->assertSessionHasErrors('resolution_note');
        $this->assertSame(Status::REPORT_IN_PROGRESS, $report->fresh()->status);

        // baru → ditolak tanpa catatan → ditolak
        $reportNew = $this->makeReport($user, $facility, Status::REPORT_NEW);
        $this->actingAs($officer)
            ->from(route('officer.reports.show', $reportNew))
            ->patch(route('officer.reports.status.update', $reportNew), [
                'status'          => Status::REPORT_REJECTED,
                'resolution_note' => '',
            ])
            ->assertSessionHasErrors('resolution_note');
        $this->assertSame(Status::REPORT_NEW, $reportNew->fresh()->status);
    }

    // -- 4. Pengujian View (show.blade.php) -------------------------------------

    public function test_final_report_does_not_render_status_update_form(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility();
        $user     = $this->makeUser();
        $report   = $this->makeReport($user, $facility, Status::REPORT_COMPLETED, 'Sudah diselesaikan');

        $response = $this->actingAs($officer)->get(route('officer.reports.show', $report));

        $response->assertOk();
        $response->assertSee('Laporan Berstatus Final');
        $response->assertDontSee('Simpan Perubahan Status');
    }

    public function test_inactive_facility_renders_disabled_form_with_notice(): void
    {
        $officer  = $this->makeOfficer();
        $facility = $this->makeRoomFacility(['status' => Status::FACILITY_INACTIVE]);
        $user     = $this->makeUser();
        $report   = $this->makeReport($user, $facility, Status::REPORT_NEW);

        $response = $this->actingAs($officer)->get(route('officer.reports.show', $report));

        $response->assertOk();
        $response->assertSee('Fasilitas nonaktif hanya bisa diubah oleh admin');
        $response->assertSee('disabled', false);
    }
}
