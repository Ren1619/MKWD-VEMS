<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Driver> */
class DriverFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'hris_employee_id' => fake()->unique()->uuid(),
            'employee_number' => fake()->unique()->bothify('EMP-####'),
            'employee_name' => fake()->name(),
            'employee_email' => fake()->safeEmail(),
            'employee_phone' => '09171234567',
            'department' => 'Operations',
            'position' => 'Driver',
            'license_number' => fake()->unique()->bothify('N##-##-######'),
            'license_type' => 'professional',
            'license_expiry' => today()->addYear(),
            'status' => Driver::STATUS_INACTIVE,
            'driving_authorized' => false,
            'is_present_in_hris' => true,
            'last_synced_at' => now(),
        ];
    }

    public function eligible(): static
    {
        return $this->state(fn (): array => [
            'status' => Driver::STATUS_ACTIVE,
            'driving_authorized' => true,
        ]);
    }

    public function outsourced(): static
    {
        return $this->state(fn (): array => [
            'source_type' => Driver::SOURCE_OUTSOURCED,
            'hris_employee_id' => null,
            'is_present_in_hris' => false,
            'last_synced_at' => null,
        ]);
    }
}
