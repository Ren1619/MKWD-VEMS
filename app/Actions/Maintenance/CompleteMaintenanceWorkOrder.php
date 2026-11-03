<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteMaintenanceWorkOrder
{
    /** @param array<string, mixed> $data */
    public function handle(MaintenanceWorkOrder $workOrder, User $user, array $data): void
    {
        DB::transaction(function () use ($workOrder, $user, $data): void {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($workOrder->vehicle_id);
            $locked = MaintenanceWorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);
            if (! in_array($locked->status, [MaintenanceWorkOrder::STATUS_OPEN, MaintenanceWorkOrder::STATUS_IN_PROGRESS], true)) {
                throw ValidationException::withMessages(['completed_at' => 'Only active work orders may be completed.']);
            }
            if ((int) $data['completion_mileage'] < $vehicle->current_mileage) {
                throw ValidationException::withMessages(['completion_mileage' => 'Completion mileage cannot be below the vehicle current mileage.']);
            }
            $locked->update([...$data, 'status' => MaintenanceWorkOrder::STATUS_COMPLETED, 'completed_by' => $user->id]);
            if ($vehicle->operational_status === Vehicle::STATUS_UNDER_MAINTENANCE) {
                $vehicle->update(['operational_status' => Vehicle::STATUS_AVAILABLE]);
            }
            if ($locked->maintenance_schedule_id) {
                $schedule = MaintenanceSchedule::query()->lockForUpdate()->findOrFail($locked->maintenance_schedule_id);
                $completedDate = Date::parse($data['completed_at'])->toDateString();
                $mileage = (int) $data['completion_mileage'];
                $schedule->update([
                    'last_completed_date' => $completedDate,
                    'last_completed_mileage' => $mileage,
                    'next_due_date' => $schedule->interval_days ? Date::parse($completedDate)->addDays($schedule->interval_days) : null,
                    'next_due_mileage' => $schedule->interval_km ? $mileage + $schedule->interval_km : null,
                ]);
            }
        });
    }
}
