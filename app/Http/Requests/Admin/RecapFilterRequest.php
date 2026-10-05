<?php

namespace App\Http\Requests\Admin;

use App\Models\Building;
use App\Models\Faculty;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecapFilterRequest extends FormRequest
{
    /** Format bawaan input datetime-local. */
    public const string FORMAT = 'Y-m-d\TH:i';

    public function authorize(): bool
    {
        return $this->user()?->hasRole(Status::ROLE_ADMIN) === true;
    }

    /**
     * Halaman dibuka tanpa filter → bulan berjalan, [awal bulan, awal bulan berikutnya).
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('from') && ! $this->filled('to')) {
            $monthStart = now()->startOfMonth();

            $this->merge([
                'from' => $monthStart->format(self::FORMAT),
                'to' => $monthStart->copy()->addMonth()->format(self::FORMAT),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:'.self::FORMAT],
            'to' => ['required', 'date_format:'.self::FORMAT, 'after:from'],
            'faculty_id' => ['nullable', 'integer', Rule::exists(Faculty::class, 'id')],
            'building_id' => ['nullable', 'integer', Rule::exists(Building::class, 'id')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from', 'to'])) {
                    return;
                }

                if ($this->parse('to')->gt($this->parse('from')->addYear())) {
                    $validator->errors()->add('to', 'Rentang periode rekap maksimal 1 tahun.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.required' => 'Waktu awal periode wajib diisi.',
            'from.date_format' => 'Format waktu awal tidak valid.',
            'to.required' => 'Waktu akhir periode wajib diisi.',
            'to.date_format' => 'Format waktu akhir tidak valid.',
            'to.after' => 'Waktu akhir harus setelah waktu awal.',
            'faculty_id.exists' => 'Fakultas yang dipilih tidak valid.',
            'building_id.exists' => 'Gedung yang dipilih tidak valid.',
        ];
    }

    /**
     * Filter siap pakai untuk RecapService::recap(); kunci = nama parameter.
     *
     * @return array{from: Carbon, to: Carbon, facultyId: ?int, buildingId: ?int}
     */
    public function filters(): array
    {
        return [
            'from' => $this->parse('from'),
            'to' => $this->parse('to'),
            'facultyId' => $this->filled('faculty_id') ? $this->integer('faculty_id') : null,
            'buildingId' => $this->filled('building_id') ? $this->integer('building_id') : null,
        ];
    }

    /**
     * Gagal validasi kembali ke halaman rekap tanpa parameter (bulan berjalan),
     * pesan dan input lama ikut dibawa.
     */
    protected function getRedirectUrl(): string
    {
        return route('admin.recaps.index');
    }

    private function parse(string $key): Carbon
    {
        // createFromFormat mengisi detik dengan detik sekarang; dibulatkan ke menit.
        return Carbon::createFromFormat(self::FORMAT, (string) $this->input($key))->startOfMinute();
    }
}
