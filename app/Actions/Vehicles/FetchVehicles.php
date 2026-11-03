<?php

namespace App\Actions\Vehicles;

use App\Integrations\MkwdIms\MkwdImsClient;
use Illuminate\Support\Arr;

/**
 * @phpstan-type AmsVehicle array{
 *     id: string,
 *     property_tag_uuid: ?string,
 *     property_number: ?string,
 *     serial_number: ?string,
 *     plate_number: ?string,
 *     name: string,
 *     brand: ?string,
 *     model: ?string,
 *     vehicle_year: ?int,
 *     description: ?string,
 *     category_code: ?string,
 *     category_name: ?string,
 *     subcategory_code: ?string,
 *     subcategory_name: ?string,
 *     unit_of_measure: ?string,
 *     location: ?string,
 *     office_name: ?string,
 *     nature_of_occupancy: ?string,
 *     fund_cluster: ?string,
 *     acquisition_date: ?string,
 *     acquisition_cost: ?float,
 *     lifecycle_status: ?string,
 *     condition_status: ?string,
 *     custody_status: ?string,
 *     current_custodian_code: ?string,
 *     current_custodian: ?string,
 *     current_borrower_code: ?string,
 *     current_borrower: ?string,
 *     created_at: ?string,
 *     updated_at: ?string
 * }
 */
final class FetchVehicles
{
    public function __construct(private readonly MkwdImsClient $client) {}

    /**
     * @return list<AmsVehicle>
     */
    public function handle(): array
    {
        return array_values(
            collect($this->client->vehicles())
                ->map(fn (array $vehicle): array => $this->normalize($vehicle))
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $vehicle
     * @return AmsVehicle
     */
    private function normalize(array $vehicle): array
    {
        return [
            'id' => (string) Arr::get($vehicle, 'id', Arr::get($vehicle, 'property_tag_uuid', '')),
            'property_tag_uuid' => $this->nullableString(Arr::get($vehicle, 'property_tag_uuid')),
            'property_number' => $this->nullableString(Arr::get($vehicle, 'property_number')),
            'serial_number' => $this->nullableString(Arr::get($vehicle, 'serial_number')),
            'plate_number' => $this->nullableString(Arr::get($vehicle, 'plate_number', Arr::get($vehicle, 'license_plate'))),
            'name' => $this->nullableString(Arr::get($vehicle, 'name')) ?? 'Unnamed asset',
            'brand' => $this->nullableString(Arr::get($vehicle, 'brand')),
            'model' => $this->nullableString(Arr::get($vehicle, 'model')),
            'vehicle_year' => $this->nullableInteger(Arr::get($vehicle, 'vehicle_year', Arr::get($vehicle, 'year'))),
            'description' => $this->nullableString(Arr::get($vehicle, 'description')),
            'category_code' => $this->nullableString(Arr::get($vehicle, 'category.code')),
            'category_name' => $this->nullableString(Arr::get($vehicle, 'category.name')),
            'subcategory_code' => $this->nullableString(Arr::get($vehicle, 'subcategory.code')),
            'subcategory_name' => $this->nullableString(Arr::get($vehicle, 'subcategory.name')),
            'unit_of_measure' => $this->nullableString(Arr::get($vehicle, 'unit_of_measure')),
            'location' => $this->nullableString(Arr::get($vehicle, 'location')),
            'office_name' => $this->nullableString(Arr::get($vehicle, 'office.name', Arr::get($vehicle, 'assigned_office.name'))),
            'nature_of_occupancy' => $this->nullableString(Arr::get($vehicle, 'nature_of_occupancy')),
            'fund_cluster' => $this->nullableString(Arr::get($vehicle, 'fund_cluster')),
            'acquisition_date' => $this->nullableString(Arr::get($vehicle, 'acquisition_date')),
            'acquisition_cost' => $this->nullableFloat(Arr::get($vehicle, 'acquisition_cost')),
            'lifecycle_status' => $this->nullableString(Arr::get($vehicle, 'lifecycle_status')),
            'condition_status' => $this->nullableString(Arr::get($vehicle, 'condition_status')),
            'custody_status' => $this->nullableString(Arr::get($vehicle, 'custody_status')),
            'current_custodian_code' => $this->nullableString(Arr::get($vehicle, 'current_custodian.code')),
            'current_custodian' => $this->nullableString(Arr::get($vehicle, 'current_custodian.name', Arr::get($vehicle, 'current_custodian'))),
            'current_borrower_code' => $this->nullableString(Arr::get($vehicle, 'current_borrower.code')),
            'current_borrower' => $this->nullableString(Arr::get($vehicle, 'current_borrower.name', Arr::get($vehicle, 'current_borrower'))),
            'created_at' => $this->nullableString(Arr::get($vehicle, 'created_at')),
            'updated_at' => $this->nullableString(Arr::get($vehicle, 'updated_at')),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue === '' ? null : $normalizedValue;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
