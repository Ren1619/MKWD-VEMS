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

test('single vehicle sync updates cached ams data without overwriting operational fields', function () {
    $vehicle = Vehicle::factory()->create([
        'ams_asset_id' => '5',
        'name' => 'Old name',
        'brand' => 'Old brand',
        'current_mileage' => 42000,
        'operational_status' => Vehicle::STATUS_IN_USE,
        'operational_notes' => 'Keep this note.',
    ]);

    Http::fake([
        'http://ams.test/api/vehicles' => Http::response([
            'data' => [amsVehicle(['name' => 'Updated name', 'brand' => 'Honda'])],
        ]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('vehicles.sync', $vehicle))
        ->assertRedirect();

    $vehicle->refresh();

    expect($vehicle)
        ->name->toBe('Updated name')
        ->brand->toBe('Honda')
        ->current_mileage->toBe(42000)
        ->operational_status->toBe(Vehicle::STATUS_IN_USE)
        ->operational_notes->toBe('Keep this note.')
        ->category_code->toBe('AST-TRN')
        ->subcategory_code->toBe('AST-TRN-MC')
        ->vehicle_category_id->not->toBeNull()
        ->vehicle_subcategory_id->not->toBeNull()
        ->is_present_in_ams->toBeTrue()
        ->last_sync_error->toBeNull();

    expect(Vehicle::query()->where('ams_asset_id', '5')->count())->toBe(1);
});

test('ams failure preserves the existing vehicle and last known data', function () {
    $vehicle = Vehicle::factory()->create([
        'ams_asset_id' => '5',
        'name' => 'Last known name',
        'current_mileage' => 15000,
    ]);
    $lastSyncedAt = $vehicle->last_synced_at;

    Http::fake([
        'http://ams.test/api/vehicles' => Http::response(status: 503),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('vehicles.sync', $vehicle))
        ->assertRedirect()
        ->assertInertiaFlash('vehicleSyncWarning');

    $vehicle->refresh();

    $this->assertModelExists($vehicle);
    expect($vehicle)
        ->name->toBe('Last known name')
        ->current_mileage->toBe(15000);
    expect($vehicle->last_synced_at)->toEqual($lastSyncedAt);
    expect($vehicle->last_sync_error)->toBeNull();
});

test('disposed ams status retires the vehicle while preserving its history', function () {
    $vehicle = Vehicle::factory()->create([
        'ams_asset_id' => '5',
        'current_mileage' => 88000,
        'operational_status' => Vehicle::STATUS_AVAILABLE,
    ]);

    Http::fake([
        'http://ams.test/api/vehicles' => Http::response([
            'data' => [amsVehicle(['lifecycle_status' => 'disposed'])],
        ]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('vehicles.sync', $vehicle));

    expect($vehicle->refresh())
        ->ams_asset_status->toBe('disposed')
        ->operational_status->toBe(Vehicle::STATUS_RETIRED)
        ->current_mileage->toBe(88000);
});

test('a vehicle missing from the ams feed is flagged instead of deleted', function () {
    $vehicle = Vehicle::factory()->create(['ams_asset_id' => '99']);

    Http::fake([
        'http://ams.test/api/vehicles' => Http::response(['data' => [amsVehicle()]]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('vehicles.sync', $vehicle));

    $vehicle->refresh();

    $this->assertModelExists($vehicle);
    expect($vehicle)
        ->is_present_in_ams->toBeFalse()
        ->last_sync_error->not->toBeNull();
});

test('sync all updates present vehicles and flags missing vehicles', function () {
    $present = Vehicle::factory()->create(['ams_asset_id' => '5', 'name' => 'Old name']);
    $missing = Vehicle::factory()->create(['ams_asset_id' => '99']);

    Http::fake([
        'http://ams.test/api/vehicles' => Http::response(['data' => [amsVehicle(['name' => 'Fresh name'])]]),
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('vehicles.sync_all'))
        ->assertRedirect();

    expect($present->refresh())
        ->name->toBe('Fresh name')
        ->is_present_in_ams->toBeTrue()
        ->and($missing->refresh()->is_present_in_ams)->toBeFalse();
});

test('unauthorized users cannot synchronize vehicles', function () {
    $vehicle = Vehicle::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('vehicles.sync', $vehicle))->assertForbidden();
    $this->actingAs($user)->post(route('vehicles.sync_all'))->assertForbidden();

    Http::assertNothingSent();
});
