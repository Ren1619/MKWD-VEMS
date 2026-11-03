<?php

namespace App\Actions\Drivers;

use App\Integrations\Hris\EmployeeDirectory;
use App\Models\Driver;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class SaveDriver
{
    public function __construct(private readonly EmployeeDirectory $employees) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data, ?Driver $driver = null): Driver
    {
        $creating = $driver === null;
        $driver ??= new Driver;

        if ($creating) {
            $driver->source_type = $data['source_type'] ?? Driver::SOURCE_HRIS;
        }

        if ($driver->isOutsourced()) {
            $driver->fill(Arr::only($data, Driver::PROFILE_FIELDS));
            $driver->hris_employee_id = null;
            $driver->is_present_in_hris = false;
            $driver->last_synced_at = null;
        } elseif ($creating) {
            $employee = $this->employees->find($data['hris_employee_id']);

            if ($employee === null) {
                throw ValidationException::withMessages([
                    'hris_employee_id' => 'This employee is no longer available in HRIS. Search and select an employee again.',
                ]);
            }

            $driver->fill([
                ...$employee->profileAttributes(),
                'hris_employee_id' => $employee->id,
                'last_synced_at' => now(),
            ]);
        }

        $driver->fill(Arr::only($data, Driver::LOCAL_FIELDS));
        $changedFields = array_keys($driver->getDirty());

        try {
            $driver->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'license_number' => 'This employee or license is already registered as a driver.',
            ]);
        }

        Log::info($creating ? 'Driver registered.' : 'Driver details updated.', [
            'driver_id' => $driver->id,
            'changed_fields' => $changedFields,
        ]);

        return $driver;
    }
}
