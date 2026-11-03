<?php

namespace Database\Factories;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceWorkOrder>
 */
class MaintenanceWorkOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('WO-####-######'),
            'vehicle_id' => Vehicle::factory(),
            'opened_by' => User::factory(),
            'status' => MaintenanceWorkOrder::STATUS_OPEN,
            'title' => 'Inspect and service vehicle',
            'instructions' => 'Diagnose the reported condition and complete required service.',
            'scheduled_start' => today(),
        ];
    }
}
