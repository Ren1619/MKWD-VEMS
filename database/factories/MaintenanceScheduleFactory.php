<?php

namespace Database\Factories;

use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceSchedule>
 */
class MaintenanceScheduleFactory extends Factory
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
            'created_by' => User::factory(),
            'service_type' => 'Periodic service',
            'description' => 'Oil, filters, fluids, and safety inspection.',
            'interval_days' => 180,
            'interval_km' => 5000,
            'last_completed_date' => today(),
            'last_completed_mileage' => 10000,
            'next_due_date' => today()->addDays(180),
            'next_due_mileage' => 15000,
            'is_active' => true,
        ];
    }
}
