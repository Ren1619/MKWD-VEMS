<?php

namespace App\Http\Requests\Vehicles;

use App\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('vehicles.import') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ams_asset_id' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Vehicle::class, 'ams_asset_id')
                    ->where('source_system', Vehicle::SOURCE_MKWD_IMS),
            ],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'operational_status' => ['required', Rule::in(Vehicle::operationalStatuses())],
            'fuel_type' => ['nullable', Rule::in(Vehicle::fuelTypes())],
            'maintenance_interval_km' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'operational_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
