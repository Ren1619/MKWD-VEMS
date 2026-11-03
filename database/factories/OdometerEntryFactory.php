<?php

namespace Database\Factories;

use App\Models\OdometerEntry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OdometerEntry> */
class OdometerEntryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $previous = fake()->numberBetween(0, 100000);

        return [
            'vehicle_id' => Vehicle::factory(),
            'trip_id' => null,
            'recorded_by' => User::factory()->vehicleManager(),
            'entry_type' => OdometerEntry::TYPE_MANUAL_CORRECTION,
            'previous_reading' => $previous,
            'reading' => $previous + fake()->numberBetween(1, 500),
            'notes' => fake()->sentence(),
            'recorded_at' => now(),
        ];
    }
}
