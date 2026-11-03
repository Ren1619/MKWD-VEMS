<?php

namespace App\Actions\Assignments;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignDriver
{
    /** @param array{vehicle_id: int, driver_id: int, start_date: string, end_date?: ?string, assignment_type: string} $data */
    public function handle(array $data): VehicleDriverAssignment
    {
        return DB::transaction(function () use ($data): VehicleDriverAssignment {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($data['vehicle_id']);
            $driver = Driver::query()->lockForUpdate()->findOrFail($data['driver_id']);

            if (! $vehicle->is_present_in_ams || $vehicle->operational_status === Vehicle::STATUS_RETIRED
                || Vehicle::amsStatusRequiresRetirement($vehicle->ams_asset_status)) {
                throw ValidationException::withMessages(['vehicle_id' => 'Missing or retired vehicles cannot receive new assignments.']);
            }

            if (! $driver->isEligibleToDrive()) {
                throw ValidationException::withMessages(['driver_id' => implode(' ', $driver->eligibilityReasons())]);
            }

            $licenseExpiry = Date::parse($driver->license_expiry)->toDateString();
            if ($licenseExpiry < ($data['end_date'] ?? $data['start_date'])) {
                throw ValidationException::withMessages(['driver_id' => 'The license must remain valid through the selected assignment dates.']);
            }

            $conflict = VehicleDriverAssignment::query()
                ->where(fn (Builder $query) => $query->where('vehicle_id', $vehicle->id)->orWhere('driver_id', $driver->id))
                ->when($data['end_date'] ?? null, fn (Builder $query, string $end) => $query->where('start_date', '<=', $end))
                ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $data['start_date']))
                ->lockForUpdate()
                ->first();

            if ($conflict !== null) {
                throw ValidationException::withMessages([
                    $conflict->vehicle_id === $vehicle->id ? 'vehicle_id' : 'driver_id' => 'An assignment already overlaps these dates. End the existing assignment or choose different dates.',
                ]);
            }

            return VehicleDriverAssignment::query()->create($data);
        }, 3);
    }
}
