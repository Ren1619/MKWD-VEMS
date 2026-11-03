<?php

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->travelTo(now()->setDate(2026, 9, 8)->startOfDay());
});

/** @return array<string, mixed> */
function assignmentData(array $overrides = []): array
{
    return [
        'vehicle_id' => Vehicle::factory()->create()->id,
        'driver_id' => Driver::factory()->eligible()->create()->id,
        'start_date' => '2026-09-08',
        'end_date' => null,
        'assignment_type' => 'primary',
        ...$overrides,
    ];
}

test('managers can assign hris and outsourced drivers with either assignment type', function (string $type, bool $outsourced) {
    $driver = Driver::factory()->eligible()->create($outsourced ? ['source_type' => 'outsourced', 'hris_employee_id' => null, 'is_present_in_hris' => false] : []);
    $data = assignmentData(['driver_id' => $driver->id, 'assignment_type' => $type]);
    $vehicle = Vehicle::query()->findOrFail($data['vehicle_id']);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), $data)->assertSessionHasNoErrors()
        ->assertRedirect(route('assignments.index', ['vehicle_id' => $vehicle->id]));
    $this->assertDatabaseHas('vehicle_driver_assignments', $data);
    expect($vehicle->refresh()->operational_status)->toBe(Vehicle::STATUS_AVAILABLE);
})->with([['primary', false], ['temporary', false], ['primary', true], ['temporary', true]]);

test('overlapping intervals are rejected for either the vehicle or driver', function (string $field, ?string $existingEnd, string $start, ?string $end) {
    $existing = VehicleDriverAssignment::factory()->create(['start_date' => '2026-09-10', 'end_date' => $existingEnd]);
    $data = assignmentData([$field => $existing->{$field}, 'start_date' => $start, 'end_date' => $end, 'assignment_type' => 'temporary']);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), $data)->assertSessionHasErrors($field);
    expect(VehicleDriverAssignment::query()->count())->toBe(1);
})->with(['vehicle_id', 'driver_id'])->with([
    ['2026-09-20', '2026-09-20', '2026-09-25'],
    ['2026-09-20', '2026-09-01', '2026-09-10'],
    ['2026-09-20', '2026-09-11', '2026-09-12'],
    ['2026-09-20', '2026-09-01', null],
    [null, '2026-10-01', null],
]);

test('non overlapping assignments preserve past and future history', function (string $start, ?string $end) {
    $existing = VehicleDriverAssignment::factory()->create(['start_date' => '2026-09-10', 'end_date' => '2026-09-20']);
    $data = assignmentData(['vehicle_id' => $existing->vehicle_id, 'driver_id' => $existing->driver_id, 'start_date' => $start, 'end_date' => $end]);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), $data)->assertSessionHasNoErrors();
    expect(VehicleDriverAssignment::query()->count())->toBe(2);
})->with([['2026-09-01', '2026-09-09'], ['2026-09-21', null]]);

test('assignment inputs are validated', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), assignmentData($overrides))->assertSessionHasErrors($field);
    expect(VehicleDriverAssignment::query()->count())->toBe(0);
})->with([
    [['vehicle_id' => 999999], 'vehicle_id'],
    [['driver_id' => 999999], 'driver_id'],
    [['start_date' => 'not-a-date'], 'start_date'],
    [['end_date' => '2026-09-07'], 'end_date'],
    [['assignment_type' => 'invalid'], 'assignment_type'],
    [['end_date' => '2026-02-30'], 'end_date'],
    [['start_date' => '09/08/2026'], 'start_date'],
    [['vehicle_id' => '1.0'], 'vehicle_id'],
]);

test('ineligible drivers cannot be assigned', function (array $attributes) {
    $driver = Driver::factory()->eligible()->create($attributes);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), assignmentData(['driver_id' => $driver->id]))
        ->assertSessionHasErrors('driver_id');
})->with([
    [['status' => 'inactive']],
    [['driving_authorized' => false]],
    [['is_present_in_hris' => false]],
    [['license_expiry' => '2026-09-07']],
]);

test('licenses must cover the start and any specified end date', function (string $start, ?string $end) {
    $driver = Driver::factory()->eligible()->create(['license_expiry' => '2026-09-10']);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), assignmentData(['driver_id' => $driver->id, 'start_date' => $start, 'end_date' => $end]))
        ->assertSessionHasErrors('driver_id');
})->with([['2026-09-11', null], ['2026-09-08', '2026-09-11']]);

test('licenses remain valid on their expiry date', function () {
    $driver = Driver::factory()->eligible()->create(['license_expiry' => '2026-09-08']);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), assignmentData(['driver_id' => $driver->id, 'end_date' => '2026-09-08']))
        ->assertSessionHasNoErrors();
});

test('missing and retired vehicles cannot receive new assignments', function (array $attributes) {
    $vehicle = Vehicle::factory()->create($attributes);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('assignments.store'), assignmentData(['vehicle_id' => $vehicle->id]))
        ->assertSessionHasErrors('vehicle_id');
})->with([
    [['is_present_in_ams' => false]],
    [['operational_status' => 'retired']],
    [['ams_asset_status' => 'disposed']],
]);

test('both profiles show the same current assignment with reciprocal profile details', function () {
    $assignment = VehicleDriverAssignment::factory()->create(['end_date' => '2026-09-08']);
    $this->actingAs(User::factory()->create());
    $this->get(route('vehicles.show', $assignment->vehicle_id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('currentAssignment.id', $assignment->id)
            ->where('currentAssignment.driver.employee_name', $assignment->driver->employee_name)
            ->where('currentAssignment.status', 'current'));
    $this->get(route('drivers.show', $assignment->driver_id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('currentAssignment.id', $assignment->id)
            ->where('currentAssignment.vehicle.name', $assignment->vehicle->name));
    $this->travel(1)->days();
    $this->get(route('vehicles.show', $assignment->vehicle_id))->assertInertia(fn (Assert $page) => $page->where('currentAssignment', null));
    $this->get(route('drivers.show', $assignment->driver_id))->assertInertia(fn (Assert $page) => $page->where('currentAssignment', null));
});

test('scheduled assignments are not shown as current', function () {
    $assignment = VehicleDriverAssignment::factory()->create(['start_date' => '2026-09-09']);
    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.show', $assignment->vehicle_id))
        ->assertInertia(fn (Assert $page) => $page->where('currentAssignment', null));
});

test('ending an assignment retains its history and frees subsequent dates', function () {
    $assignment = VehicleDriverAssignment::factory()->create(['start_date' => '2026-09-01']);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->from(route('assignments.index'))
        ->patch(route('assignments.end', $assignment), ['end_date' => '2026-09-07'])
        ->assertSessionHasNoErrors()->assertRedirect(route('assignments.index'));
    expect($assignment->refresh()->getStatusAttribute())->toBe('ended');
    expect($assignment->closed_at)->not->toBeNull();
    $this->post(route('assignments.store'), assignmentData(['vehicle_id' => $assignment->vehicle_id, 'driver_id' => $assignment->driver_id]))
        ->assertSessionHasNoErrors();
    expect(VehicleDriverAssignment::query()->count())->toBe(2);
});

test('ending today remains current and blocks another assignment today', function () {
    $assignment = VehicleDriverAssignment::factory()->create();
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('assignments.end', $assignment), ['end_date' => '2026-09-08'])->assertSessionHasNoErrors();
    expect($assignment->refresh()->getStatusAttribute())->toBe('current');
    expect($assignment->getCanEndAttribute())->toBeFalse();
    $this->post(route('assignments.store'), assignmentData(['vehicle_id' => $assignment->vehicle_id]))->assertSessionHasErrors('vehicle_id');
});

test('closed history cannot be changed and date bounds are enforced', function (array $attributes, string $endDate) {
    $assignment = VehicleDriverAssignment::factory()->create(['start_date' => '2026-09-01', ...$attributes]);
    $before = $assignment->refresh()->getAttributes();
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('assignments.end', $assignment), ['end_date' => $endDate])->assertSessionHasErrors('end_date');
    expect($assignment->refresh()->getAttributes())->toBe($before);
})->with([
    [['closed_at' => '2026-09-08 00:00:00', 'end_date' => '2026-09-08'], '2026-09-07'],
    [['end_date' => '2026-09-07'], '2026-09-08'],
    [[], '2026-08-31'],
    [[], '2026-09-09'],
    [['start_date' => '2026-09-10'], '2026-09-08'],
]);

test('assignment listing filters history and paginates', function () {
    $current = VehicleDriverAssignment::factory()->create();
    VehicleDriverAssignment::factory()->create(['vehicle_id' => $current->vehicle_id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-07']);
    VehicleDriverAssignment::factory()->count(15)->create(['start_date' => '2026-09-10']);
    $this->actingAs(User::factory()->create())
        ->get(route('assignments.index'))->assertInertia(fn (Assert $page) => $page
        ->component('assignments/index')->has('assignments.data', 15)->where('assignments.total', 17)->where('canManage', false));
    $this->get(route('assignments.index', ['vehicle_id' => $current->vehicle_id, 'status' => 'ended']))
        ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 1)->where('assignments.data.0.status', 'ended'));
    $this->get(route('assignments.index', ['driver_id' => $current->driver_id, 'status' => 'current']))
        ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 1)->where('assignments.data.0.id', $current->id));
    $this->get(route('assignments.index', ['status' => 'scheduled']))
        ->assertInertia(fn (Assert $page) => $page->where('assignments.total', 15));
});

test('managers can open assignment form preselected from either profile', function () {
    $data = assignmentData();
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->get(route('assignments.create', ['vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('assignments/create')
        ->where('defaults.vehicle_id', (string) $data['vehicle_id'])
        ->where('defaults.driver_id', (string) $data['driver_id'])
        ->has('vehicles', 1)->has('drivers', 1));
});

test('regular users cannot create or end assignments', function () {
    $assignment = VehicleDriverAssignment::factory()->create();
    $this->actingAs(User::factory()->create())
        ->get(route('assignments.create'))->assertForbidden();
    $this->post(route('assignments.store'), assignmentData())->assertForbidden();
    $this->patch(route('assignments.end', $assignment), ['end_date' => '2026-09-08'])->assertForbidden();
    expect(VehicleDriverAssignment::query()->count())->toBe(1);
});

test('guests cannot access assignment routes', function () {
    $assignment = VehicleDriverAssignment::factory()->create();
    $this->get(route('assignments.index'))->assertRedirect(route('login'));
    $this->get(route('assignments.create'))->assertRedirect(route('login'));
    $this->post(route('assignments.store'), [])->assertRedirect(route('login'));
    $this->patch(route('assignments.end', $assignment), [])->assertRedirect(route('login'));
});
