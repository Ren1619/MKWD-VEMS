<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMaintenanceScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('maintenance.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'regex:/\A[1-9]\d*\z/', 'integer', 'exists:vehicles,id'],
            'service_type' => ['required', 'string', 'max:255', 'regex:/\A[^\x00-\x1F\x7F\r\n]+\z/u'],
            'description' => ['nullable', 'string', 'max:5000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'interval_days' => ['nullable', 'regex:/\A\d+\z/', 'integer', 'min:1', 'max:3650'],
            'interval_km' => ['nullable', 'regex:/\A\d+\z/', 'integer', 'min:1', 'max:1000000'],
            'last_completed_date' => ['nullable', 'regex:/\A\d{4}-\d{2}-\d{2}\z/', 'date_format:Y-m-d'],
            'last_completed_mileage' => ['nullable', 'regex:/\A\d+\z/', 'integer', 'min:0'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('interval_days') && ! $this->filled('interval_km')) {
                $validator->errors()->add('interval_days', 'Provide a date interval, mileage interval, or both.');
            }
        }];
    }
}
