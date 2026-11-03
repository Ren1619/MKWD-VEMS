<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
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
            'ams_asset_id' => (string) fake()->unique()->numberBetween(1, 100000),
            'property_tag_uuid' => fake()->uuid(),
            'property_number' => fake()->unique()->bothify('PROP-####-###'),
            'serial_number' => fake()->bothify('VEM-####-????'),
            'plate_number' => fake()->optional()->bothify('???-####'),
            'name' => fake()->randomElement(['Utility Vehicle', 'Service Motorcycle', 'Dump Truck']),
            'brand' => fake()->randomElement(['Toyota', 'Honda', 'Isuzu']),
            'model' => fake()->bothify('Model-##'),
            'vehicle_year' => fake()->numberBetween(2015, 2026),
            'location' => 'Motor Pool',
            'office_name' => fake()->optional()->company(),
            'acquisition_date' => fake()->date(),
            'acquisition_cost' => fake()->randomFloat(2, 100000, 3000000),
            'ams_asset_status' => 'active',
            'ams_condition_status' => 'serviceable',
            'ams_custody_status' => 'available',
            'current_mileage' => fake()->numberBetween(0, 150000),
            'fuel_type' => fake()->randomElement(['Diesel', 'Gasoline']),
            'operational_status' => Vehicle::STATUS_AVAILABLE,
            'maintenance_interval_km' => 5000,
            'is_present_in_ams' => true,
            'last_synced_at' => now(),
            'imported_at' => now(),
        ];
    }
}
