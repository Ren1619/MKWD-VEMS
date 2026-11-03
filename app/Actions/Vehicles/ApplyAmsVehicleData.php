<?php

namespace App\Actions\Vehicles;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

/** @phpstan-import-type AmsVehicle from FetchVehicles */
final class ApplyAmsVehicleData
{
    /**
     * @param  AmsVehicle  $amsVehicle
     */
    public function handle(
        Vehicle $vehicle,
        array $amsVehicle,
        ?int $vehicleCategoryId = null,
        ?int $vehicleSubcategoryId = null,
    ): Vehicle {
        $previousAmsStatus = $vehicle->ams_asset_status;
        $previousOperationalStatus = $vehicle->operational_status;

        $this->fill($vehicle, $amsVehicle, $vehicleCategoryId, $vehicleSubcategoryId);
        $vehicle->save();

        if ($previousAmsStatus !== $vehicle->ams_asset_status) {
            Log::info('Vehicle AMS status changed during synchronization.', [
                'vehicle_id' => $vehicle->id,
                'ams_asset_id' => $vehicle->ams_asset_id,
                'from' => $previousAmsStatus,
                'to' => $vehicle->ams_asset_status,
            ]);
        }

        if (
            $previousOperationalStatus !== Vehicle::STATUS_RETIRED
            && $vehicle->operational_status === Vehicle::STATUS_RETIRED
            && Vehicle::amsStatusRequiresRetirement($vehicle->ams_asset_status)
        ) {
            Log::warning('Vehicle retired in VEMS due to AMS lifecycle status.', [
                'vehicle_id' => $vehicle->id,
                'ams_asset_id' => $vehicle->ams_asset_id,
                'ams_asset_status' => $vehicle->ams_asset_status,
            ]);
        }

        return $vehicle->refresh();
    }

    /**
     * @param  AmsVehicle  $amsVehicle
     */
    public function fill(
        Vehicle $vehicle,
        array $amsVehicle,
        ?int $vehicleCategoryId = null,
        ?int $vehicleSubcategoryId = null,
    ): Vehicle {
        $vehicle->fill([
            'property_tag_uuid' => $amsVehicle['property_tag_uuid'],
            'property_number' => $amsVehicle['property_number'],
            'serial_number' => $amsVehicle['serial_number'],
            'plate_number' => $amsVehicle['plate_number'],
            'name' => $amsVehicle['name'],
            'brand' => $amsVehicle['brand'],
            'model' => $amsVehicle['model'],
            'vehicle_year' => $amsVehicle['vehicle_year'],
            'description' => $amsVehicle['description'],
            'category_code' => $amsVehicle['category_code'],
            'category_name' => $amsVehicle['category_name'],
            'vehicle_category_id' => $vehicleCategoryId,
            'subcategory_code' => $amsVehicle['subcategory_code'],
            'subcategory_name' => $amsVehicle['subcategory_name'],
            'vehicle_subcategory_id' => $vehicleSubcategoryId,
            'unit_of_measure' => $amsVehicle['unit_of_measure'],
            'location' => $amsVehicle['location'],
            'office_name' => $amsVehicle['office_name'],
            'nature_of_occupancy' => $amsVehicle['nature_of_occupancy'],
            'fund_cluster' => $amsVehicle['fund_cluster'],
            'acquisition_date' => $amsVehicle['acquisition_date'],
            'acquisition_cost' => $amsVehicle['acquisition_cost'],
            'ams_asset_status' => $amsVehicle['lifecycle_status'],
            'ams_condition_status' => $amsVehicle['condition_status'],
            'ams_custody_status' => $amsVehicle['custody_status'],
            'current_custodian_code' => $amsVehicle['current_custodian_code'],
            'current_custodian' => $amsVehicle['current_custodian'],
            'current_borrower_code' => $amsVehicle['current_borrower_code'],
            'current_borrower' => $amsVehicle['current_borrower'],
            'ams_created_at' => $amsVehicle['created_at'],
            'ams_updated_at' => $amsVehicle['updated_at'],
            'is_present_in_ams' => true,
            'last_sync_error' => null,
            'last_synced_at' => now(),
        ]);

        if (Vehicle::amsStatusRequiresRetirement($amsVehicle['lifecycle_status'])) {
            $vehicle->operational_status = Vehicle::STATUS_RETIRED;
        }

        return $vehicle;
    }
}
