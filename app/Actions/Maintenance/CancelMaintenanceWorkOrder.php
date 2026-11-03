<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelMaintenanceWorkOrder
{
    public function handle(MaintenanceWorkOrder $workOrder, User $user, string $reason): void
    {
        DB::transaction(function () use ($workOrder, $user, $reason): void {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($workOrder->vehicle_id);
            $locked = MaintenanceWorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);
            if (! in_array($locked->status, [MaintenanceWorkOrder::STATUS_OPEN, MaintenanceWorkOrder::STATUS_IN_PROGRESS], true)) {
                throw ValidationException::withMessages(['cancellation_reason' => 'Only active work orders may be cancelled.']);
            }
            $locked->update(['status' => MaintenanceWorkOrder::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancellation_reason' => $reason]);
            if ($vehicle->operational_status === Vehicle::STATUS_UNDER_MAINTENANCE) {
                $vehicle->update(['operational_status' => Vehicle::STATUS_AVAILABLE]);
            }
        });
    }
}
