<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\Site;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    /**
     * FR-3: placement rules. started_at is required and cannot be in the
     * future beyond today (placements begin the day they are recorded or
     * backdated); the EOS and site uniqueness rules are DB-enforced and
     * re-checked in the controller for clean messages.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->whereHas('role', fn ($role) => $role->where('code', Role::EOS))),
                Rule::unique('assignments', 'user_id')->where(fn ($query) => $query->whereNull('ended_at')),
            ],
            'site_id' => [
                'required',
                Rule::exists(Site::class, 'id')->where(fn ($query) => $query->where('is_active', true)),
                Rule::unique('assignments', 'site_id')->where(fn ($query) => $query->whereNull('ended_at')),
            ],
            'started_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => __('This EOS already has an active placement. End it first.'),
            'site_id.unique' => __('This school already has an active EOS. Choose another school or end the current placement.'),
            'user_id.exists' => __('Selected account is not an EOS.'),
            'site_id.exists' => __('Selected school is inactive or does not exist.'),
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->isAdministrator() || $this->user()->isSupervisi();
    }
}
