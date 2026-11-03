<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleCategory>
 */
class VehicleCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_system' => Vehicle::SOURCE_MKWD_IMS,
            'source_code' => fake()->unique()->bothify('CAT-###'),
            'name' => fake()->words(2, true),
            'is_active' => true,
            'last_synced_at' => now(),
        ];
    }
}
