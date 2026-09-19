<?php

namespace App\Http\Requests\Admin;

use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountStatusRequest extends FormRequest
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
            'account_status' => [
                'required',
                Rule::in([
                    Status::ACCOUNT_ACTIVE,
                    Status::ACCOUNT_REJECTED,
                    Status::ACCOUNT_INACTIVE,
                ]),
            ],
        ];
    }
}
