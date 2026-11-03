<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('trips.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'vehicle_id' => [Rule::requiredIf($this->input('decision') === 'approved'), 'nullable', 'integer', 'exists:vehicles,id'],
            'driver_id' => [Rule::requiredIf($this->input('decision') === 'approved'), 'nullable', 'integer', 'exists:drivers,id'],
            'decision_notes' => [Rule::requiredIf($this->input('decision') === 'rejected'), 'nullable', 'string', 'max:2000'],
        ];
    }
}
