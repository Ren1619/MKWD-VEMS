<?php

use App\Integrations\Hris\EmployeeDirectory;
use App\Models\Driver;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->mock(EmployeeDirectory::class)->shouldNotReceive('find');
});

function outsourcedDriverData(array $overrides = []): array
{
    return [
        'source_type' => Driver::SOURCE_OUTSOURCED,
        'employee_name' => 'Outside Driver',
        'employee_number' => 'CONTRACT-100',
        'employee_email' => 'driver@example.test',
        'employee_phone' => '09171234567',
        'license_number' => 'OUT-100',
        'license_type' => 'professional',
        'license_expiry' => today()->addYear()->toDateString(),
        'status' => Driver::STATUS_ACTIVE,
        'driving_authorized' => true,
        ...$overrides,
    ];
}

test('managers can register multiple outsourced drivers without hris ids', function () {
    $this->actingAs(User::factory()->vehicleManager()->create());
    foreach (['OUT-100', 'OUT-200'] as $license) {
        $this->post(route('drivers.store'), outsourcedDriverData(['license_number' => $license]))
            ->assertRedirect()->assertSessionHasNoErrors();
    }
    expect(Driver::query()->count())->toBe(2);
    $driver = Driver::query()->firstOrFail();
    expect($driver)->hris_employee_id->toBeNull()
        ->source_type->toBe(Driver::SOURCE_OUTSOURCED)
        ->employee_name->toBe('Outside Driver')
        ->employee_email->toBe('driver@example.test')
        ->employee_phone->toBe('09171234567')
        ->department->toBeNull()
        ->position->toBeNull()
        ->last_synced_at->toBeNull();
    expect($driver->isEligibleToDrive())->toBeTrue();
});

test('outsourced profile and license details remain editable after registration', function () {
    $driver = Driver::factory()->outsourced()->create();
    $data = outsourcedDriverData(['employee_name' => 'Updated Driver', 'employee_email' => null]);
    unset($data['source_type']);
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('drivers.update', $driver), $data)->assertSessionHasNoErrors();
    expect($driver->refresh())->employee_name->toBe('Updated Driver')->employee_email->toBeNull()
        ->license_number->toBe('OUT-100')->hris_employee_id->toBeNull();
    $this->get(route('drivers.show', $driver))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('driver.source_type', Driver::SOURCE_OUTSOURCED)
            ->where('driver.employee_name', 'Updated Driver')
            ->where('driver.eligible_to_drive', true));
});

test('outsourced profiles validate manual identity fields', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.store'), outsourcedDriverData($overrides))->assertSessionHasErrors($field);
    expect(Driver::query()->count())->toBe(0);
})->with([
    'missing name' => [['employee_name' => ''], 'employee_name'],
    'long name' => [['employee_name' => str_repeat('a', 256)], 'employee_name'],
    'invalid email' => [['employee_email' => 'invalid'], 'employee_email'],
    'long phone' => [['employee_phone' => str_repeat('1', 51)], 'employee_phone'],
    'department supplied' => [['department' => 'Contractor'], 'department'],
    'position supplied' => [['position' => 'Driver'], 'position'],
    'HRIS id supplied' => [['hris_employee_id' => 'EMP-1'], 'hris_employee_id'],
    'invalid source' => [['source_type' => 'other'], 'source_type'],
]);

test('outsourced drivers cannot sync with hris', function () {
    $driver = Driver::factory()->outsourced()->create();
    $before = $driver->refresh()->getAttributes();
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('drivers.sync', $driver))->assertSessionHasErrors('hris_sync');
    expect($driver->refresh()->getAttributes())->toBe($before);
});

test('changing source cannot bypass hris profile protection', function () {
    $driver = Driver::factory()->create();
    $this->actingAs(User::factory()->vehicleManager()->create())
        ->patch(route('drivers.update', $driver), outsourcedDriverData())
        ->assertSessionHasErrors(['source_type', 'employee_name']);
    expect($driver->refresh()->source_type)->toBe(Driver::SOURCE_HRIS);
});

test('outsourced drivers still require approval active status and valid licenses', function () {
    $driver = Driver::factory()->outsourced()->eligible()->create(['license_expiry' => today()->subDay()]);
    expect($driver->isEligibleToDrive())->toBeFalse();
    expect($driver->eligibilityReasons())->toBe(['License has expired.']);
});

test('viewers cannot create or edit outsourced profiles', function () {
    $driver = Driver::factory()->outsourced()->create();
    $this->actingAs(User::factory()->create());
    $this->post(route('drivers.store'), outsourcedDriverData())->assertForbidden();
    $this->patch(route('drivers.update', $driver), outsourcedDriverData())->assertForbidden();
});
