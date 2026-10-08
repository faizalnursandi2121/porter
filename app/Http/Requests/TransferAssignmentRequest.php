<?php

namespace App\Http\Requests;

use App\Models\Site;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferAssignmentRequest extends FormRequest
{
    /**
     * FR-3: transfer rules. ended_at is when the old placement stops,
     * started_at when the new one begins — started_at must not precede
     * ended_at. The target site must be active and free of an active EOS
     * (DB partial unique index + controller check).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'site_id' => [
                'required',
                Rule::exists(Site::class, 'id')->where(fn ($query) => $query->where('is_active', true)),
                Rule::unique('assignments', 'site_id')->where(fn ($query) => $query->whereNull('ended_at')),
            ],
            'ended_at' => ['required', 'date', 'after_or_equal:started_at_of_current'],
            'started_at' => ['required', 'date', 'after_or_equal:ended_at', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_id.unique' => __('This school already has an active EOS. Choose another school or end the current placement.'),
            'started_at.after_or_equal' => __('The new placement cannot start before the current one ends.'),
            'ended_at.after_or_equal' => __('The end date cannot be before the current placement started.'),
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->isAdministrator() || $this->user()->isSupervisi();
    }
}
