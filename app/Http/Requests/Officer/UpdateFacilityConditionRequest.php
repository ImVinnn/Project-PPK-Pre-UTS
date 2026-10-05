<?php

namespace App\Http\Requests\Officer;

use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilityConditionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Dilindungi oleh middleware auth & role:petugas
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $report = $this->route('report');
        $facility = $report?->facility;

        $rules = [
            'facility_status' => [
                'required',
                Rule::in([Status::FACILITY_ACTIVE, Status::FACILITY_MAINTENANCE]),
            ],
        ];

        if ($facility && $facility->isEquipment() && $facility->equipmentDetail) {
            $maxStock = $facility->equipmentDetail->stock_total;
            $rules['stock_unavailable'] = [
                'required',
                'integer',
                'min:0',
                'max:' . $maxStock,
            ];
        }

        return $rules;
    }

    /**
     * Custom validation logic: proteksi fasilitas nonaktif & stok pada non-alat.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $report = $this->route('report');
            $facility = $report?->facility;

            if (! $facility) {
                $validator->errors()->add('facility_status', 'Fasilitas tidak ditemukan.');
                return;
            }

            // Petugas tidak boleh mengubah fasilitas yang sedang nonaktif
            if ($facility->status === Status::FACILITY_INACTIVE) {
                $validator->errors()->add(
                    'facility_status',
                    'Fasilitas yang berstatus nonaktif hanya dapat diubah oleh administrator.'
                );
            }

            // stock_unavailable ditolak jika fasilitas bukan alat
            if (! $facility->isEquipment() && $this->has('stock_unavailable') && $this->input('stock_unavailable') !== null) {
                $validator->errors()->add(
                    'stock_unavailable',
                    'Stok rusak hanya dapat diatur untuk fasilitas bertipe alat.'
                );
            }
        });
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'facility_status.required' => 'Status operasional fasilitas wajib dipilih.',
            'facility_status.in'       => 'Status fasilitas hanya boleh aktif atau maintenance.',
            'stock_unavailable.required' => 'Jumlah stok rusak wajib diisi.',
            'stock_unavailable.integer'  => 'Jumlah stok rusak harus berupa angka bulat.',
            'stock_unavailable.min'      => 'Jumlah stok rusak minimal 0.',
            'stock_unavailable.max'      => 'Jumlah stok rusak tidak boleh melebihi total stok (:max unit).',
        ];
    }
}
