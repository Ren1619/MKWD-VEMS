<?php

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    config()->set('services.mkwd_ims.base_url', 'http://ams.test');
    config()->set('services.mkwd_ims.api_token', 'test-token');
    config()->set('services.mkwd_ims.retry_delay_milliseconds', 0);
    Http::preventStrayRequests();
});

test('guests are redirected to login', function () {
    $this->get(route('vehicles.index'))->assertRedirect(route('login'));
});

test('authenticated users can view imported vehicle profiles', function () {
    $vehicle = Vehicle::factory()->create([
        'name' => 'Service Truck',
        'operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $vehicle->id)
            ->where('vehicles.data.0.name', 'Service Truck')
            ->where('summary.total', 1)
            ->where('summary.maintenance', 1)
            ->where('canManage', false)
        );

    Http::assertNothingSent();
});

test('ams vehicle list can be fetched for import with eligibility states', function () {
    Vehicle::factory()->create(['ams_asset_id' => '5']);

    Http::fake([
        'http://ams.test/api/vehicles' => Http::response([
            'data' => [
                amsVehicle(),
                amsVehicle(['id' => 6, 'name' => 'Available Truck']),
                amsVehicle(['id' => 7, 'name' => 'Disposed Van', 'lifecycle_status' => 'disposed']),
            ],
        ]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->get(route('vehicles.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/import')
            ->has('amsVehicles', 3)
            ->where('amsVehicles.0.is_imported', true)
            ->where('amsVehicles.0.is_eligible', false)
            ->where('amsVehicles.1.is_eligible', true)
            ->where('amsVehicles.2.is_eligible', false)
            ->where('sourceError', null)
        );
});

test('vehicle list supports operational filters', function () {
    Vehicle::factory()->create(['operational_status' => Vehicle::STATUS_AVAILABLE]);
    $maintenanceVehicle = Vehicle::factory()->create([
        'operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.index', ['operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $maintenanceVehicle->id)
            ->where('filters.operational_status', Vehicle::STATUS_UNDER_MAINTENANCE)
        );
});
