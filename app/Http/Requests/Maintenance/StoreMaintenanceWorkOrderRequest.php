<?php

namespace App\Http\Requests\Maintenance;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMaintenanceWorkOrderRequest extends FormRequest
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
            'maintenance_request_id' => ['nullable', 'regex:/\A[1-9]\d*\z/', 'integer', 'exists:maintenance_requests,id'],
            'maintenance_schedule_id' => ['nullable', 'regex:/\A[1-9]\d*\z/', 'integer', 'exists:maintenance_schedules,id'],
            'title' => ['required', 'string', 'max:255', 'regex:/\A[^\x00-\x1F\x7F\r\n]+\z/u'],
            'instructions' => ['required', 'string', 'max:5000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'service_provider' => ['nullable', 'string', 'max:255', 'regex:/\A[^\x00-\x1F\x7F\r\n]+\z/u'],
            'scheduled_start' => ['nullable', 'regex:/\A\d{4}-\d{2}-\d{2}\z/', 'date_format:Y-m-d'],
            'scheduled_end' => ['nullable', 'regex:/\A\d{4}-\d{2}-\d{2}\z/', 'date_format:Y-m-d', 'after_or_equal:scheduled_start'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $vehicleId = $this->integer('vehicle_id');
            if ($this->filled('maintenance_request_id') && ! MaintenanceRequest::query()->whereKey($this->integer('maintenance_request_id'))->where('vehicle_id', $vehicleId)->where('status', MaintenanceRequest::STATUS_APPROVED)->exists()) {
                $validator->errors()->add('maintenance_request_id', 'Select an approved request for this vehicle.');
            }
            if ($this->filled('maintenance_schedule_id') && ! MaintenanceSchedule::query()->whereKey($this->integer('maintenance_schedule_id'))->where('vehicle_id', $vehicleId)->where('is_active', true)->exists()) {
                $validator->errors()->add('maintenance_schedule_id', 'Select an active schedule for this vehicle.');
            }
        }];
    }
}
