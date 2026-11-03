<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleSubcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleSubcategory>
 */
class VehicleSubcategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_category_id' => VehicleCategory::factory(),
            'source_system' => Vehicle::SOURCE_MKWD_IMS,
            'source_code' => fake()->unique()->bothify('SUB-###'),
            'name' => fake()->words(2, true),
            'is_active' => true,
            'last_synced_at' => now(),
        ];
    }
}
