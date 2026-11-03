<?php

use App\Models\Driver;
use App\Models\FuelLog;
use App\Models\MaintenanceSchedule;
use App\Models\OdometerEntry;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(10, 0));
});

test('dashboard reports current fleet operations and period activity', function () {
    $available = Vehicle::factory()->create(['operational_status' => Vehicle::STATUS_AVAILABLE]);
    Vehicle::factory()->create(['operational_status' => Vehicle::STATUS_AVAILABLE]);
    Vehicle::factory()->create(['operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE]);
    Vehicle::factory()->create(['operational_status' => Vehicle::STATUS_RETIRED]);
    VehicleDriverAssignment::factory()->create(['vehicle_id' => $available->id, 'driver_id' => Driver::factory()->eligible(), 'start_date' => '2026-09-01', 'end_date' => '2026-09-08']);

    MaintenanceSchedule::factory()->create(['vehicle_id' => $available->id, 'next_due_date' => '2026-09-08', 'next_due_mileage' => null]);
    MaintenanceSchedule::factory()->create(['vehicle_id' => $available->id, 'next_due_date' => '2026-09-07', 'next_due_mileage' => null]);
    Trip::factory()->create(['status' => Trip::STATUS_COMPLETED, 'planned_departure' => '2026-09-05 08:00:00', 'planned_return' => '2026-09-05 10:00:00']);
    Trip::factory()->create(['status' => Trip::STATUS_PENDING, 'planned_departure' => '2026-09-08 12:00:00', 'planned_return' => '2026-09-08 13:00:00']);
    Trip::factory()->create(['status' => Trip::STATUS_COMPLETED, 'planned_departure' => '2026-08-30 08:00:00', 'planned_return' => '2026-08-30 10:00:00']);
    FuelLog::factory()->create(['vehicle_id' => $available->id, 'odometer_entry_id' => OdometerEntry::factory()->create(['vehicle_id' => $available->id])->id, 'quantity_liters' => 40, 'total_cost' => 2400, 'distance_since_previous' => null, 'refueled_at' => '2026-09-01 08:00:00']);
    FuelLog::factory()->create(['vehicle_id' => $available->id, 'odometer_entry_id' => OdometerEntry::factory()->create(['vehicle_id' => $available->id])->id, 'quantity_liters' => 50, 'total_cost' => 3000, 'distance_since_previous' => 500, 'refueled_at' => '2026-09-08 08:00:00']);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('dashboard')
            ->where('filters.date_from', '2026-09-01')->where('filters.date_to', '2026-09-08')
            ->where('availability.available', 2)->where('availability.operable', 3)->where('availability.rate', 66.7)
            ->where('activeAssignments', 1)->where('trips.total', 2)->where('trips.byStatus.completed', 1)->where('trips.byStatus.pending', 1)
            ->where('maintenance.due', 1)->where('maintenance.overdue', 1)
            ->where('fuel.quantity', 90)->where('fuel.cost', 5400)->where('fuel.distance', 500)->where('fuel.consumption', 10)
            ->where('operationalStatuses.0.status', Vehicle::STATUS_AVAILABLE)->where('operationalStatuses.0.count', 2)
            ->has('trips.recent', 2));
});

test('dashboard date filter limits trips and fuel usage', function () {
    $vehicle = Vehicle::factory()->create();
    Trip::factory()->create(['planned_departure' => '2026-09-01 08:00:00', 'planned_return' => '2026-09-01 09:00:00']);
    Trip::factory()->create(['planned_departure' => '2026-09-08 08:00:00', 'planned_return' => '2026-09-08 09:00:00']);
    FuelLog::factory()->create(['vehicle_id' => $vehicle->id, 'odometer_entry_id' => OdometerEntry::factory()->create(['vehicle_id' => $vehicle->id])->id, 'quantity_liters' => 30, 'total_cost' => 1800, 'refueled_at' => '2026-09-01 08:00:00']);
    FuelLog::factory()->create(['vehicle_id' => $vehicle->id, 'odometer_entry_id' => OdometerEntry::factory()->create(['vehicle_id' => $vehicle->id])->id, 'quantity_liters' => 20, 'total_cost' => 1200, 'refueled_at' => '2026-09-08 08:00:00']);

    $this->actingAs(User::factory()->create())->get(route('dashboard', ['date_from' => '2026-09-08', 'date_to' => '2026-09-08']))
        ->assertInertia(fn (Assert $page) => $page->where('trips.total', 1)->where('fuel.quantity', 20)->where('fuel.cost', 1200));
});

test('dashboard validates its report period', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard', ['date_from' => '2026-09-08', 'date_to' => '2026-09-07']))
        ->assertSessionHasErrors('date_to');
});

test('guests cannot view operational reports', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
