<?php

namespace App\Actions\Trips;

use App\Actions\Odometer\RecordOdometerEntry;
use App\Models\Driver;
use App\Models\OdometerEntry;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispatchTrip
{
    public function __construct(private RecordOdometerEntry $recordOdometerEntry) {}

    public function handle(Trip $trip, User $manager, string $actualDeparture, int $startOdometer): void
    {
        DB::transaction(function () use ($trip, $manager, $actualDeparture, $startOdometer): void {
            $trip = Trip::query()->lockForUpdate()->findOrFail($trip->id);

            if ($trip->status !== Trip::STATUS_APPROVED || $trip->vehicle_id === null || $trip->driver_id === null) {
                throw ValidationException::withMessages(['actual_departure' => 'Only approved trips can depart.']);
            }

            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($trip->vehicle_id);
            $driver = Driver::query()->lockForUpdate()->findOrFail($trip->driver_id);

            if (! $driver->isEligibleToDrive() || Date::parse($driver->license_expiry)->lt(today())) {
                throw ValidationException::withMessages(['actual_departure' => 'The assigned driver is no longer eligible to drive.']);
            }

            if (! $vehicle->is_present_in_ams || in_array($vehicle->operational_status, [
                Vehicle::STATUS_IN_USE,
                Vehicle::STATUS_UNDER_MAINTENANCE, Vehicle::STATUS_OUT_OF_SERVICE, Vehicle::STATUS_RETIRED,
            ], true)) {
                throw ValidationException::withMessages(['actual_departure' => 'The assigned vehicle is no longer available for dispatch.']);
            }

            if ($startOdometer < $vehicle->current_mileage) {
                throw ValidationException::withMessages(['start_odometer' => 'The start odometer cannot be lower than the vehicle current mileage.']);
            }

            $trip->update([
                'status' => Trip::STATUS_DISPATCHED,
                'actual_departure' => $actualDeparture,
                'start_odometer' => $startOdometer,
                'dispatched_by' => $manager->id,
            ]);
            $this->recordOdometerEntry->handle(
                $vehicle,
                $manager,
                $startOdometer,
                OdometerEntry::TYPE_TRIP_DEPARTURE,
                $trip,
                'Departure reading for '.$trip->reference_number.'.',
                $actualDeparture,
            );
            $vehicle->update(['operational_status' => Vehicle::STATUS_IN_USE]);
        }, 3);
    }
}
