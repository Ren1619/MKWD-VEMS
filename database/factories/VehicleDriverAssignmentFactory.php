<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleDriverAssignment> */
class VehicleDriverAssignmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory()->eligible(),
            'start_date' => today()->toDateString(),
            'end_date' => null,
            'assignment_type' => 'primary',
        ];
    }
}
