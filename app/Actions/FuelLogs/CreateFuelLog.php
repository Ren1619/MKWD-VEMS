<?php

namespace App\Actions\FuelLogs;

use App\Actions\Odometer\RecordOdometerEntry;
use App\Models\FuelLog;
use App\Models\OdometerEntry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateFuelLog
{
    public function __construct(private RecordOdometerEntry $recordOdometerEntry) {}

    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): FuelLog
    {
        return DB::transaction(function () use ($user, $data): FuelLog {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail((int) $data['vehicle_id']);
            $previous = FuelLog::query()->where('vehicle_id', $vehicle->id)->latest('refueled_at')->latest('id')->first();
            $odometer = (int) $data['odometer'];

            if ($odometer < $vehicle->current_mileage) {
                throw ValidationException::withMessages(['odometer' => 'Refueling odometer cannot be below the vehicle current mileage. Use an authorized mileage correction if the current reading is wrong.']);
            }
            if ($previous && Date::parse($previous->refueled_at)->gt(Date::parse($data['refueled_at']))) {
                throw ValidationException::withMessages(['refueled_at' => 'Refueling entries must be recorded in chronological order so consumption remains accurate.']);
            }

            $distance = $previous ? $odometer - $previous->odometer : null;
            $quantity = (float) $data['quantity_liters'];
            $cost = (float) $data['total_cost'];
            $entry = $this->recordOdometerEntry->handle(
                $vehicle,
                $user,
                $odometer,
                OdometerEntry::TYPE_REFUEL,
                notes: 'Odometer recorded with fuel log'.(($data['receipt_reference'] ?? null) ? ' '.$data['receipt_reference'] : ''),
                recordedAt: $data['refueled_at'],
            );

            return FuelLog::query()->create([
                ...$data,
                'odometer_entry_id' => $entry->id,
                'recorded_by' => $user->id,
                'distance_since_previous' => $distance,
                'consumption_km_per_liter' => $distance !== null ? $distance / $quantity : null,
                'cost_per_km' => $distance > 0 ? $cost / $distance : null,
                'price_per_liter' => $cost / $quantity,
            ]);
        }, 3);
    }
}
