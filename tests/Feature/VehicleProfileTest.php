<?php

use App\Models\User;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
});

test('authenticated users can view complete ams and operational vehicle information', function () {
    $vehicle = Vehicle::factory()->create([
        'ams_asset_id' => '5',
        'property_number' => 'PROP-2018-003',
        'name' => 'Utility Motorcycle',
        'brand' => 'Honda',
        'model' => 'XR150L',
        'vehicle_year' => 2018,
        'subcategory_code' => 'AST-TRN-MC',
        'subcategory_name' => 'Motorcycles',
        'current_custodian_code' => 'EMP-001',
        'current_custodian' => 'Juan Dela Cruz',
        'current_mileage' => 48500,
        'fuel_type' => Vehicle::FUEL_GASOLINE,
        'operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE,
        'operational_notes' => 'Awaiting scheduled service.',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/show')
            ->where('vehicle.id', $vehicle->id)
            ->where('vehicle.ams_asset_id', '5')
            ->where('vehicle.property_number', 'PROP-2018-003')
            ->where('vehicle.name', 'Utility Motorcycle')
            ->where('vehicle.subcategory_name', 'Motorcycles')
            ->where('vehicle.current_custodian_code', 'EMP-001')
            ->where('vehicle.current_mileage', 48500)
            ->where('vehicle.fuel_type', Vehicle::FUEL_GASOLINE)
            ->where('vehicle.operational_status', Vehicle::STATUS_UNDER_MAINTENANCE)
            ->where('vehicle.operational_notes', 'Awaiting scheduled service.')
            ->where('operationalStatuses', Vehicle::operationalStatuses())
            ->where('fuelTypes', Vehicle::fuelTypes())
            ->where('canUpdate', false)
            ->where('canSync', false)
        );
});

test('vehicle managers receive profile actions', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canUpdate', true)
            ->where('canSync', true)
        );
});

test('profile safely provides null optional ams fields', function () {
    $vehicle = Vehicle::factory()->create([
        'property_tag_uuid' => null,
        'property_number' => null,
        'serial_number' => null,
        'plate_number' => null,
        'brand' => null,
        'model' => null,
        'vehicle_year' => null,
        'description' => null,
        'office_name' => null,
        'current_custodian' => null,
        'current_borrower' => null,
        'ams_created_at' => null,
        'ams_updated_at' => null,
        'last_synced_at' => null,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('vehicle.plate_number', null)
            ->where('vehicle.office_name', null)
            ->where('vehicle.last_synced_at', null)
        );
});

test('guests cannot view vehicle profiles', function () {
    $vehicle = Vehicle::factory()->create();

    $this->get(route('vehicles.show', $vehicle))
        ->assertRedirect(route('login'));
});

test('unknown vehicle profiles return not found', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.show', 999999))
        ->assertNotFound();
});
