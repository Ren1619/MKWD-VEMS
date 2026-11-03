<?php

namespace App\Http\Requests\Vehicles;

use App\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateVehicleRequest extends FormRequest
{
    private const AMS_OWNED_FIELDS = [
        'source_system',
        'ams_asset_id',
        'property_tag_uuid',
        'property_number',
        'serial_number',
        'plate_number',
        'name',
        'brand',
        'model',
        'vehicle_year',
        'description',
        'category_code',
        'category_name',
        'vehicle_category_id',
        'subcategory_code',
        'subcategory_name',
        'vehicle_subcategory_id',
        'unit_of_measure',
        'location',
        'office_name',
        'nature_of_occupancy',
        'fund_cluster',
        'acquisition_date',
        'acquisition_cost',
        'ams_asset_status',
        'ams_condition_status',
        'ams_custody_status',
        'current_custodian_code',
        'current_custodian',
        'current_borrower_code',
        'current_borrower',
        'ams_created_at',
        'ams_updated_at',
        'is_present_in_ams',
        'last_sync_error',
        'last_synced_at',
        'imported_at',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('vehicles.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_mileage' => ['required', 'integer', 'min:0'],
            'mileage_correction_reason' => ['nullable', 'string', 'max:2000'],
            'operational_status' => ['required', Rule::in(Vehicle::operationalStatuses())],
            'fuel_type' => ['nullable', Rule::in(Vehicle::fuelTypes())],
            'maintenance_interval_km' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'operational_notes' => ['nullable', 'string', 'max:5000'],
            ...array_fill_keys(self::AMS_OWNED_FIELDS, ['missing']),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $vehicle = $this->route('vehicle');

                if (! $vehicle instanceof Vehicle) {
                    return;
                }

                if ($this->integer('current_mileage') !== $vehicle->current_mileage
                    && $this->string('mileage_correction_reason')->trim()->isEmpty()) {
                    $validator->errors()->add('mileage_correction_reason', 'Explain why this manual mileage correction is required.');
                }

                if (
                    Vehicle::amsStatusRequiresRetirement($vehicle->ams_asset_status)
                    && $this->string('operational_status')->toString() !== Vehicle::STATUS_RETIRED
                ) {
                    $validator->errors()->add('operational_status', 'A vehicle disposed or retired in AMS must remain retired in VEMS.');
                }
            },
        ];
    }
}
