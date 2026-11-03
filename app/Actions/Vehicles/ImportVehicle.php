<?php

namespace App\Actions\Vehicles;

use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class ImportVehicle
{
    public function __construct(
        private readonly FetchVehicles $fetchVehicles,
        private readonly ApplyAmsVehicleData $applyAmsVehicleData,
        private readonly SynchronizeVehicleTaxonomy $synchronizeVehicleTaxonomy,
    ) {}

    /**
     * @param  array<string, mixed>  $operationalData
     */
    public function handle(array $operationalData): Vehicle
    {
        $amsAssetId = (string) $operationalData['ams_asset_id'];
        $amsVehicle = collect($this->fetchVehicles->handle())->firstWhere('id', $amsAssetId);

        if (! is_array($amsVehicle)) {
            throw ValidationException::withMessages([
                'ams_asset_id' => 'The selected asset is no longer available from AMS.',
            ]);
        }

        if (Vehicle::amsStatusRequiresRetirement($amsVehicle['lifecycle_status'])) {
            throw ValidationException::withMessages([
                'ams_asset_id' => 'Disposed or retired AMS assets cannot be imported for operational use.',
            ]);
        }

        return DB::transaction(function () use ($amsAssetId, $amsVehicle, $operationalData): Vehicle {
            $vehicle = Vehicle::query()->firstOrCreate(
                [
                    'source_system' => Vehicle::SOURCE_MKWD_IMS,
                    'ams_asset_id' => $amsAssetId,
                ],
                [
                    ...Arr::only($operationalData, [
                        'current_mileage',
                        'fuel_type',
                        'operational_status',
                        'maintenance_interval_km',
                        'operational_notes',
                    ]),
                    'name' => $amsVehicle['name'],
                    'imported_at' => now(),
                ],
            );

            if (! $vehicle->wasRecentlyCreated) {
                throw ValidationException::withMessages([
                    'ams_asset_id' => 'This AMS vehicle has already been imported.',
                ]);
            }

            $taxonomy = $this->synchronizeVehicleTaxonomy->handle([$amsVehicle], now(), false);
            $vehicle = $this->applyAmsVehicleData->handle(
                $vehicle,
                $amsVehicle,
                $amsVehicle['category_code'] === null
                    ? null
                    : $taxonomy['category_ids']->get($amsVehicle['category_code']),
                $amsVehicle['subcategory_code'] === null
                    ? null
                    : $taxonomy['subcategory_ids']->get($amsVehicle['subcategory_code']),
            );

            Log::info('Vehicle imported from AMS.', [
                'vehicle_id' => $vehicle->id,
                'ams_asset_id' => $vehicle->ams_asset_id,
            ]);

            return $vehicle;
        });
    }
}
