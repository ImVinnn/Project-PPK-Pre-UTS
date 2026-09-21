<?php

namespace App\Http\Requests;

use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDamageReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware (auth + role:pengguna)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'exists:facilities,id'],
            'category' => ['required', Rule::in(Status::REPORT_CATEGORIES)],
            'other_category' => ['required_if:category,lainnya', 'nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'facility_id.required' => 'Fasilitas wajib dipilih.',
            'facility_id.exists' => 'Fasilitas yang dipilih tidak valid.',
            'category.required' => 'Kategori kerusakan wajib dipilih.',
            'category.in' => 'Kategori kerusakan tidak valid.',
            'other_category.required_if' => 'Keterangan kategori lainnya wajib diisi jika memilih "Lainnya".',
            'other_category.max' => 'Keterangan kategori lainnya maksimal 100 karakter.',
            'description.required' => 'Deskripsi kerusakan wajib diisi.',
            'photo.required' => 'Foto bukti kerusakan wajib diunggah.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }

    /**
     * After validation passes, force other_category to null
     * when category is not 'lainnya'.
     */
    protected function passedValidation(): void
    {
        if ($this->input('category') !== Status::REPORT_OTHER) {
            $this->merge(['other_category' => null]);
        }
    }
}

