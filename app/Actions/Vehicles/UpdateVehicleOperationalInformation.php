<?php

namespace App\Actions\Vehicles;

use App\Actions\Odometer\RecordOdometerEntry;
use App\Models\OdometerEntry;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UpdateVehicleOperationalInformation
{
    public function __construct(private RecordOdometerEntry $recordOdometerEntry) {}

    /** @param array<string, mixed> $operationalData */
    public function handle(Vehicle $vehicle, array $operationalData, User $user): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $operationalData, $user): Vehicle {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicle->id);
            $trackedFields = [
                'current_mileage',
                'operational_status',
                'fuel_type',
                'maintenance_interval_km',
                'operational_notes',
            ];
            $before = $vehicle->only($trackedFields);
            $newMileage = (int) $operationalData['current_mileage'];

            if ($newMileage !== $vehicle->current_mileage
                && Trip::query()->where('vehicle_id', $vehicle->id)->where('status', Trip::STATUS_DISPATCHED)->exists()) {
                throw ValidationException::withMessages([
                    'current_mileage' => 'Mileage cannot be manually corrected while this vehicle has a dispatched trip.',
                ]);
            }

            $vehicle->update(Arr::only($operationalData, [
                'operational_status', 'fuel_type', 'maintenance_interval_km', 'operational_notes',
            ]));

            if ($newMileage !== $vehicle->current_mileage) {
                $this->recordOdometerEntry->handle(
                    $vehicle,
                    $user,
                    $newMileage,
                    OdometerEntry::TYPE_MANUAL_CORRECTION,
                    notes: (string) $operationalData['mileage_correction_reason'],
                );
            }

            $vehicle->refresh();
            $after = $vehicle->only($trackedFields);

            Log::info('Vehicle operational information updated.', [
                'vehicle_id' => $vehicle->id,
                'changed_fields' => array_keys(array_diff_assoc($after, $before)),
                'previous_mileage' => $before['current_mileage'],
                'current_mileage' => $after['current_mileage'],
                'previous_operational_status' => $before['operational_status'],
                'operational_status' => $after['operational_status'],
            ]);

            return $vehicle;
        }, 3);
    }
}
