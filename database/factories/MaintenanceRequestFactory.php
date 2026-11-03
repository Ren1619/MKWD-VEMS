<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('MR-####-######'),
            'vehicle_id' => Vehicle::factory(),
            'requested_by' => User::factory(),
            'priority' => 'normal',
            'status' => MaintenanceRequest::STATUS_PENDING,
            'subject' => 'Unusual engine noise',
            'description' => 'A new noise was noticed during operation.',
        ];
    }
}
