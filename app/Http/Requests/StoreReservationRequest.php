<?php

namespace App\Http\Requests;

use App\Models\Facility;
use App\Services\ReservationAvailabilityService;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Status::ROLE_USER)
            && $user->isActive();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $today = Carbon::today(config('app.timezone', 'Asia/Jakarta'))->toDateString();
        $maxDate = Carbon::today(config('app.timezone', 'Asia/Jakarta'))->addDays(Status::MAX_ADVANCE_DAYS)->toDateString();

        return [
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'date' => ['required', 'date_format:Y-m-d', "after_or_equal:{$today}", "before_or_equal:{$maxDate}"],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string', 'min:5', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Custom error messages for attribute validations.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'facility_id.required' => 'Fasilitas wajib dipilih.',
            'facility_id.exists' => 'Fasilitas yang dipilih tidak valid.',
            'date.required' => 'Tanggal penggunaan fasilitas wajib diisi.',
            'date.after_or_equal' => 'Tanggal reservasi tidak boleh di masa lampau.',
            'date.before_or_equal' => 'Reservasi hanya dapat diajukan maksimal 90 hari ke depan.',
            'start_time.required' => 'Waktu mulai wajib dipilih.',
            'end_time.required' => 'Waktu selesai wajib dipilih.',
            'purpose.required' => 'Tujuan peminjaman / agenda kegiatan wajib diisi.',
            'purpose.min' => 'Tujuan peminjaman minimal 5 karakter.',
            'purpose.max' => 'Tujuan peminjaman maksimal 255 karakter.',
            'quantity.min' => 'Jumlah unit minimal 1.',
        ];
    }

    /**
     * Configure the validator instance with the 6 mandatory server-side rules (PRD 3.4).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $tz = config('app.timezone', 'Asia/Jakarta');
            $date = $this->input('date');
            $startTimeStr = $this->input('start_time');
            $endTimeStr = $this->input('end_time');

            try {
                $startDateTime = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTimeStr}", $tz);
                $endDateTime = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTimeStr}", $tz);
            } catch (\Throwable) {
                $v->errors()->add('start_time', 'Format waktu tidak valid.');
                return;
            }

            // Aturan 1: Kelipatan 30 menit (menit harus :00 atau :30, detik :00)
            if (! in_array($startDateTime->minute, [0, 30], true) || $startDateTime->second !== 0) {
                $v->errors()->add('start_time', 'Waktu mulai harus merupakan kelipatan slot 30 menit (contoh: 08:00, 08:30).');
            }

            if (! in_array($endDateTime->minute, [0, 30], true) || $endDateTime->second !== 0) {
                $v->errors()->add('end_time', 'Waktu selesai harus merupakan kelipatan slot 30 menit (contoh: 09:00, 09:30).');
            }

            // Aturan 2: Jam operasional 07.00–20.00, tanggal sama, dan tidak di masa lalu
            $operationalStart = Carbon::createFromFormat('Y-m-d H:i', "{$date} ".Status::OPERATING_HOUR_START, $tz);
            $operationalEnd = Carbon::createFromFormat('Y-m-d H:i', "{$date} ".Status::OPERATING_HOUR_END, $tz);

            if ($startDateTime->lt($operationalStart) || $endDateTime->gt($operationalEnd)) {
                $v->errors()->add('start_time', 'Reservasi harus berada dalam jam operasional kampus (07.00 - 20.00 WIB).');
            }

            if ($startDateTime->isPast()) {
                $v->errors()->add('start_time', 'Waktu mulai reservasi tidak boleh di masa lampau.');
            }

            // Aturan 3: end_time > start_time
            if (! $endDateTime->gt($startDateTime)) {
                $v->errors()->add('end_time', 'Waktu selesai harus lebih besar daripada waktu mulai.');
                return;
            }

            // Aturan 4: Fasilitas berstatus aktif (bukan perbaikan / nonaktif)
            $facility = Facility::query()->with('equipmentDetail')->find($this->input('facility_id'));
            if (! $facility) {
                $v->errors()->add('facility_id', 'Fasilitas tidak ditemukan.');
                return;
            }

            if ($facility->status !== Status::FACILITY_ACTIVE) {
                $statusText = $facility->status === Status::FACILITY_MAINTENANCE ? 'dalam perbaikan' : 'nonaktif';
                $v->errors()->add('facility_id', "Fasilitas sedang {$statusText} dan tidak dapat direservasi.");
                return;
            }

            // Validasi kuantitas alat
            if ($facility->isEquipment()) {
                $requestedQuantity = (int) ($this->input('quantity') ?? 1);
                $usableStock = 0;
                if ($facility->equipmentDetail) {
                    $usableStock = max(0, $facility->equipmentDetail->stock_total - $facility->equipmentDetail->stock_unavailable);
                }

                if ($requestedQuantity > $usableStock) {
                    $v->errors()->add('quantity', "Jumlah yang diajukan ({$requestedQuantity}) melebihi unit siap pakai ({$usableStock} unit).");
                }
            }

            $durationMinutes = (int) $startDateTime->diffInMinutes($endDateTime);
            $maxAllowedMinutes = Status::MAX_DURATION_MINUTES[$facility->type] ?? 180;

            // Aturan 5: Durasi tidak melebihi batas jenis fasilitas
            if ($durationMinutes > $maxAllowedMinutes) {
                $maxHours = $maxAllowedMinutes / 60;
                $v->errors()->add(
                    'end_time',
                    "Durasi reservasi ({$durationMinutes} menit) melebihi batas maksimal untuk jenis {$facility->type} ({$maxHours} jam / {$maxAllowedMinutes} menit)."
                );
                return;
            }

            // Aturan 6: Akumulasi durasi rangkaian berurutan milik akun sendiri (dua arah)
            /** @var ReservationAvailabilityService $availabilityService */
            $availabilityService = app(ReservationAvailabilityService::class);
            $chainMinutes = $availabilityService->calculateContiguousChainMinutes(
                $this->user()->id,
                $facility->id,
                $startDateTime,
                $endDateTime
            );

            if ($chainMinutes > $maxAllowedMinutes) {
                $maxHours = $maxAllowedMinutes / 60;
                $v->errors()->add(
                    'start_time',
                    "Total durasi rangkaian reservasi berurutan Anda ({$chainMinutes} menit) melebihi batas maksimal jenis {$facility->type} ({$maxHours} jam). Reservasi berurutan dijumlahkan untuk mencegah monopoli fasilitas."
                );
            }
        });
    }

    /**
     * Get combined start_time Carbon instance.
     */
    public function getStartDateTime(): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i',
            "{$this->input('date')} {$this->input('start_time')}",
            config('app.timezone', 'Asia/Jakarta')
        );
    }

    /**
     * Get combined end_time Carbon instance.
     */
    public function getEndDateTime(): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i',
            "{$this->input('date')} {$this->input('end_time')}",
            config('app.timezone', 'Asia/Jakarta')
        );
    }
}
