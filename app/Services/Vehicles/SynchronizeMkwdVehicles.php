<?php

namespace App\Services\Vehicles;

use App\Actions\Vehicles\ApplyAmsVehicleData;
use App\Actions\Vehicles\FetchVehicles;
use App\Actions\Vehicles\SynchronizeVehicleTaxonomy;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type AmsVehicle from FetchVehicles */
final class SynchronizeMkwdVehicles
{
    public function __construct(
        private readonly FetchVehicles $fetchVehicles,
        private readonly ApplyAmsVehicleData $applyAmsVehicleData,
        private readonly SynchronizeVehicleTaxonomy $synchronizeVehicleTaxonomy,
    ) {}

    public function handle(): VehicleSyncResult
    {
        $remoteVehicles = $this->fetchVehicles->handle();
        $synchronizedAt = now();

        return DB::transaction(
            fn (): VehicleSyncResult => $this->synchronizeSnapshot($remoteVehicles, $synchronizedAt),
        );
    }

    /**
     * @param  list<AmsVehicle>  $remoteVehicles
     */
    private function synchronizeSnapshot(array $remoteVehicles, CarbonInterface $synchronizedAt): VehicleSyncResult
    {
        $taxonomy = $this->synchronizeVehicleTaxonomy->handle($remoteVehicles, $synchronizedAt);
        $existingVehicles = Vehicle::query()
            ->where('source_system', Vehicle::SOURCE_MKWD_IMS)
            ->get()
            ->keyBy(fn (Vehicle $vehicle): string => $vehicle->ams_asset_id);

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $reactivated = 0;
        $remoteIds = [];

        foreach ($remoteVehicles as $remoteVehicle) {
            $remoteId = $remoteVehicle['id'];
            $remoteIds[] = $remoteId;
            $vehicle = $existingVehicles->get($remoteId);

            if (! $vehicle instanceof Vehicle) {
                $vehicle = new Vehicle([
                    'source_system' => Vehicle::SOURCE_MKWD_IMS,
                    'ams_asset_id' => $remoteId,
                    'name' => $remoteVehicle['name'],
                    'imported_at' => $synchronizedAt,
                ]);
                $created++;
            }

            $wasRecentlyCreated = ! $vehicle->exists;
            $wasPresent = $vehicle->is_present_in_ams;

            $this->applyAmsVehicleData->fill(
                $vehicle,
                $remoteVehicle,
                $remoteVehicle['category_code'] === null
                    ? null
                    : $taxonomy['category_ids']->get($remoteVehicle['category_code']),
                $remoteVehicle['subcategory_code'] === null
                    ? null
                    : $taxonomy['subcategory_ids']->get($remoteVehicle['subcategory_code']),
            );

            if (! $wasRecentlyCreated) {
                $changedFields = array_diff(
                    array_keys($vehicle->getDirty()),
                    ['is_present_in_ams', 'last_sync_error', 'last_synced_at', 'updated_at'],
                );

                if (! $wasPresent) {
                    $reactivated++;
                } elseif ($changedFields !== []) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

            $vehicle->save();
        }

        $archiveQuery = Vehicle::query()
            ->where('source_system', Vehicle::SOURCE_MKWD_IMS)
            ->where('is_present_in_ams', true);

        if ($remoteIds !== []) {
            $archiveQuery->whereNotIn('ams_asset_id', $remoteIds);
        }

        $archived = $archiveQuery->update([
            'is_present_in_ams' => false,
            'last_sync_error' => 'This asset was not returned by the latest MKWD IMS vehicle snapshot.',
            'updated_at' => $synchronizedAt,
        ]);

        return new VehicleSyncResult(
            created: $created,
            updated: $updated,
            unchanged: $unchanged,
            archived: $archived,
            reactivated: $reactivated,
            failed: 0,
        );
    }
}
