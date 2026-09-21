<?php

namespace App\Http\Requests\Officer;

use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportStatusRequest extends FormRequest
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

