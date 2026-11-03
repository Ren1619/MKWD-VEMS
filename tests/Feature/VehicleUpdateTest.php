<?php

use App\Models\User;
use App\Models\Vehicle;

test('vehicle managers can update vems operational fields', function () {
    $vehicle = Vehicle::factory()->create([
        'property_number' => 'AMS-ORIGINAL',
        'brand' => 'Toyota',
        'current_mileage' => 10000,
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('vehicles.update', $vehicle), [
            'current_mileage' => 11000,
            'mileage_correction_reason' => 'Corrected after dashboard inspection.',
            'operational_status' => Vehicle::STATUS_IN_USE,
            'fuel_type' => Vehicle::FUEL_DIESEL,
            'maintenance_interval_km' => 7500,
            'operational_notes' => 'Assigned to operations.',
        ])
        ->assertRedirect();

    $vehicle->refresh();

    expect($vehicle)
        ->current_mileage->toBe(11000)
        ->operational_status->toBe(Vehicle::STATUS_IN_USE)
        ->fuel_type->toBe(Vehicle::FUEL_DIESEL)
        ->maintenance_interval_km->toBe(7500)
        ->property_number->toBe('AMS-ORIGINAL')
        ->brand->toBe('Toyota')
        ->ams_asset_status->toBe('active');
});

test('ams owned fields are rejected by the operational update endpoint', function () {
    $vehicle = Vehicle::factory()->create([
        'property_number' => 'AMS-ORIGINAL',
        'brand' => 'Toyota',
        'current_mileage' => 10000,
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->from(route('vehicles.show', $vehicle))
        ->patch(route('vehicles.update', $vehicle), [
            'current_mileage' => 11000,
            'operational_status' => Vehicle::STATUS_IN_USE,
            'property_number' => 'VEMS-CHANGED',
            'brand' => 'Changed Brand',
            'ams_asset_status' => 'disposed',
        ])
        ->assertRedirect(route('vehicles.show', $vehicle))
        ->assertSessionHasErrors([
            'property_number',
            'brand',
            'ams_asset_status',
        ]);

    expect($vehicle->refresh())
        ->current_mileage->toBe(10000)
        ->property_number->toBe('AMS-ORIGINAL')
        ->brand->toBe('Toyota')
        ->ams_asset_status->toBe('active');
});

test('invalid operational information is rejected', function (array $overrides, string $errorField) {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 10000]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('vehicles.update', $vehicle), [
            'current_mileage' => 10000,
            'operational_status' => Vehicle::STATUS_AVAILABLE,
            ...$overrides,
        ])
        ->assertSessionHasErrors($errorField);
})->with([
    'negative mileage' => [['current_mileage' => -1], 'current_mileage'],
    'non-numeric mileage' => [['current_mileage' => 'not-a-number'], 'current_mileage'],
    'unknown status' => [['operational_status' => 'flying'], 'operational_status'],
    'unknown fuel type' => [['fuel_type' => 'Steam'], 'fuel_type'],
    'zero maintenance interval' => [['maintenance_interval_km' => 0], 'maintenance_interval_km'],
    'excessive maintenance interval' => [['maintenance_interval_km' => 1000001], 'maintenance_interval_km'],
    'excessive notes' => [['operational_notes' => str_repeat('a', 5001)], 'operational_notes'],
]);

test('current mileage can decrease only as a documented correction', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 10000]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->from(route('vehicles.show', $vehicle))
        ->patch(route('vehicles.update', $vehicle), [
            'current_mileage' => 9999,
            'operational_status' => Vehicle::STATUS_AVAILABLE,
        ])
        ->assertRedirect(route('vehicles.show', $vehicle))
        ->assertSessionHasErrors('mileage_correction_reason');

    expect($vehicle->refresh()->current_mileage)->toBe(10000);

    $this->patch(route('vehicles.update', $vehicle), [
        'current_mileage' => 9999,
        'mileage_correction_reason' => 'The previous value was entered one kilometre too high.',
        'operational_status' => Vehicle::STATUS_AVAILABLE,
    ])->assertSessionHasNoErrors();

    expect($vehicle->refresh()->current_mileage)->toBe(9999);
});

test('a vehicle retired by ams cannot be returned to operational use', function () {
    $vehicle = Vehicle::factory()->create([
        'ams_asset_status' => 'disposed',
        'operational_status' => Vehicle::STATUS_RETIRED,
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('vehicles.update', $vehicle), [
            'current_mileage' => $vehicle->current_mileage,
            'operational_status' => Vehicle::STATUS_AVAILABLE,
        ])
        ->assertSessionHasErrors('operational_status');

    expect($vehicle->refresh()->operational_status)->toBe(Vehicle::STATUS_RETIRED);
});

test('unauthorized users cannot update vehicle operational information', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('vehicles.update', $vehicle), [
            'current_mileage' => $vehicle->current_mileage + 1,
            'operational_status' => Vehicle::STATUS_AVAILABLE,
        ])
        ->assertForbidden();
});
