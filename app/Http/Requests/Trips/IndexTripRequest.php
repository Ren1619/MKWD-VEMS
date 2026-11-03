<?php

namespace App\Http\Requests\Trips;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('trips.view') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Trip::statuses())],
        ];
    }
}
