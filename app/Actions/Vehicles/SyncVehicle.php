<?php

namespace App\Actions\Vehicles;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SyncVehicle
{
    public function __construct(
        private readonly FetchVehicles $fetchVehicles,
        private readonly ApplyAmsVehicleData $applyAmsVehicleData,
        private readonly SynchronizeVehicleTaxonomy $synchronizeVehicleTaxonomy,
    ) {}

    public function handle(Vehicle $vehicle): bool
    {
        $amsVehicle = collect($this->fetchVehicles->handle())
            ->firstWhere('id', $vehicle->ams_asset_id);

        if (! is_array($amsVehicle)) {
            $vehicle->update([
                'is_present_in_ams' => false,
                'last_sync_error' => 'This asset was not returned by the latest AMS vehicle feed.',
            ]);

            Log::warning('Imported vehicle was not returned by AMS.', [
                'vehicle_id' => $vehicle->id,
                'ams_asset_id' => $vehicle->ams_asset_id,
            ]);

            return false;
        }

        DB::transaction(function () use ($vehicle, $amsVehicle): void {
            $synchronizedAt = now();
            $taxonomy = $this->synchronizeVehicleTaxonomy->handle([$amsVehicle], $synchronizedAt, false);

            $this->applyAmsVehicleData->handle(
                $vehicle,
                $amsVehicle,
                $amsVehicle['category_code'] === null
                    ? null
                    : $taxonomy['category_ids']->get($amsVehicle['category_code']),
                $amsVehicle['subcategory_code'] === null
                    ? null
                    : $taxonomy['subcategory_ids']->get($amsVehicle['subcategory_code']),
            );
        });

        Log::info('Vehicle synchronized with AMS.', [
            'vehicle_id' => $vehicle->id,
            'ams_asset_id' => $vehicle->ams_asset_id,
        ]);

        return true;
    }
}
