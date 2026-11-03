<?php

namespace App\Actions\Trips;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideTrip
{
    /** @param array{decision: string, vehicle_id?: ?int, driver_id?: ?int, decision_notes?: ?string} $data */
    public function handle(Trip $trip, User $manager, array $data): void
    {
        DB::transaction(function () use ($trip, $manager, $data): void {
            $trip = Trip::query()->lockForUpdate()->findOrFail($trip->id);

            if ($trip->status !== Trip::STATUS_PENDING) {
                throw ValidationException::withMessages(['decision' => 'Only pending trip requests can be decided.']);
            }

            if ($data['decision'] === Trip::STATUS_REJECTED) {
                $trip->update([
                    'status' => Trip::STATUS_REJECTED,
                    'decided_by' => $manager->id,
                    'decided_at' => now(),
                    'decision_notes' => $data['decision_notes'] ?? null,
                ]);

                return;
            }

            $vehicleId = $data['vehicle_id'] ?? null;
            $driverId = $data['driver_id'] ?? null;

            if ($vehicleId === null || $driverId === null) {
                throw ValidationException::withMessages(['vehicle_id' => 'A vehicle and driver are required for approval.']);
            }

            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicleId);
            $driver = Driver::query()->lockForUpdate()->findOrFail($driverId);

            $this->ensureVehicleAndDriverAreAvailable($trip, $vehicle, $driver);

            $trip->update([
                'status' => Trip::STATUS_APPROVED,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'decided_by' => $manager->id,
                'decided_at' => now(),
                'decision_notes' => $data['decision_notes'] ?? null,
            ]);
        }, 3);
    }

    private function ensureVehicleAndDriverAreAvailable(Trip $trip, Vehicle $vehicle, Driver $driver): void
    {
        if (! $vehicle->is_present_in_ams || in_array($vehicle->operational_status, [
            Vehicle::STATUS_UNDER_MAINTENANCE, Vehicle::STATUS_OUT_OF_SERVICE, Vehicle::STATUS_RETIRED,
        ], true) || Vehicle::amsStatusRequiresRetirement($vehicle->ams_asset_status)) {
            throw ValidationException::withMessages(['vehicle_id' => 'This vehicle is not available for dispatch.']);
        }

        if (! $driver->isEligibleToDrive() || Date::parse($driver->license_expiry)->endOfDay()->lt($trip->planned_return)) {
            throw ValidationException::withMessages(['driver_id' => 'The driver must be eligible and licensed through the planned return.']);
        }

        $tripConflict = Trip::query()
            ->whereIn('status', [Trip::STATUS_APPROVED, Trip::STATUS_DISPATCHED])
            ->where(fn (Builder $query) => $query->where('vehicle_id', $vehicle->id)->orWhere('driver_id', $driver->id))
            ->where('planned_departure', '<', $trip->planned_return)
            ->where('planned_return', '>', $trip->planned_departure)
            ->lockForUpdate()
            ->exists();

        if ($tripConflict) {
            throw ValidationException::withMessages(['vehicle_id' => 'The selected vehicle or driver has an overlapping approved trip.']);
        }

        $assignmentConflict = VehicleDriverAssignment::query()
            ->where('start_date', '<=', Date::parse($trip->planned_return)->toDateString())
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', Date::parse($trip->planned_departure)->toDateString()))
            ->where(function (Builder $query) use ($vehicle, $driver): void {
                $query->where(fn (Builder $query) => $query->where('vehicle_id', $vehicle->id)->where('driver_id', '!=', $driver->id))
                    ->orWhere(fn (Builder $query) => $query->where('driver_id', $driver->id)->where('vehicle_id', '!=', $vehicle->id));
            })
            ->lockForUpdate()
            ->exists();

        if ($assignmentConflict) {
            throw ValidationException::withMessages(['vehicle_id' => 'The vehicle or driver is assigned to a different counterpart during these dates.']);
        }
    }
}
