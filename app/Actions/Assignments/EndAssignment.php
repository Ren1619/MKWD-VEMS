<?php

namespace App\Actions\Assignments;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EndAssignment
{
    public function handle(VehicleDriverAssignment $assignment, string $endDate): void
    {
        DB::transaction(function () use ($assignment, $endDate): void {
            Vehicle::query()->lockForUpdate()->findOrFail($assignment->vehicle_id);
            Driver::query()->lockForUpdate()->findOrFail($assignment->driver_id);
            $locked = VehicleDriverAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if (! $locked->getCanEndAttribute()
                || $endDate < Date::parse($locked->start_date)->toDateString()
                || ($locked->end_date !== null && $endDate > Date::parse($locked->end_date)->toDateString())) {
                throw ValidationException::withMessages(['end_date' => 'Only a current assignment can be ended, within its existing dates. Closed history cannot be changed.']);
            }

            $locked->update(['end_date' => $endDate, 'closed_at' => now()]);
        }, 3);
    }
}
