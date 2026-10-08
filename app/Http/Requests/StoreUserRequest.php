<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\Site;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * FR-2: account creation rules. An EOS account must be placed at a
     * school at creation time; every other role is placed later (or never).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'role_code' => ['required', Rule::in(Role::EOS, Role::SUPERVISI, Role::HR, Role::ADMINISTRATOR)],
            'site_id' => [
                Rule::requiredIf(fn () => $this->input('role_code') === Role::EOS),
                'nullable',
                Rule::exists(Site::class, 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }

    /**
     * FR-2: the mandatory school placement is expressed as "site required
     * when role is EOS" — prepare the rule message in the request's language.
     */
    public function messages(): array
    {
        return [
            'site_id.required' => __('An EOS account must be placed at a school.'),
        ];
    }
}
