<?php

use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(8, 0));
});

/** @return array<string, mixed> */
function tripRequestData(array $overrides = []): array
{
    return [
        'destination' => 'Regional Office',
        'purpose' => 'Deliver signed project documents.',
        'passengers' => "Maria Santos\nJuan Cruz",
        'planned_departure' => '2026-09-09T08:00',
        'planned_return' => '2026-09-09T12:00',
        ...$overrides,
    ];
}

/** @return array<string, mixed> */
function tripApprovalData(Vehicle $vehicle, Driver $driver, array $overrides = []): array
{
    return [
        'decision' => Trip::STATUS_APPROVED,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'decision_notes' => 'Approved for official travel.',
        ...$overrides,
    ];
}

function pendingTrip(array $overrides = []): Trip
{
    return Trip::factory()->create([
        'planned_departure' => '2026-09-09 08:00:00',
        'planned_return' => '2026-09-09 12:00:00',
        ...$overrides,
    ]);
}

test('authenticated users can submit a trip request with an optional passenger manifest', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('trips.store'), tripRequestData())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $trip = Trip::query()->firstOrFail();
    expect($trip)->requested_by->toBe($user->id)
        ->status->toBe(Trip::STATUS_PENDING)
        ->reference_number->toBe('TRIP-2026-000001')
        ->passengers->toBe("Maria Santos\nJuan Cruz")
        ->vehicle_id->toBeNull()
        ->driver_id->toBeNull();
});

test('passengers are optional', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('trips.store'), tripRequestData(['passengers' => null]))
        ->assertSessionHasNoErrors();

    expect(Trip::query()->firstOrFail()->passengers)->toBeNull();
});

test('trip request input is validated', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->create())
        ->post(route('trips.store'), tripRequestData($overrides))
        ->assertSessionHasErrors($field);

    expect(Trip::query()->count())->toBe(0);
})->with([
    'destination required' => [['destination' => ''], 'destination'],
    'purpose required' => [['purpose' => ''], 'purpose'],
    'departure valid' => [['planned_departure' => 'wrong'], 'planned_departure'],
    'departure future' => [['planned_departure' => '2026-09-08T07:59'], 'planned_departure'],
    'return after departure' => [['planned_return' => '2026-09-09T08:00'], 'planned_return'],
    'passenger length' => [['passengers' => str_repeat('x', 5001)], 'passengers'],
    'destination control characters' => [['destination' => "Regional\x07Office"], 'destination'],
    'purpose control characters' => [['purpose' => "Deliver\x00documents"], 'purpose'],
]);

test('managers can approve a trip and assign available resources', function () {
    $trip = pendingTrip();
    $vehicle = Vehicle::factory()->create();
    $driver = Driver::factory()->eligible()->create(['license_expiry' => '2026-09-09']);

    $this->actingAs($manager = User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', $trip), tripApprovalData($vehicle, $driver))
        ->assertSessionHasNoErrors();

    expect($trip->refresh())->status->toBe(Trip::STATUS_APPROVED)
        ->vehicle_id->toBe($vehicle->id)
        ->driver_id->toBe($driver->id)
        ->decided_by->toBe($manager->id)
        ->decided_at->not->toBeNull();
});

test('trip approval shows an eligible outsourced driver as eligible', function () {
    $driver = Driver::factory()->outsourced()->eligible()->create([
        'employee_name' => 'Klein Allen',
        'license_number' => 'DASDWQ',
        'license_type' => 'professional',
        'license_expiry' => '2213-12-31',
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->get(route('trips.show', pendingTrip()))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drivers.0.id', $driver->id)
            ->where('drivers.0.status', Driver::STATUS_ACTIVE)
            ->where('drivers.0.license_type', 'professional')
            ->where('drivers.0.eligible_to_drive', true));
});

test('managers can reject a request with a reason and without resources', function () {
    $trip = pendingTrip();

    $this->actingAs($manager = User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', $trip), [
            'decision' => Trip::STATUS_REJECTED,
            'decision_notes' => 'Travel is no longer required.',
        ])->assertSessionHasNoErrors();

    expect($trip->refresh())->status->toBe(Trip::STATUS_REJECTED)
        ->decision_notes->toBe('Travel is no longer required.')
        ->decided_by->toBe($manager->id)
        ->vehicle_id->toBeNull();
});

test('rejections require a reason and approvals require both resources', function (array $data, string $field) {
    $trip = pendingTrip();
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', $trip), $data)->assertSessionHasErrors($field);
    expect($trip->refresh()->status)->toBe(Trip::STATUS_PENDING);
})->with([
    [['decision' => 'rejected'], 'decision_notes'],
    [['decision' => 'approved'], 'vehicle_id'],
    [['decision' => 'approved', 'vehicle_id' => 999999, 'driver_id' => 999999], 'vehicle_id'],
    [['decision' => 'invalid'], 'decision'],
]);

test('an approved or dispatched overlapping trip blocks its vehicle and driver', function (string $status, string $resource) {
    $vehicle = Vehicle::factory()->create();
    $driver = Driver::factory()->eligible()->create();
    Trip::factory()->create([
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'status' => $status,
        'planned_departure' => '2026-09-09 10:00:00',
        'planned_return' => '2026-09-09 14:00:00',
    ]);
    $trip = pendingTrip();
    $secondVehicle = Vehicle::factory()->create();
    $secondDriver = Driver::factory()->eligible()->create();
    $data = tripApprovalData(
        $resource === 'vehicle' ? $vehicle : $secondVehicle,
        $resource === 'driver' ? $driver : $secondDriver,
    );

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', $trip), $data)->assertSessionHasErrors('vehicle_id');

    expect($trip->refresh()->status)->toBe(Trip::STATUS_PENDING);
})->with([Trip::STATUS_APPROVED, Trip::STATUS_DISPATCHED])->with(['vehicle', 'driver']);

test('touching trip schedules do not overlap', function () {
    $vehicle = Vehicle::factory()->create();
    $driver = Driver::factory()->eligible()->create();
    Trip::factory()->create([
        'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'status' => Trip::STATUS_APPROVED,
        'planned_departure' => '2026-09-09 06:00:00', 'planned_return' => '2026-09-09 08:00:00',
    ]);
    $trip = pendingTrip();

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', $trip), tripApprovalData($vehicle, $driver))
        ->assertSessionHasNoErrors();
});

test('conflicting vehicle driver assignments block trip approval', function (string $resource) {
    $vehicle = Vehicle::factory()->create();
    $driver = Driver::factory()->eligible()->create();
    $otherVehicle = Vehicle::factory()->create();
    $otherDriver = Driver::factory()->eligible()->create();
    VehicleDriverAssignment::factory()->create([
        'vehicle_id' => $resource === 'vehicle' ? $vehicle->id : $otherVehicle->id,
        'driver_id' => $resource === 'driver' ? $driver->id : $otherDriver->id,
        'start_date' => '2026-09-09',
        'end_date' => '2026-09-09',
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', pendingTrip()), tripApprovalData($vehicle, $driver))
        ->assertSessionHasErrors('vehicle_id');
})->with(['vehicle', 'driver']);

test('a matching vehicle driver assignment permits approval', function () {
    $vehicle = Vehicle::factory()->create();
    $driver = Driver::factory()->eligible()->create();
    VehicleDriverAssignment::factory()->create([
        'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'start_date' => '2026-09-09', 'end_date' => '2026-09-09',
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', pendingTrip()), tripApprovalData($vehicle, $driver))
        ->assertSessionHasNoErrors();
});

test('unavailable vehicles and ineligible or expiring drivers cannot be approved', function (array $vehicleAttributes, array $driverAttributes, string $field) {
    $vehicle = Vehicle::factory()->create($vehicleAttributes);
    $driver = Driver::factory()->eligible()->create($driverAttributes);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', pendingTrip()), tripApprovalData($vehicle, $driver))
        ->assertSessionHasErrors($field);
})->with([
    'missing vehicle' => [['is_present_in_ams' => false], [], 'vehicle_id'],
    'maintenance vehicle' => [['operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE], [], 'vehicle_id'],
    'out of service vehicle' => [['operational_status' => Vehicle::STATUS_OUT_OF_SERVICE], [], 'vehicle_id'],
    'retired AMS asset' => [['ams_asset_status' => 'disposed'], [], 'vehicle_id'],
    'inactive driver' => [[], ['status' => Driver::STATUS_INACTIVE], 'driver_id'],
    'unapproved driver' => [[], ['driving_authorized' => false], 'driver_id'],
    'license expires too soon' => [[], ['license_expiry' => '2026-09-08'], 'driver_id'],
]);

test('only pending trips can be decided', function () {
    $trip = pendingTrip(['status' => Trip::STATUS_REJECTED]);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.decide', $trip), ['decision' => 'rejected', 'decision_notes' => 'Again'])
        ->assertSessionHasErrors('decision');
});

test('managers can dispatch an approved trip and record start odometer', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 12500]);
    $driver = Driver::factory()->eligible()->create();
    $trip = pendingTrip(['status' => Trip::STATUS_APPROVED, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);

    $this->actingAs($manager = User::factory()->vehicleManager()->create())
        ->patch(route('trips.dispatch', $trip), ['actual_departure' => '2026-09-08T08:00', 'start_odometer' => 12505])
        ->assertSessionHasNoErrors();

    expect($trip->refresh())->status->toBe(Trip::STATUS_DISPATCHED)
        ->start_odometer->toBe(12505)
        ->dispatched_by->toBe($manager->id);
    expect($vehicle->refresh())->current_mileage->toBe(12505)
        ->operational_status->toBe(Vehicle::STATUS_IN_USE);
});

test('dispatch rejects stale eligibility vehicle status and odometer', function (array $vehicleChanges, array $driverChanges, int $odometer, string $field) {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);
    $driver = Driver::factory()->eligible()->create();
    $trip = pendingTrip(['status' => Trip::STATUS_APPROVED, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);
    $vehicle->update($vehicleChanges);
    $driver->update($driverChanges);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.dispatch', $trip), ['actual_departure' => '2026-09-08T08:00', 'start_odometer' => $odometer])
        ->assertSessionHasErrors($field);
    expect($trip->refresh()->status)->toBe(Trip::STATUS_APPROVED);
})->with([
    'odometer rollback' => [[], [], 999, 'start_odometer'],
    'driver inactive' => [[], ['status' => Driver::STATUS_INACTIVE], 1000, 'actual_departure'],
    'vehicle maintenance' => [['operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE], [], 1000, 'actual_departure'],
    'vehicle already in use' => [['operational_status' => Vehicle::STATUS_IN_USE], [], 1000, 'actual_departure'],
]);

test('only approved trips can be dispatched', function () {
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.dispatch', pendingTrip()), ['actual_departure' => '2026-09-08T08:00', 'start_odometer' => 1])
        ->assertSessionHasErrors('actual_departure');
});

test('managers can complete a dispatched trip and update mileage', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 500, 'operational_status' => Vehicle::STATUS_IN_USE]);
    $driver = Driver::factory()->eligible()->create();
    $trip = pendingTrip([
        'status' => Trip::STATUS_DISPATCHED, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'actual_departure' => '2026-09-08 06:00:00', 'start_odometer' => 500,
    ]);

    $this->actingAs($manager = User::factory()->vehicleManager()->create())
        ->patch(route('trips.complete', $trip), ['actual_return' => '2026-09-08T08:00', 'end_odometer' => 575])
        ->assertSessionHasNoErrors();

    expect($trip->refresh())->status->toBe(Trip::STATUS_COMPLETED)
        ->end_odometer->toBe(575)
        ->returned_by->toBe($manager->id);
    expect($vehicle->refresh())->current_mileage->toBe(575)
        ->operational_status->toBe(Vehicle::STATUS_AVAILABLE);
});

test('return records mileage without erasing an intervening vehicle status', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 500, 'operational_status' => Vehicle::STATUS_UNDER_MAINTENANCE]);
    $trip = pendingTrip([
        'status' => Trip::STATUS_DISPATCHED, 'vehicle_id' => $vehicle->id, 'driver_id' => Driver::factory()->eligible(),
        'actual_departure' => '2026-09-08 06:00:00', 'start_odometer' => 500,
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.complete', $trip), ['actual_return' => '2026-09-08T08:00', 'end_odometer' => 550])
        ->assertSessionHasNoErrors();

    expect($vehicle->refresh())->current_mileage->toBe(550)
        ->operational_status->toBe(Vehicle::STATUS_UNDER_MAINTENANCE);
});

test('return must follow departure and end odometer cannot decrease', function (string $return, int $odometer, string $field) {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 500]);
    $trip = pendingTrip([
        'status' => Trip::STATUS_DISPATCHED, 'vehicle_id' => $vehicle->id, 'driver_id' => Driver::factory()->eligible()->create()->id,
        'actual_departure' => '2026-09-08 07:00:00', 'start_odometer' => 500,
    ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('trips.complete', $trip), ['actual_return' => $return, 'end_odometer' => $odometer])
        ->assertSessionHasErrors($field);
    expect($trip->refresh()->status)->toBe(Trip::STATUS_DISPATCHED);
})->with([
    ['2026-09-08T06:59', 500, 'actual_return'],
    ['2026-09-08T08:00', 499, 'end_odometer'],
    ['2026-09-08T08:01', 500, 'actual_return'],
]);

test('trip list and profile expose dispatch history', function () {
    $trip = pendingTrip([
        'reference_number' => 'TRIP-2026-000123',
        'status' => Trip::STATUS_COMPLETED,
        'vehicle_id' => Vehicle::factory(),
        'driver_id' => Driver::factory()->eligible(),
        'decided_by' => User::factory(),
        'decided_at' => now(),
        'actual_departure' => '2026-09-08 06:00:00',
        'start_odometer' => 100,
        'actual_return' => '2026-09-08 07:00:00',
        'end_odometer' => 150,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('trips.index', ['search' => '000123', 'status' => 'completed']))
        ->assertInertia(fn (Assert $page) => $page->component('trips/index')->has('trips.data', 1)
            ->where('trips.data.0.reference_number', 'TRIP-2026-000123'));

    $this->get(route('trips.show', $trip))
        ->assertInertia(fn (Assert $page) => $page->component('trips/show')
            ->where('trip.end_odometer', 150)->where('trip.vehicle.id', $trip->vehicle_id)
            ->where('canManage', false)->has('vehicles', 0)->has('drivers', 0));
});

test('regular users cannot decide dispatch or complete trips', function () {
    $trip = pendingTrip();
    $user = User::factory()->create();
    $this->actingAs($user)
        ->patch(route('trips.decide', $trip), [])->assertForbidden();
    $this->patch(route('trips.dispatch', $trip), [])->assertForbidden();
    $this->patch(route('trips.complete', $trip), [])->assertForbidden();
    expect($trip->refresh()->status)->toBe(Trip::STATUS_PENDING);
});

test('guests cannot access trip routes', function () {
    $trip = pendingTrip();
    $this->get(route('trips.index'))->assertRedirect(route('login'));
    $this->get(route('trips.create'))->assertRedirect(route('login'));
    $this->get(route('trips.show', $trip))->assertRedirect(route('login'));
    $this->post(route('trips.store'), [])->assertRedirect(route('login'));
});
