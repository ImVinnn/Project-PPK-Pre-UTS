<?php

namespace App\Http\Requests;

use App\Models\Building;
use App\Models\Faculty;
use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFacilityRequest extends FormRequest
{
    private const array ROOM_LIKE_TYPES = [
        Status::FACILITY_CLASSROOM,
        Status::FACILITY_HALL,
        Status::FACILITY_LABORATORY,
    ];

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
        $type = $this->input('type');

        $rules = [
            'faculty_id' => ['nullable', 'integer', Rule::exists(Faculty::class, 'id')],
            'building_id' => ['nullable', 'integer', Rule::exists(Building::class, 'id')],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(Status::FACILITY_TYPES)],
            'location_detail' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ];

        if ($type === Status::FACILITY_EQUIPMENT) {
            $rules['capacity'] = ['prohibited'];
            $rules['brand'] = ['nullable', 'string', 'max:100'];
            $rules['model'] = ['nullable', 'string', 'max:100'];
            $rules['stock_total'] = ['required', 'integer', 'min:1'];
            $rules['stock_unavailable'] = ['nullable', 'integer', 'min:0', 'lte:stock_total'];
        } else {
            $rules['capacity'] = ['required', 'integer', 'min:1'];

            if (in_array($type, self::ROOM_LIKE_TYPES, true)) {
                $rules['room_number'] = ['required', 'string', 'max:30'];
                $rules['floor'] = ['nullable', 'integer'];
            }
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $facultyId = $this->input('faculty_id');
        $buildingId = $this->input('building_id');

        $this->merge([
            'faculty_id' => filled($facultyId) ? (int) $facultyId : null,
            'building_id' => filled($buildingId) ? (int) $buildingId : null,
            'name' => trim((string) $this->input('name')),
            'location_detail' => trim((string) $this->input('location_detail')),
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $buildingId = $this->input('building_id');

            if ($buildingId === null || $validator->errors()->has('building_id')) {
                return;
            }

            $building = Building::query()->find($buildingId);

            if ($building && $building->faculty_id !== $this->input('faculty_id')) {
                $validator->errors()->add(
                    'faculty_id',
                    'Fakultas fasilitas harus sama dengan fakultas pemilik gedung (Universitas jika gedung milik universitas).',
                );
            }
        });
    }
}
