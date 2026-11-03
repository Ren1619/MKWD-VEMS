<?php

use App\Integrations\MkwdIms\MkwdImsException;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleSubcategory;
use App\Services\Vehicles\SynchronizeMkwdVehicles;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.mkwd_ims.base_url', 'http://mkwd-ims.test');
    config()->set('services.mkwd_ims.api_token', 'integration-test-token');
    config()->set('services.mkwd_ims.retry_attempts', 3);
    config()->set('services.mkwd_ims.retry_delay_milliseconds', 0);
    Http::preventStrayRequests();
});

test('the client sends the bearer token and accept header', function () {
    Http::fake([
        'http://mkwd-ims.test/api/vehicles' => Http::response(['data' => [amsVehicle()]]),
    ]);

    resolve(SynchronizeMkwdVehicles::class)->handle();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://mkwd-ims.test/api/vehicles'
        && $request->hasHeader('Authorization', 'Bearer integration-test-token')
        && $request->hasHeader('Accept', 'application/json'));
});

test('a successful snapshot creates vehicles and reconciles dynamic taxonomy', function () {
    Http::fake([
        'http://mkwd-ims.test/api/vehicles' => Http::response(['data' => [amsVehicle()]]),
    ]);

    $result = resolve(SynchronizeMkwdVehicles::class)->handle();
    $vehicle = Vehicle::query()->with(['category', 'subcategory'])->sole();

    expect($result->toArray())->toBe([
        'created' => 1,
        'updated' => 0,
        'unchanged' => 0,
        'archived' => 0,
        'reactivated' => 0,
        'failed' => 0,
    ])->and($vehicle)
        ->source_system->toBe(Vehicle::SOURCE_MKWD_IMS)
        ->ams_asset_id->toBe('5')
        ->current_custodian_code->toBe('EMP-001')
        ->current_custodian->toBe('Juan Dela Cruz')
        ->is_present_in_ams->toBeTrue()
        ->last_synced_at->not->toBeNull()
        ->and($vehicle->category?->source_code)->toBe('AST-TRN')
        ->and($vehicle->subcategory?->source_code)->toBe('AST-TRN-MC');
});

test('a subsequent snapshot updates vehicles and renamed taxonomy without duplicates', function () {
    Http::fakeSequence()
        ->push(['data' => [amsVehicle()]], 200)
        ->push(['data' => [amsVehicle([
            'brand' => 'Yamaha',
            'category' => ['code' => 'AST-TRN', 'name' => 'Fleet Equipment'],
            'subcategory' => ['code' => 'AST-TRN-MC', 'name' => 'Service Motorcycles'],
        ])]], 200);

    $synchronizer = resolve(SynchronizeMkwdVehicles::class);
    $synchronizer->handle();
    $result = $synchronizer->handle();

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and(Vehicle::query()->count())->toBe(1)
        ->and(VehicleCategory::query()->sole()->name)->toBe('Fleet Equipment')
        ->and(VehicleSubcategory::query()->sole()->name)->toBe('Service Motorcycles')
        ->and(Vehicle::query()->sole()->brand)->toBe('Yamaha');
});

test('missing vehicles are archived only after a complete successful snapshot', function () {
    Http::fakeSequence()
        ->push(['data' => [amsVehicle(), amsVehicle(['id' => 6, 'property_tag_uuid' => null])]], 200)
        ->push(['data' => [amsVehicle()]], 200);

    $synchronizer = resolve(SynchronizeMkwdVehicles::class);
    $synchronizer->handle();
    $result = $synchronizer->handle();

    expect($result->archived)->toBe(1)
        ->and(Vehicle::query()->where('ams_asset_id', '6')->sole()->is_present_in_ams)->toBeFalse()
        ->and(Vehicle::query()->count())->toBe(2);
});

test('an archived vehicle is reactivated when it returns', function () {
    Http::fakeSequence()
        ->push(['data' => [amsVehicle()]], 200)
        ->push(['data' => []], 200)
        ->push(['data' => [amsVehicle()]], 200);

    $synchronizer = resolve(SynchronizeMkwdVehicles::class);
    $synchronizer->handle();
    $synchronizer->handle();
    $result = $synchronizer->handle();

    expect($result->reactivated)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(Vehicle::query()->sole()->is_present_in_ams)->toBeTrue()
        ->and(Vehicle::query()->sole()->last_sync_error)->toBeNull();
});

test('unauthorized source responses fail without retries or local changes', function (int $status) {
    $vehicle = Vehicle::factory()->create([
        'name' => 'Last known vehicle',
        'last_sync_error' => null,
    ]);
    $original = $vehicle->refresh()->getAttributes();

    Http::fake([
        'http://mkwd-ims.test/api/vehicles' => Http::response(status: $status),
    ]);

    expect(fn () => resolve(SynchronizeMkwdVehicles::class)->handle())
        ->toThrow(MkwdImsException::class);

    expect($vehicle->refresh()->getAttributes())->toBe($original);
    Http::assertSentCount(1);
})->with([401, 403]);

test('temporary source responses use the configured retry behavior', function (int $status) {
    Http::fakeSequence()
        ->pushStatus($status)
        ->push(['data' => [amsVehicle()]], 200);

    $result = resolve(SynchronizeMkwdVehicles::class)->handle();

    expect($result->created)->toBe(1);
    Http::assertSentCount(2);
})->with([429, 500, 503]);

test('temporary connection failures are retried', function () {
    Http::fakeSequence()
        ->pushFailedConnection()
        ->push(['data' => [amsVehicle()]], 200);

    $result = resolve(SynchronizeMkwdVehicles::class)->handle();

    expect($result->created)->toBe(1);
    Http::assertSentCount(2);
});

test('taxonomy omitted from a later complete snapshot is marked inactive', function () {
    Http::fakeSequence()
        ->push(['data' => [amsVehicle()]], 200)
        ->push(['data' => [amsVehicle(['category' => null, 'subcategory' => null])]], 200);

    $synchronizer = resolve(SynchronizeMkwdVehicles::class);
    $synchronizer->handle();
    $synchronizer->handle();

    expect(VehicleCategory::query()->sole()->is_active)->toBeFalse()
        ->and(VehicleSubcategory::query()->sole()->is_active)->toBeFalse()
        ->and(Vehicle::query()->sole()->vehicle_category_id)->toBeNull()
        ->and(Vehicle::query()->sole()->vehicle_subcategory_id)->toBeNull();
});

test('an invalid or partial response cannot modify or archive local data', function (array $payload) {
    $vehicle = Vehicle::factory()->create([
        'name' => 'Last known vehicle',
        'is_present_in_ams' => true,
    ]);
    $original = $vehicle->refresh()->getAttributes();

    Http::fake([
        'http://mkwd-ims.test/api/vehicles' => Http::response($payload),
    ]);

    expect(fn () => resolve(SynchronizeMkwdVehicles::class)->handle())
        ->toThrow(MkwdImsException::class);

    expect($vehicle->refresh()->getAttributes())->toBe($original);
})->with([
    'missing data member' => [[]],
    'partial vehicle' => [['data' => [['id' => 5]]]],
    'non-list data' => [['data' => ['id' => 5]]],
]);

test('the complete synchronization is idempotent', function () {
    Http::fake([
        'http://mkwd-ims.test/api/vehicles' => Http::response(['data' => [amsVehicle()]]),
    ]);

    $synchronizer = resolve(SynchronizeMkwdVehicles::class);
    $firstResult = $synchronizer->handle();
    Vehicle::query()->sole()->update([
        'current_mileage' => 45678,
        'operational_notes' => 'Locally managed note.',
    ]);
    $secondResult = $synchronizer->handle();

    expect($firstResult->created)->toBe(1)
        ->and($secondResult->toArray())->toBe([
            'created' => 0,
            'updated' => 0,
            'unchanged' => 1,
            'archived' => 0,
            'reactivated' => 0,
            'failed' => 0,
        ])
        ->and(Vehicle::query()->count())->toBe(1)
        ->and(Vehicle::query()->sole()->current_mileage)->toBe(45678)
        ->and(Vehicle::query()->sole()->operational_notes)->toBe('Locally managed note.')
        ->and(VehicleCategory::query()->count())->toBe(1)
        ->and(VehicleSubcategory::query()->count())->toBe(1);
});
