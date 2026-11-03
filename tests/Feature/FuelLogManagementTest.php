<?php

use App\Models\FuelLog;
use App\Models\OdometerEntry;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(10, 0));
});

/** @return array<string, mixed> */
function fuelLogData(Vehicle $vehicle, array $overrides = []): array
{
    return [
        'vehicle_id' => $vehicle->id,
        'quantity_liters' => 40,
        'total_cost' => 2400,
        'odometer' => 1000,
        'refueled_at' => '2026-09-08T09:00',
        'station' => 'Central Fuel Station',
        'receipt_reference' => 'OR-12345',
        'notes' => 'Full tank.',
        ...$overrides,
    ];
}

test('managers record fuel and append its odometer history', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 900]);
    $this->actingAs($manager = User::factory()->vehicleManager()->create())
        ->post(route('fuel-logs.store'), fuelLogData($vehicle))
        ->assertSessionHasNoErrors()->assertRedirect(route('fuel-logs.index', ['vehicle_id' => $vehicle->id]));

    $log = FuelLog::query()->firstOrFail();
    expect($log)->recorded_by->toBe($manager->id)
        ->price_per_liter->toBe('60.000')
        ->distance_since_previous->toBeNull()
        ->consumption_km_per_liter->toBeNull();
    expect($vehicle->refresh()->current_mileage)->toBe(1000);
    expect(OdometerEntry::query()->firstOrFail())->entry_type->toBe(OdometerEntry::TYPE_REFUEL)
        ->previous_reading->toBe(900)->reading->toBe(1000);
});

test('consecutive entries calculate fuel consumption and running cost', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 900]);
    $manager = User::factory()->vehicleManager()->create();
    $this->actingAs($manager)->post(route('fuel-logs.store'), fuelLogData($vehicle));
    $this->post(route('fuel-logs.store'), fuelLogData($vehicle, [
        'quantity_liters' => 40, 'total_cost' => 2400, 'odometer' => 1400, 'refueled_at' => '2026-09-08T09:30', 'receipt_reference' => 'OR-12346',
    ]))->assertSessionHasNoErrors();

    $log = FuelLog::query()->latest('id')->firstOrFail();
    expect($log)->distance_since_previous->toBe(400)
        ->consumption_km_per_liter->toBe('10.000')
        ->cost_per_km->toBe('6.000');
});

test('fuel log index exposes filtered totals and average consumption', function () {
    $vehicle = Vehicle::factory()->create();
    FuelLog::factory()->create(['vehicle_id' => $vehicle->id, 'quantity_liters' => 40, 'total_cost' => 2400, 'distance_since_previous' => null, 'consumption_km_per_liter' => null]);
    FuelLog::factory()->create(['vehicle_id' => $vehicle->id, 'quantity_liters' => 50, 'total_cost' => 3000, 'distance_since_previous' => 500, 'consumption_km_per_liter' => 10]);

    $this->actingAs(User::factory()->create())->get(route('fuel-logs.index', ['vehicle_id' => $vehicle->id]))
        ->assertInertia(fn (Assert $page) => $page->component('fuel-logs/index')->has('logs.data', 2)
            ->where('summary.quantity', 90)->where('summary.cost', 5400)->where('summary.distance', 500)->where('summary.consumption', 10));
});

test('refueling cannot silently roll the vehicle odometer backward', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1500]);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('fuel-logs.store'), fuelLogData($vehicle, ['odometer' => 1499]))
        ->assertSessionHasErrors('odometer');

    expect(FuelLog::query()->count())->toBe(0);
    expect(OdometerEntry::query()->count())->toBe(0);
});

test('fuel logs require valid quantity cost odometer and date', function (array $changes, string $field) {
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('fuel-logs.store'), fuelLogData(Vehicle::factory()->create(), $changes))
        ->assertSessionHasErrors($field);
})->with([
    'quantity' => [['quantity_liters' => 0], 'quantity_liters'],
    'cost' => [['total_cost' => -1], 'total_cost'],
    'odometer' => [['odometer' => -1], 'odometer'],
    'future date' => [['refueled_at' => '2026-09-08T10:01'], 'refueled_at'],
    'quantity decimal precision' => [['quantity_liters' => '40.1234'], 'quantity_liters'],
    'cost decimal precision' => [['total_cost' => '2400.999'], 'total_cost'],
    'receipt control characters' => [['receipt_reference' => "OR-12\x07"], 'receipt_reference'],
]);

test('regular users can view but cannot create fuel logs', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('fuel-logs.index'))->assertSuccessful();
    $this->post(route('fuel-logs.store'), fuelLogData(Vehicle::factory()->create()))->assertForbidden();
});

test('guests cannot access fuel logs', function () {
    $this->get(route('fuel-logs.index'))->assertRedirect(route('login'));
    $this->get(route('fuel-logs.create'))->assertRedirect(route('login'));
});
