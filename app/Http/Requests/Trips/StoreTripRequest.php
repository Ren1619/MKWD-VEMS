<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('trips.request') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255', 'regex:/\A[^\x00-\x1F\x7F\r\n]+\z/u'],
            'purpose' => ['required', 'string', 'max:5000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'passengers' => ['nullable', 'string', 'max:5000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'planned_departure' => ['required', 'regex:/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}\z/', 'date_format:Y-m-d\TH:i', 'after_or_equal:now'],
            'planned_return' => ['required', 'regex:/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}\z/', 'date_format:Y-m-d\TH:i', 'after:planned_departure'],
        ];
    }
}
