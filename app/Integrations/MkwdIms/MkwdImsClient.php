<?php

namespace App\Integrations\MkwdIms;

use DateTimeImmutable;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class MkwdImsClient
{
    /** @var list<string> */
    private const REQUIRED_VEHICLE_FIELDS = [
        'id',
        'property_tag_uuid',
        'property_number',
        'serial_number',
        'name',
        'brand',
        'model',
        'description',
        'category',
        'subcategory',
        'unit_of_measure',
        'location',
        'nature_of_occupancy',
        'fund_cluster',
        'acquisition_date',
        'acquisition_cost',
        'lifecycle_status',
        'condition_status',
        'custody_status',
        'current_custodian',
        'current_borrower',
        'created_at',
        'updated_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function vehicles(): array
    {
        $baseUrl = Str::of((string) config('services.mkwd_ims.base_url'))->trim()->rtrim('/')->toString();
        $apiToken = (string) config('services.mkwd_ims.api_token');

        if ($baseUrl === '' || $apiToken === '') {
            throw MkwdImsException::missingConfiguration();
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->withToken($apiToken)
                ->connectTimeout((int) config('services.mkwd_ims.connect_timeout'))
                ->timeout((int) config('services.mkwd_ims.timeout'))
                ->retry(
                    times: (int) config('services.mkwd_ims.retry_attempts'),
                    sleepMilliseconds: (int) config('services.mkwd_ims.retry_delay_milliseconds'),
                    when: fn (Throwable $exception): bool => $this->isTemporaryFailure($exception),
                    throw: false,
                )
                ->get('/api/vehicles');
        } catch (ConnectionException) {
            throw MkwdImsException::connectionFailed();
        } catch (RequestException $exception) {
            $status = $exception->response->status();

            throw $status === 401 || $status === 403
                ? MkwdImsException::authenticationFailed()
                : MkwdImsException::requestFailed($status);
        }

        $this->ensureSuccessful($response);

        return $this->validatedVehicles($response->json());
    }

    private function isTemporaryFailure(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        $status = $exception->response->status();

        return $status === 429 || $status >= 500;
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->unauthorized() || $response->forbidden()) {
            throw MkwdImsException::authenticationFailed();
        }

        if ($response->failed()) {
            throw MkwdImsException::requestFailed($response->status());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function validatedVehicles(mixed $payload): array
    {
        if (! is_array($payload) || ! array_key_exists('data', $payload)) {
            throw MkwdImsException::invalidResponse('the top-level data member is missing');
        }

        $vehicles = $payload['data'];

        if (! is_array($vehicles) || ! array_is_list($vehicles)) {
            throw MkwdImsException::invalidResponse('data must be a JSON array');
        }

        $remoteIds = [];

        foreach ($vehicles as $index => $vehicle) {
            if (! is_array($vehicle)) {
                throw MkwdImsException::invalidResponse("vehicle at index {$index} must be an object");
            }

            $this->validateVehicle($vehicle, $index);

            $remoteId = trim((string) $vehicle['id']);

            if (isset($remoteIds[$remoteId])) {
                throw MkwdImsException::invalidResponse("duplicate vehicle id at index {$index}");
            }

            $remoteIds[$remoteId] = true;
        }

        $this->validateTaxonomyConsistency($vehicles);

        /** @var list<array<string, mixed>> $vehicles */
        return $vehicles;
    }

    /**
     * @param  array<string, mixed>  $vehicle
     */
    private function validateVehicle(array $vehicle, int $index): void
    {
        foreach (self::REQUIRED_VEHICLE_FIELDS as $field) {
            if (! array_key_exists($field, $vehicle)) {
                throw MkwdImsException::invalidResponse("vehicle at index {$index} is missing {$field}");
            }
        }

        if (! array_key_exists('id', $vehicle)
            || ! is_int($vehicle['id']) && ! is_string($vehicle['id'])
            || trim((string) $vehicle['id']) === '') {
            throw MkwdImsException::invalidResponse("vehicle at index {$index} has an invalid id");
        }

        if (! isset($vehicle['name']) || ! is_string($vehicle['name']) || trim($vehicle['name']) === '') {
            throw MkwdImsException::invalidResponse("vehicle at index {$index} has an invalid name");
        }

        foreach ([
            'property_tag_uuid',
            'property_number',
            'serial_number',
            'brand',
            'model',
            'description',
            'unit_of_measure',
            'location',
            'nature_of_occupancy',
            'fund_cluster',
            'lifecycle_status',
            'condition_status',
            'custody_status',
        ] as $field) {
            if (array_key_exists($field, $vehicle) && $vehicle[$field] !== null && ! is_string($vehicle[$field])) {
                throw MkwdImsException::invalidResponse("{$field} at vehicle index {$index} must be a string or null");
            }
        }

        foreach (['category', 'subcategory', 'current_custodian', 'current_borrower'] as $relation) {
            $this->validateRelatedObject($vehicle, $relation, $index);
        }

        if (isset($vehicle['acquisition_cost']) && ! is_numeric($vehicle['acquisition_cost'])) {
            throw MkwdImsException::invalidResponse("acquisition_cost at vehicle index {$index} must be numeric or null");
        }

        $this->validateDate($vehicle, 'acquisition_date', $index, 'Y-m-d');
        $this->validateDate($vehicle, 'created_at', $index);
        $this->validateDate($vehicle, 'updated_at', $index);
    }

    /**
     * @param  list<array<string, mixed>>  $vehicles
     */
    private function validateTaxonomyConsistency(array $vehicles): void
    {
        $categories = [];
        $subcategories = [];

        foreach ($vehicles as $index => $vehicle) {
            if (is_array($vehicle['category'])) {
                $code = trim((string) $vehicle['category']['code']);
                $name = trim((string) $vehicle['category']['name']);

                if (isset($categories[$code]) && $categories[$code] !== $name) {
                    throw MkwdImsException::invalidResponse("category code {$code} has conflicting names at index {$index}");
                }

                $categories[$code] = $name;
            }

            if (is_array($vehicle['subcategory'])) {
                $code = trim((string) $vehicle['subcategory']['code']);
                $identity = [
                    trim((string) $vehicle['subcategory']['name']),
                    is_array($vehicle['category']) ? trim((string) $vehicle['category']['code']) : null,
                ];

                if (isset($subcategories[$code]) && $subcategories[$code] !== $identity) {
                    throw MkwdImsException::invalidResponse("subcategory code {$code} has conflicting values at index {$index}");
                }

                $subcategories[$code] = $identity;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $vehicle
     */
    private function validateRelatedObject(array $vehicle, string $relation, int $index): void
    {
        if (! array_key_exists($relation, $vehicle) || $vehicle[$relation] === null) {
            return;
        }

        if (! is_array($vehicle[$relation])) {
            throw MkwdImsException::invalidResponse("{$relation} at vehicle index {$index} must be an object or null");
        }

        foreach (['code', 'name'] as $field) {
            if (! isset($vehicle[$relation][$field])
                || ! is_string($vehicle[$relation][$field])
                || trim($vehicle[$relation][$field]) === '') {
                throw MkwdImsException::invalidResponse("{$relation}.{$field} at vehicle index {$index} is required");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $vehicle
     */
    private function validateDate(array $vehicle, string $field, int $index, ?string $format = null): void
    {
        if (! array_key_exists($field, $vehicle) || $vehicle[$field] === null) {
            return;
        }

        if (! is_string($vehicle[$field])) {
            throw MkwdImsException::invalidResponse("{$field} at vehicle index {$index} must be a date string or null");
        }

        if ($format !== null) {
            $parsedDate = DateTimeImmutable::createFromFormat("!{$format}", $vehicle[$field]);

            if ($parsedDate === false || $parsedDate->format($format) !== $vehicle[$field]) {
                throw MkwdImsException::invalidResponse("{$field} at vehicle index {$index} has an invalid format");
            }

            return;
        }

        try {
            new DateTimeImmutable($vehicle[$field]);
        } catch (Exception) {
            throw MkwdImsException::invalidResponse("{$field} at vehicle index {$index} has an invalid format");
        }
    }
}
