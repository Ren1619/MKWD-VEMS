<?php

namespace App\Http\Requests\Odometer;

use App\Models\OdometerEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOdometerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('odometer.view') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'entry_type' => ['nullable', Rule::in(OdometerEntry::types())],
        ];
    }
}
