<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenanceWorkOrder;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartMaintenanceWorkOrder
{
    public function handle(MaintenanceWorkOrder $workOrder): void
    {
        DB::transaction(function () use ($workOrder): void {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($workOrder->vehicle_id);
            $locked = MaintenanceWorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);
            if ($locked->status !== MaintenanceWorkOrder::STATUS_OPEN) {
                throw ValidationException::withMessages(['status' => 'Only open work orders may be started.']);
            }
            if (in_array($vehicle->operational_status, [Vehicle::STATUS_IN_USE, Vehicle::STATUS_RETIRED], true)) {
                throw ValidationException::withMessages(['status' => 'This vehicle cannot enter maintenance in its current status.']);
            }
            $locked->update(['status' => MaintenanceWorkOrder::STATUS_IN_PROGRESS, 'started_at' => now()]);
            $vehicle->update(['operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE]);
        });
    }
}
