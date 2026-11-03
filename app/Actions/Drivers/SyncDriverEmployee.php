<?php

namespace App\Actions\Drivers;

use App\Integrations\Hris\EmployeeDirectory;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class SyncDriverEmployee
{
    public function __construct(private readonly EmployeeDirectory $employees) {}

    public function handle(Driver $driver): bool
    {
        if ($driver->isOutsourced() || $driver->hris_employee_id === null) {
            throw ValidationException::withMessages([
                'hris_sync' => 'Outsourced drivers are managed locally and cannot be refreshed from HRIS.',
            ]);
        }

        $employee = $this->employees->find($driver->hris_employee_id);

        if ($employee === null) {
            $driver->update(['is_present_in_hris' => false]);
            Log::warning('Driver employee was not found in HRIS.', ['driver_id' => $driver->id]);

            return false;
        }

        $driver->update([
            ...$employee->profileAttributes(),
            'is_present_in_hris' => true,
            'last_synced_at' => now(),
        ]);

        Log::info('Driver employee information refreshed from HRIS.', ['driver_id' => $driver->id]);

        return true;
    }
}
