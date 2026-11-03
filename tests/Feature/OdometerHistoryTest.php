<?php

use App\Models\Driver;
use App\Models\OdometerEntry;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(8, 0));
});

/** @return array<string, mixed> */
function operationalUpdate(Vehicle $vehicle, array $overrides = []): array
{
    return [
        'current_mileage' => $vehicle->current_mileage,
        'operational_status' => $vehicle->operational_status,
        'fuel_type' => $vehicle->fuel_type,
        'maintenance_interval_km' => $vehicle->maintenance_interval_km,
        'operational_notes' => $vehicle->operational_notes,
        ...$overrides,
    ];
}

function dispatchedTripForOdometer(Vehicle $vehicle, User $manager): Trip
{
    return Trip::factory()->create([
        'reference_number' => 'TRIP-2026-000100',
        'status' => Trip::STATUS_APPROVED,
        'vehicle_id' => $vehicle->id,
        'driver_id' => Driver::factory()->eligible(),
        'decided_by' => $manager->id,
        'decided_at' => now(),
        'planned_departure' => '2026-09-08 08:00:00',
        'planned_return' => '2026-09-08 12:00:00',
    ]);
}

test('trip departure appends odometer history and updates current mileage atomically', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);
    $manager = User::factory()->vehicleManager()->create();
    $trip = dispatchedTripForOdometer($vehicle, $manager);

    $this->actingAs($manager)->patch(route('trips.dispatch', $trip), [
        'actual_departure' => '2026-09-08T08:00',
        'start_odometer' => 1010,
    ])->assertSessionHasNoErrors();

    $entry = OdometerEntry::query()->firstOrFail();
    expect($entry)->vehicle_id->toBe($vehicle->id)
        ->trip_id->toBe($trip->id)
        ->recorded_by->toBe($manager->id)
        ->entry_type->toBe(OdometerEntry::TYPE_TRIP_DEPARTURE)
        ->previous_reading->toBe(1000)
        ->reading->toBe(1010)
        ->notes->toContain($trip->reference_number);
    expect($entry->recorded_at->format('Y-m-d H:i'))->toBe('2026-09-08 08:00');
    expect($vehicle->refresh()->current_mileage)->toBe(1010);
});

test('completed trips append a return reading without replacing departure history', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);
    $manager = User::factory()->vehicleManager()->create();
    $trip = dispatchedTripForOdometer($vehicle, $manager);
    $this->actingAs($manager)->patch(route('trips.dispatch', $trip), [
        'actual_departure' => '2026-09-08T08:00', 'start_odometer' => 1010,
    ])->assertSessionHasNoErrors();
    $this->patch(route('trips.complete', $trip), [
        'actual_return' => '2026-09-08T08:00', 'end_odometer' => 1090,
    ])->assertSessionHasNoErrors();

    $entries = OdometerEntry::query()->orderBy('recorded_at')->orderBy('id')->get();
    expect($entries)->toHaveCount(2);
    expect($entries[0])->entry_type->toBe(OdometerEntry::TYPE_TRIP_DEPARTURE)
        ->reading->toBe(1010);
    expect($entries[1])->entry_type->toBe(OdometerEntry::TYPE_TRIP_RETURN)
        ->previous_reading->toBe(1010)
        ->reading->toBe(1090);
    expect($vehicle->refresh()->current_mileage)->toBe(1090);
});

test('failed trip transitions do not leave odometer entries', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);
    $manager = User::factory()->vehicleManager()->create();
    $trip = dispatchedTripForOdometer($vehicle, $manager);

    $this->actingAs($manager)->patch(route('trips.dispatch', $trip), [
        'actual_departure' => '2026-09-08T08:00', 'start_odometer' => 999,
    ])->assertSessionHasErrors('start_odometer');

    expect(OdometerEntry::query()->count())->toBe(0);
    expect($vehicle->refresh()->current_mileage)->toBe(1000);
});

test('authorized manual mileage changes append documented corrections including decreases', function (int $reading) {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);
    $manager = User::factory()->vehicleManager()->create();

    $this->actingAs($manager)->patch(route('vehicles.update', $vehicle), operationalUpdate($vehicle, [
        'current_mileage' => $reading,
        'mileage_correction_reason' => 'Verified against the physical dashboard.',
    ]))->assertSessionHasNoErrors();

    $entry = OdometerEntry::query()->firstOrFail();
    expect($entry)->entry_type->toBe(OdometerEntry::TYPE_MANUAL_CORRECTION)
        ->previous_reading->toBe(1000)
        ->reading->toBe($reading)
        ->recorded_by->toBe($manager->id)
        ->trip_id->toBeNull()
        ->notes->toBe('Verified against the physical dashboard.');
    expect($vehicle->refresh()->current_mileage)->toBe($reading);
})->with([1200, 900]);

test('unchanged mileage does not create a correction entry', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('vehicles.update', $vehicle), operationalUpdate($vehicle, ['operational_notes' => 'No mileage change.']))
        ->assertSessionHasNoErrors();

    expect(OdometerEntry::query()->count())->toBe(0);
});

test('manual mileage corrections require a reason', function (?string $reason) {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);
    $overrides = ['current_mileage' => 1100];

    if ($reason !== null) {
        $overrides['mileage_correction_reason'] = $reason;
    }

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('vehicles.update', $vehicle), operationalUpdate($vehicle, $overrides))
        ->assertSessionHasErrors('mileage_correction_reason');

    expect(OdometerEntry::query()->count())->toBe(0);
    expect($vehicle->refresh()->current_mileage)->toBe(1000);
})->with([null, '   ']);

test('manual corrections are blocked while a trip is dispatched', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);
    Trip::factory()->create([
        'status' => Trip::STATUS_DISPATCHED,
        'vehicle_id' => $vehicle->id,
        'driver_id' => Driver::factory()->eligible(),
        'actual_departure' => now(),
        'start_odometer' => 1000,
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('vehicles.update', $vehicle), operationalUpdate($vehicle, [
            'current_mileage' => 1050,
            'mileage_correction_reason' => 'Attempt during active trip.',
        ]))->assertSessionHasErrors('current_mileage');

    expect(OdometerEntry::query()->count())->toBe(0);
});

test('odometer history is visible, filterable, ordered, and paginated', function () {
    $vehicle = Vehicle::factory()->create();
    OdometerEntry::factory()->count(21)->create(['vehicle_id' => $vehicle->id]);
    $other = OdometerEntry::factory()->create(['entry_type' => OdometerEntry::TYPE_TRIP_RETURN]);

    $this->actingAs(User::factory()->create())
        ->get(route('odometer.index'))
        ->assertInertia(fn (Assert $page) => $page->component('odometer/index')
            ->has('entries.data', 20)->where('entries.total', 22)->where('canCorrect', false));

    $this->get(route('odometer.index', [
        'vehicle_id' => $other->vehicle_id,
        'entry_type' => OdometerEntry::TYPE_TRIP_RETURN,
    ]))->assertInertia(fn (Assert $page) => $page->has('entries.data', 1)
        ->where('entries.data.0.id', $other->id)
        ->where('entries.data.0.recorder.name', $other->recorder->name));
});

test('vehicle profiles show the five most recent odometer entries', function () {
    $vehicle = Vehicle::factory()->create();
    OdometerEntry::factory()->count(6)->sequence(
        fn ($sequence) => ['vehicle_id' => $vehicle->id, 'recorded_at' => now()->subMinutes(6 - $sequence->index)],
    )->create();

    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.show', $vehicle))
        ->assertInertia(fn (Assert $page) => $page->has('recentOdometerEntries', 5)
            ->where('recentOdometerEntries.0.reading', OdometerEntry::query()->latest('recorded_at')->value('reading')));
});

test('regular users cannot create manual corrections', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);

    $this->actingAs(User::factory()->create())
        ->patch(route('vehicles.update', $vehicle), operationalUpdate($vehicle, [
            'current_mileage' => 1100,
            'mileage_correction_reason' => 'Unauthorized.',
        ]))->assertForbidden();

    expect(OdometerEntry::query()->count())->toBe(0);
});

test('guests cannot view odometer history', function () {
    $this->get(route('odometer.index'))->assertRedirect(route('login'));
});
