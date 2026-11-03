<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;

class DispatchTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('trips.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'actual_departure' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'start_odometer' => ['required', 'integer', 'min:0'],
        ];
    }
}
