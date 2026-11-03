<?php

namespace App\Actions\Odometer;

use App\Models\OdometerEntry;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordOdometerEntry
{
    public function handle(
        Vehicle $vehicle,
        User $recorder,
        int $reading,
        string $entryType,
        ?Trip $trip = null,
        ?string $notes = null,
        mixed $recordedAt = null,
    ): OdometerEntry {
        return DB::transaction(function () use ($vehicle, $recorder, $reading, $entryType, $trip, $notes, $recordedAt): OdometerEntry {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicle->id);

            if ($entryType === OdometerEntry::TYPE_MANUAL_CORRECTION
                && Trip::query()->where('vehicle_id', $vehicle->id)->where('status', Trip::STATUS_DISPATCHED)->exists()) {
                throw ValidationException::withMessages([
                    'current_mileage' => 'Mileage cannot be manually corrected while this vehicle has a dispatched trip.',
                ]);
            }

            $entry = OdometerEntry::query()->create([
                'vehicle_id' => $vehicle->id,
                'trip_id' => $trip?->id,
                'recorded_by' => $recorder->id,
                'entry_type' => $entryType,
                'previous_reading' => $vehicle->current_mileage,
                'reading' => $reading,
                'notes' => $notes,
                'recorded_at' => $recordedAt ?? now(),
            ]);

            $vehicle->update(['current_mileage' => $reading]);

            return $entry;
        }, 3);
    }
}
