<?php

namespace Database\Factories;

use App\Models\FuelLog;
use App\Models\OdometerEntry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelLog>
 */
class FuelLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'odometer_entry_id' => OdometerEntry::factory(),
            'recorded_by' => User::factory(),
            'quantity_liters' => 40,
            'total_cost' => 2400,
            'odometer' => 10000,
            'refueled_at' => now(),
            'station' => 'Main fuel station',
            'price_per_liter' => 60,
        ];
    }
}
