<?php

namespace App\Http\Requests\Assignments;

use App\Models\VehicleDriverAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignments.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'regex:/\A[1-9]\d*\z/', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'regex:/\A[1-9]\d*\z/', 'integer', 'exists:drivers,id'],
            'start_date' => ['required', 'regex:/\A\d{4}-\d{2}-\d{2}\z/', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'regex:/\A\d{4}-\d{2}-\d{2}\z/', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'assignment_type' => ['required', Rule::in(VehicleDriverAssignment::types())],
        ];
    }
}
