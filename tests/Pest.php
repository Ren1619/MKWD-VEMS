<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function amsVehicle(array $overrides = []): array
{
    return array_replace([
        'id' => 5,
        'property_tag_uuid' => '142e1baf-a0b5-44df-8673-ff6cb9e6df2e',
        'property_number' => 'PROP-2018-003',
        'serial_number' => 'MKWD-PROP-0005',
        'plate_number' => 'ABC-1234',
        'name' => 'Utility Motorcycle',
        'brand' => 'Honda',
        'model' => 'XR150L',
        'year' => 2018,
        'description' => 'Field service motorcycle.',
        'category' => ['code' => 'AST-TRN', 'name' => 'Transportation Equipment'],
        'subcategory' => ['code' => 'AST-TRN-MC', 'name' => 'Motorcycles'],
        'unit_of_measure' => 'unit',
        'location' => 'Motor Pool',
        'office' => ['name' => 'Operations Division'],
        'nature_of_occupancy' => 'Owned',
        'fund_cluster' => 'Corporate Fund',
        'acquisition_date' => '2018-03-19',
        'acquisition_cost' => '89900.00',
        'lifecycle_status' => 'active',
        'condition_status' => 'serviceable',
        'custody_status' => 'available',
        'current_custodian' => ['code' => 'EMP-001', 'name' => 'Juan Dela Cruz'],
        'current_borrower' => null,
        'created_at' => '2018-03-19T02:54:10.000000Z',
        'updated_at' => '2026-09-01T02:54:10.000000Z',
    ], $overrides);
}
