<?php

use App\Exceptions\HrisUnavailableException;
use App\Integrations\Hris\EmployeeData;
use App\Integrations\Hris\EmployeeDirectory;
use App\Models\Driver;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
});

function driverRegistrationData(array $overrides = []): array
{
    return [
        'hris_employee_id' => 'EMP-100',
        'license_number' => 'N01-22-123456',
        'license_type' => 'professional',
        'license_expiry' => today()->addYear()->toDateString(),
        'status' => Driver::STATUS_ACTIVE,
        'driving_authorized' => true,
        ...$overrides,
    ];
}

test('drivers can be listed filtered and viewed without contacting hris', function () {
    $driver = Driver::factory()->eligible()->create(['employee_name' => 'Maria Driver']);
    Driver::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('drivers.index', ['search' => 'Maria', 'status' => Driver::STATUS_ACTIVE]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('drivers/index')
            ->has('drivers.data', 1)
            ->where('drivers.data.0.id', $driver->id)
            ->where('drivers.data.0.eligible_to_drive', true)
            ->where('canManage', false)
        );

    $this->get(route('drivers.show', $driver))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('drivers/show')
            ->where('driver.license_number', $driver->license_number)
            ->where('driver.license_expiry', $driver->license_expiry->toDateString())
            ->where('driver.eligible_to_drive', true)
            ->where('canManage', false)
        );
});

test('managers can open registration and search hris employees with duplicate indicators', function () {
    Driver::factory()->create(['hris_employee_id' => 'EMP-100']);
    $this->mock(EmployeeDirectory::class)->shouldReceive('search')->once()->with('Maria')
        ->andReturn([
            new EmployeeData('EMP-100', 'Maria Driver', '100', 'maria@example.test', null, 'Operations'),
            new EmployeeData('EMP-101', 'Maria Other'),
        ]);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->get(route('drivers.create'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('drivers/create')->has('licenseTypes'));

    $this->getJson(route('drivers.employees', ['search' => 'Maria']))
        ->assertOk()
        ->assertJsonPath('employees.0.employee_name', 'Maria Driver')
        ->assertJsonPath('employees.0.department', 'Operations')
        ->assertJsonPath('employees.0.is_registered', true)
        ->assertJsonPath('employees.1.is_registered', false);
});

test('driver registration uses authoritative employee details and stores local license and contacts', function () {
    $this->mock(EmployeeDirectory::class)->shouldReceive('find')->once()->with('EMP-100')
        ->andReturn(new EmployeeData('EMP-100', 'Maria Driver', '100', 'work@example.test', '09171234567', 'Operations', 'Operator'));

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.store'), driverRegistrationData([
            'license_number' => ' n01-22-123456 ',
            'contact_phone' => '09991234567',
            'contact_email' => 'personal@example.test',
            'contact_source' => 'Employee confirmed',
        ]))->assertRedirect();

    $driver = Driver::query()->sole();

    expect($driver)
        ->employee_name->toBe('Maria Driver')
        ->employee_email->toBe('work@example.test')
        ->department->toBe('Operations')
        ->license_number->toBe('N01-22-123456')
        ->contact_source->toBe('Employee confirmed')
        ->contact_phone->toBe('09991234567')
        ->contact_email->toBe('personal@example.test');
    expect($driver->isEligibleToDrive())->toBeTrue();
});

test('hris owned fields cannot be forged when registering or updating a driver', function () {
    $manager = User::factory()->vehicleManager()->create();
    $driver = Driver::factory()->create();
    $directory = $this->mock(EmployeeDirectory::class);
    $directory->shouldNotReceive('find');

    $this->actingAs($manager)->post(route('drivers.store'), driverRegistrationData([
        'employee_name' => 'Forged name',
        'employee_email' => 'forged@example.test',
    ]))->assertSessionHasErrors(['employee_name', 'employee_email']);

    $this->patch(route('drivers.update', $driver), [
        ...driverRegistrationData(),
        'eligible_to_drive' => true,
        'last_synced_at' => now()->toISOString(),
    ])->assertSessionHasErrors(['hris_employee_id', 'eligible_to_drive', 'last_synced_at']);

    expect(Driver::query()->count())->toBe(1);
});

test('registered employee and license cannot be duplicated', function (string $field) {
    $driver = Driver::factory()->create(['hris_employee_id' => 'EMP-100', 'license_number' => 'N01-22-123456']);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.store'), driverRegistrationData([
            'hris_employee_id' => $field === 'hris_employee_id' ? $driver->hris_employee_id : 'EMP-200',
            'license_number' => $field === 'license_number' ? $driver->license_number : 'N99-99-999999',
        ]))->assertSessionHasErrors($field);

    expect(Driver::query()->count())->toBe(1);
})->with(['hris_employee_id', 'license_number']);

test('local driver details can be updated while hris is disconnected', function () {
    $driver = Driver::factory()->create(['employee_name' => 'Preserved Name']);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('drivers.update', $driver), [
            'license_number' => $driver->license_number,
            'license_type' => 'non_professional',
            'license_expiry' => today()->addYears(2)->toDateString(),
            'status' => Driver::STATUS_ACTIVE,
            'driving_authorized' => true,
        ])->assertRedirect(route('drivers.show', $driver))->assertSessionHasNoErrors();

    expect($driver->refresh())
        ->employee_name->toBe('Preserved Name')
        ->license_type->toBe('non_professional')
        ->status->toBe(Driver::STATUS_ACTIVE);
    expect($driver->isEligibleToDrive())->toBeTrue();
});

test('invalid driver details are rejected', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.store'), driverRegistrationData($overrides))
        ->assertSessionHasErrors($field);
    expect(Driver::query()->count())->toBe(0);
})->with([
    'missing employee' => [['hris_employee_id' => ''], 'hris_employee_id'],
    'missing license' => [['license_number' => ''], 'license_number'],
    'long license' => [['license_number' => str_repeat('A', 101)], 'license_number'],
    'invalid type' => [['license_type' => 'unknown'], 'license_type'],
    'invalid expiry' => [['license_expiry' => '2026-02-30'], 'license_expiry'],
    'invalid status' => [['status' => 'unknown'], 'status'],
    'invalid approval' => [['driving_authorized' => 'yes'], 'driving_authorized'],
    'invalid contact email' => [['contact_email' => 'invalid'], 'contact_email'],
    'missing contact source' => [['contact_phone' => '09171234567'], 'contact_source'],
]);

test('driver eligibility reflects inactive unapproved expired and missing employee conditions', function (array $attributes, string $reason) {
    $driver = Driver::factory()->eligible()->create($attributes);
    expect($driver->isEligibleToDrive())->toBeFalse();
    expect($driver->eligibilityReasons())->toContain($reason);
})->with([
    'inactive' => [['status' => Driver::STATUS_INACTIVE], 'Driver is inactive.'],
    'not approved' => [['driving_authorized' => false], 'Driving approval has not been granted.'],
    'expired' => [fn () => ['license_expiry' => today()->subDay()], 'License has expired.'],
    'missing HRIS employee' => [['is_present_in_hris' => false], 'Employee was not found in HRIS; review is required.'],
]);

test('eligibility automatically expires after the license expiry day', function () {
    $this->travelTo(now()->setDate(2026, 9, 8)->startOfDay());
    $driver = Driver::factory()->eligible()->create(['license_expiry' => '2026-09-08']);
    expect($driver->isEligibleToDrive())->toBeTrue();
    $this->travel(1)->days();
    expect($driver->isEligibleToDrive())->toBeFalse();
});

test('expired licenses are retained but do not confer eligibility', function () {
    $this->mock(EmployeeDirectory::class)->shouldReceive('find')->andReturn(new EmployeeData('EMP-100', 'Expired Driver'));

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.store'), driverRegistrationData(['license_expiry' => today()->subDay()->toDateString()]))
        ->assertSessionHasNoErrors();

    expect(Driver::query()->sole()->isEligibleToDrive())->toBeFalse();
});

test('refreshing employee data preserves license status approval and alternate contacts', function () {
    $driver = Driver::factory()->eligible()->create(['contact_phone' => '09991234567', 'contact_source' => 'Employee']);
    $before = $driver->only(Driver::LOCAL_FIELDS);
    $this->mock(EmployeeDirectory::class)->shouldReceive('find')->once()->with($driver->hris_employee_id)
        ->andReturn(new EmployeeData($driver->hris_employee_id, 'Updated Name', null, 'updated@example.test'));

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.sync', $driver))->assertRedirect(route('drivers.show', $driver));

    expect($driver->refresh()->only(Driver::LOCAL_FIELDS))->toEqual($before);
    expect($driver->employee_name)->toBe('Updated Name');
    expect(Driver::query()->count())->toBe(1);
});

test('hris failures preserve all saved driver data', function () {
    $driver = Driver::factory()->eligible()->create();
    $before = $driver->refresh()->getAttributes();
    $this->mock(EmployeeDirectory::class)->shouldReceive('find')->andThrow(new HrisUnavailableException);

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->from(route('drivers.show', $driver))
        ->post(route('drivers.sync', $driver))
        ->assertRedirect(route('drivers.show', $driver))
        ->assertSessionHasErrors('hris_sync');

    expect($driver->refresh()->getAttributes())->toBe($before);
    $this->get(route('drivers.show', $driver))->assertOk();
});

test('missing employees are retained and returning employees can be refreshed', function () {
    $driver = Driver::factory()->eligible()->create();
    $directory = $this->mock(EmployeeDirectory::class);
    $directory->shouldReceive('find')->once()->andReturnNull();

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.sync', $driver))->assertRedirect();

    $this->assertModelExists($driver);
    expect($driver->refresh()->isEligibleToDrive())->toBeFalse();
    expect($driver->driving_authorized)->toBeTrue();

    $this->mock(EmployeeDirectory::class)->shouldReceive('find')
        ->andReturn(new EmployeeData($driver->hris_employee_id, 'Returning Employee'));
    $this->post(route('drivers.sync', $driver))->assertRedirect();
    expect($driver->refresh()->isEligibleToDrive())->toBeTrue();
});

test('unconfigured hris gives a clear error and cannot create fabricated drivers', function () {
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->getJson(route('drivers.employees', ['search' => 'Maria']))
        ->assertStatus(503)
        ->assertJsonPath('message', (new HrisUnavailableException)->getMessage());

    $this->post(route('drivers.store'), driverRegistrationData())->assertSessionHasErrors('hris_employee_id');
    expect(Driver::query()->count())->toBe(0);
});

test('unknown employee ids cannot register drivers', function () {
    $this->mock(EmployeeDirectory::class)->shouldReceive('find')->andReturnNull();
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.store'), driverRegistrationData())->assertSessionHasErrors('hris_employee_id');
    expect(Driver::query()->count())->toBe(0);
});

test('regular users cannot register search edit or synchronize drivers', function () {
    $driver = Driver::factory()->create();
    $this->actingAs(User::factory()->create());
    $this->get(route('drivers.create'))->assertForbidden();
    $this->getJson(route('drivers.employees', ['search' => 'Maria']))->assertForbidden();
    $this->post(route('drivers.store'), driverRegistrationData())->assertForbidden();
    $this->patch(route('drivers.update', $driver), driverRegistrationData())->assertForbidden();
    $this->post(route('drivers.sync', $driver))->assertForbidden();
});

test('guests cannot access drivers and missing driver ids return 404', function () {
    $this->get(route('drivers.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('drivers.show', 999999))->assertNotFound();
});

test('optional employee fields can be absent', function () {
    $driver = Driver::factory()->create(['employee_number' => null, 'employee_email' => null, 'employee_phone' => null, 'department' => null, 'position' => null]);
    $this->actingAs(User::factory()->create())->get(route('drivers.show', $driver))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('driver.department', null)->where('driver.contact_phone', null));
});
