<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Date;

class CreateMaintenanceSchedule
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): MaintenanceSchedule
    {
        $vehicle = Vehicle::query()->findOrFail((int) $data['vehicle_id']);
        $lastDate = $data['last_completed_date'] ?? (($data['interval_days'] ?? null) ? today()->toDateString() : null);
        $lastMileage = $data['last_completed_mileage'] ?? (($data['interval_km'] ?? null) ? $vehicle->current_mileage : null);

        return MaintenanceSchedule::query()->create([
            ...$data,
            'created_by' => $user->id,
            'last_completed_date' => $lastDate,
            'last_completed_mileage' => $lastMileage,
            'next_due_date' => $lastDate && ($data['interval_days'] ?? null) ? Date::parse($lastDate)->addDays((int) $data['interval_days']) : null,
            'next_due_mileage' => $lastMileage !== null && ($data['interval_km'] ?? null) ? (int) $lastMileage + (int) $data['interval_km'] : null,
            'is_active' => true,
        ]);
    }
}
