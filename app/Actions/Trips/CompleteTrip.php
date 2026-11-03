<?php

namespace App\Actions\Trips;

use App\Actions\Odometer\RecordOdometerEntry;
use App\Models\OdometerEntry;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteTrip
{
    public function __construct(private RecordOdometerEntry $recordOdometerEntry) {}

    public function handle(Trip $trip, User $manager, string $actualReturn, int $endOdometer): void
    {
        DB::transaction(function () use ($trip, $manager, $actualReturn, $endOdometer): void {
            $trip = Trip::query()->lockForUpdate()->findOrFail($trip->id);

            if ($trip->status !== Trip::STATUS_DISPATCHED || $trip->vehicle_id === null || $trip->actual_departure === null || $trip->start_odometer === null) {
                throw ValidationException::withMessages(['actual_return' => 'Only a dispatched trip can be returned.']);
            }

            if ($actualReturn < Date::parse($trip->actual_departure)->format('Y-m-d\TH:i')) {
                throw ValidationException::withMessages(['actual_return' => 'The return time cannot be before departure.']);
            }

            if ($endOdometer < $trip->start_odometer) {
                throw ValidationException::withMessages(['end_odometer' => 'The end odometer cannot be lower than the start odometer.']);
            }

            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($trip->vehicle_id);
            $trip->update([
                'status' => Trip::STATUS_COMPLETED,
                'actual_return' => $actualReturn,
                'end_odometer' => $endOdometer,
                'returned_by' => $manager->id,
            ]);
            $this->recordOdometerEntry->handle(
                $vehicle,
                $manager,
                $endOdometer,
                OdometerEntry::TYPE_TRIP_RETURN,
                $trip,
                'Return reading for '.$trip->reference_number.'.',
                $actualReturn,
            );

            $vehicleUpdates = [];

            if ($vehicle->operational_status === Vehicle::STATUS_IN_USE) {
                $vehicleUpdates['operational_status'] = Vehicle::STATUS_AVAILABLE;
            }

            if ($vehicleUpdates !== []) {
                $vehicle->update($vehicleUpdates);
            }
        }, 3);
    }
}
