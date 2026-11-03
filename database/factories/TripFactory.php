<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Trip> */
class TripFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'requested_by' => User::factory(),
            'status' => Trip::STATUS_PENDING,
            'destination' => fake()->city(),
            'purpose' => fake()->sentence(),
            'passengers' => null,
            'planned_departure' => now()->addDay(),
            'planned_return' => now()->addDay()->addHours(2),
        ];
    }
}
