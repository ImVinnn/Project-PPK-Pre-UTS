<?php

namespace App\Http\Requests\Officer;

use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportStatusRequest extends FormRequest
{
    /**
     * Transisi sah per status sekarang. Status sama hanya untuk memperbarui catatan;
     * selesai dan ditolak adalah status final.
     */
    public const array TRANSITIONS = [
        Status::REPORT_NEW => [Status::REPORT_NEW, Status::REPORT_IN_PROGRESS, Status::REPORT_REJECTED],
        Status::REPORT_IN_PROGRESS => [Status::REPORT_IN_PROGRESS, Status::REPORT_COMPLETED, Status::REPORT_REJECTED],
        Status::REPORT_COMPLETED => [],
        Status::REPORT_REJECTED => [],
    ];

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
        return [
            'status' => ['required', Rule::in(Status::REPORT_STATUSES)],
            'resolution_note' => [
                'required_if:status,' . Status::REPORT_COMPLETED . ',' . Status::REPORT_REJECTED,
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Aturan transisi status, dibaca dari status laporan di database.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('status')) {
                return;
            }

            $current = $this->route('report')->status;
            $requested = $this->input('status');

            if (self::TRANSITIONS[$current] === []) {
                $validator->errors()->add(
                    'status',
                    'Laporan berstatus final ('.$current.') dan tidak dapat diubah lagi.'
                );
            } elseif (! in_array($requested, self::TRANSITIONS[$current], true)) {
                $validator->errors()->add(
                    'status',
                    'Status laporan tidak dapat diubah dari "'.$current.'" ke "'.$requested.'".'
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
            'status.required' => 'Status laporan wajib dipilih.',
            'status.in' => 'Status laporan yang dipilih tidak valid.',
            'resolution_note.required_if' => 'Catatan resolusi wajib diisi apabila status laporan diubah menjadi "Selesai" atau "Ditolak".',
            'resolution_note.max' => 'Catatan resolusi maksimal 1000 karakter.',
        ];
    }
}

