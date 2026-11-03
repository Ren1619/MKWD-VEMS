<?php

use App\Models\Driver;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(10, 0));
});

test('trip and fuel creation modals receive defaults from their index pages', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 12345]);
    $manager = User::factory()->vehicleManager()->create();

    $this->actingAs($manager)->get(route('trips.index'))
        ->assertInertia(fn (Assert $page) => $page->component('trips/index')
            ->where('defaultDeparture', '2026-09-08T11:00')->where('defaultReturn', '2026-09-08T12:00'));

    $this->get(route('fuel-logs.index'))
        ->assertInertia(fn (Assert $page) => $page->component('fuel-logs/index')
            ->where('now', '2026-09-08T10:00')->where('vehicles.0.id', $vehicle->id)
            ->where('vehicles.0.current_mileage', 12345));
});

test('maintenance index supplies all three modal workflows', function () {
    $manager = User::factory()->vehicleManager()->create();
    $vehicle = Vehicle::factory()->create();
    $schedule = MaintenanceSchedule::factory()->create(['vehicle_id' => $vehicle->id]);
    $request = MaintenanceRequest::factory()->create(['vehicle_id' => $vehicle->id, 'status' => MaintenanceRequest::STATUS_APPROVED]);

    $this->actingAs($manager)->get(route('maintenance.index'))
        ->assertInertia(fn (Assert $page) => $page->component('maintenance/index')
            ->where('vehicles.0.id', $vehicle->id)
            ->where('schedules.0.id', $schedule->id)
            ->where('approvedRequests.0.id', $request->id));
});

test('assignment index supplies modal options and preserves preselection', function () {
    $vehicle = Vehicle::factory()->create();
    $driver = Driver::factory()->eligible()->create();

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->get(route('assignments.index', ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'assign' => 1]))
        ->assertInertia(fn (Assert $page) => $page->component('assignments/index')
            ->where('filters.vehicle_id', $vehicle->id)->where('filters.driver_id', $driver->id)
            ->where('filters.assign', true)->where('vehicles.0.id', $vehicle->id)->where('drivers.0.id', $driver->id));
});

test('non managers do not receive manager-only modal options', function () {
    Vehicle::factory()->create();
    Driver::factory()->eligible()->create();

    $this->actingAs(User::factory()->create())->get(route('assignments.index'))
        ->assertInertia(fn (Assert $page) => $page->where('canManage', false)->has('vehicles', 0)->has('drivers', 0));
});
