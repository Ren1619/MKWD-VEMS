<?php

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

beforeEach(function () {
    Inertia::disableSsr();
    config()->set('services.mkwd_ims.base_url', 'http://ams.test');
    config()->set('services.mkwd_ims.api_token', 'test-token');
    config()->set('services.mkwd_ims.retry_delay_milliseconds', 0);
    Http::preventStrayRequests();
});

test('an eligible ams vehicle can be imported with operational fields', function () {
    Http::fake([
        'http://ams.test/api/vehicles' => Http::response(['data' => [amsVehicle()]]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('vehicles.store'), [
            'ams_asset_id' => '5',
            'current_mileage' => 12500,
            'operational_status' => Vehicle::STATUS_AVAILABLE,
            'fuel_type' => 'Gasoline',
            'maintenance_interval_km' => 5000,
            'operational_notes' => 'Assigned to field services.',
        ])
        ->assertRedirect();

    $vehicle = Vehicle::query()->sole();

    expect($vehicle)
        ->ams_asset_id->toBe('5')
        ->property_number->toBe('PROP-2018-003')
        ->name->toBe('Utility Motorcycle')
        ->current_mileage->toBe(12500)
        ->operational_status->toBe(Vehicle::STATUS_AVAILABLE)
        ->fuel_type->toBe('Gasoline')
        ->maintenance_interval_km->toBe(5000)
        ->operational_notes->toBe('Assigned to field services.')
        ->category_code->toBe('AST-TRN')
        ->subcategory_code->toBe('AST-TRN-MC')
        ->vehicle_category_id->not->toBeNull()
        ->vehicle_subcategory_id->not->toBeNull()
        ->last_synced_at->not->toBeNull()
        ->imported_at->not->toBeNull();
});

test('the same ams asset cannot be imported twice', function () {
    Vehicle::factory()->create(['ams_asset_id' => '5']);

    Http::fake([
        'http://ams.test/api/vehicles' => Http::response(['data' => [amsVehicle()]]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->from(route('vehicles.create'))
        ->post(route('vehicles.store'), [
            'ams_asset_id' => '5',
            'current_mileage' => 0,
            'operational_status' => Vehicle::STATUS_AVAILABLE,
        ])
        ->assertRedirect(route('vehicles.create'))
        ->assertSessionHasErrors('ams_asset_id');

    expect(Vehicle::query()->where('ams_asset_id', '5')->count())->toBe(1);
    Http::assertNothingSent();
});

test('disposed ams assets cannot be imported', function () {
    Http::fake([
        'http://ams.test/api/vehicles' => Http::response([
            'data' => [amsVehicle(['lifecycle_status' => 'disposed'])],
        ]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('vehicles.store'), [
            'ams_asset_id' => '5',
            'current_mileage' => 0,
            'operational_status' => Vehicle::STATUS_AVAILABLE,
        ])
        ->assertSessionHasErrors('ams_asset_id');

    expect(Vehicle::query()->count())->toBe(0);
});

test('unauthorized users cannot open or submit the vehicle import workflow', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('vehicles.create'))->assertForbidden();
    $this->actingAs($user)->post(route('vehicles.store'), [
        'ams_asset_id' => '5',
        'current_mileage' => 0,
        'operational_status' => Vehicle::STATUS_AVAILABLE,
    ])->assertForbidden();

    Http::assertNothingSent();
});
