<?php

namespace App\Http\Requests;

use App\Models\Site;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    /**
     * FR-34: site master data rules. Coordinates are bounded to real-world
     * lat/lng ranges; timezone must be one of the Indonesian zones Site
     * model supports.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Site::class, 'name')],
            'address' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timezone' => ['required', Rule::in(array_values(Site::TIMEZONES))],
            'primary_provider' => ['required', 'string', 'max:255'],
            'backup_provider' => ['nullable', 'string', 'max:255', 'different:primary_provider'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'backup_provider.different' => __('The backup provider must differ from the primary provider.'),
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Site::class);
    }
}
