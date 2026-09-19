<?php

namespace App\Http\Requests\Admin;

use App\Models\Building;
use App\Models\Faculty;
use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Status::ROLE_ADMIN) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'faculty_id' => ['nullable', 'integer', Rule::exists(Faculty::class, 'id')],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique(Building::class)
                    ->where(fn ($query) => $query->where('faculty_id', $this->input('faculty_id')))
                    ->ignore($this->route('building')),
            ],
            'name' => ['required', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $facultyId = $this->input('faculty_id');

        $this->merge([
            'faculty_id' => filled($facultyId) ? (int) $facultyId : null,
            'code' => Str::upper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
