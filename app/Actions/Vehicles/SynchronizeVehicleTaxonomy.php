<?php

namespace App\Actions\Vehicles;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleSubcategory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @phpstan-import-type AmsVehicle from FetchVehicles */
final class SynchronizeVehicleTaxonomy
{
    /**
     * @param  list<AmsVehicle>  $remoteVehicles
     * @return array{category_ids: Collection<string, int>, subcategory_ids: Collection<string, int>}
     */
    public function handle(
        array $remoteVehicles,
        CarbonInterface $synchronizedAt,
        bool $deactivateMissing = true,
    ): array {
        $categoryIds = $this->synchronizeCategories($remoteVehicles, $synchronizedAt, $deactivateMissing);
        $subcategoryIds = $this->synchronizeSubcategories(
            $remoteVehicles,
            $categoryIds,
            $synchronizedAt,
            $deactivateMissing,
        );

        return [
            'category_ids' => $categoryIds,
            'subcategory_ids' => $subcategoryIds,
        ];
    }

    /**
     * @param  list<AmsVehicle>  $remoteVehicles
     * @return Collection<string, int>
     */
    private function synchronizeCategories(
        array $remoteVehicles,
        CarbonInterface $synchronizedAt,
        bool $deactivateMissing,
    ): Collection {
        $categories = collect($remoteVehicles)
            ->filter(fn (array $vehicle): bool => $vehicle['category_code'] !== null)
            ->mapWithKeys(fn (array $vehicle): array => [
                $vehicle['category_code'] => $vehicle['category_name'],
            ]);

        $rows = $categories->map(fn (?string $name, string $code): array => [
            'source_system' => Vehicle::SOURCE_MKWD_IMS,
            'source_code' => $code,
            'name' => $name,
            'is_active' => true,
            'last_synced_at' => $synchronizedAt,
            'created_at' => $synchronizedAt,
            'updated_at' => $synchronizedAt,
        ])->values()->all();

        if ($rows !== []) {
            VehicleCategory::query()->upsert(
                $rows,
                ['source_system', 'source_code'],
                ['name', 'is_active', 'last_synced_at', 'updated_at'],
            );
        }

        $categoryCodes = $categories->keys()->values()->all();

        if ($deactivateMissing) {
            $this->deactivateMissingTaxonomy(VehicleCategory::query(), $categoryCodes, $synchronizedAt);
        }

        return VehicleCategory::query()
            ->where('source_system', Vehicle::SOURCE_MKWD_IMS)
            ->whereIn('source_code', $categoryCodes)
            ->pluck('id', 'source_code');
    }

    /**
     * @param  list<AmsVehicle>  $remoteVehicles
     * @param  Collection<string, int>  $categoryIds
     * @return Collection<string, int>
     */
    private function synchronizeSubcategories(
        array $remoteVehicles,
        Collection $categoryIds,
        CarbonInterface $synchronizedAt,
        bool $deactivateMissing,
    ): Collection {
        $subcategories = collect($remoteVehicles)
            ->filter(fn (array $vehicle): bool => $vehicle['subcategory_code'] !== null)
            ->mapWithKeys(fn (array $vehicle): array => [
                $vehicle['subcategory_code'] => [
                    'name' => $vehicle['subcategory_name'],
                    'category_code' => $vehicle['category_code'],
                ],
            ]);

        $rows = $subcategories->map(fn (array $subcategory, string $code): array => [
            'vehicle_category_id' => $subcategory['category_code'] === null
                ? null
                : $categoryIds->get($subcategory['category_code']),
            'source_system' => Vehicle::SOURCE_MKWD_IMS,
            'source_code' => $code,
            'name' => $subcategory['name'],
            'is_active' => true,
            'last_synced_at' => $synchronizedAt,
            'created_at' => $synchronizedAt,
            'updated_at' => $synchronizedAt,
        ])->values()->all();

        if ($rows !== []) {
            VehicleSubcategory::query()->upsert(
                $rows,
                ['source_system', 'source_code'],
                ['vehicle_category_id', 'name', 'is_active', 'last_synced_at', 'updated_at'],
            );
        }

        $subcategoryCodes = $subcategories->keys()->values()->all();

        if ($deactivateMissing) {
            $this->deactivateMissingTaxonomy(VehicleSubcategory::query(), $subcategoryCodes, $synchronizedAt);
        }

        return VehicleSubcategory::query()
            ->where('source_system', Vehicle::SOURCE_MKWD_IMS)
            ->whereIn('source_code', $subcategoryCodes)
            ->pluck('id', 'source_code');
    }

    /**
     * @template TTaxonomy of VehicleCategory|VehicleSubcategory
     *
     * @param  Builder<TTaxonomy>  $query
     * @param  array<int, string>  $presentCodes
     */
    private function deactivateMissingTaxonomy(
        Builder $query,
        array $presentCodes,
        CarbonInterface $synchronizedAt,
    ): void {
        $query->where('source_system', Vehicle::SOURCE_MKWD_IMS)
            ->when(
                $presentCodes !== [],
                fn (Builder $query) => $query->whereNotIn('source_code', $presentCodes),
            )
            ->update([
                'is_active' => false,
                'last_synced_at' => $synchronizedAt,
                'updated_at' => $synchronizedAt,
            ]);
    }
}
