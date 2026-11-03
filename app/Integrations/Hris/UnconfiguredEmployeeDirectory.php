<?php

namespace App\Integrations\Hris;

use App\Exceptions\HrisUnavailableException;

/**
 * Replaced with the HRIS adapter once its endpoint, authentication and payload are supplied.
 */
final class UnconfiguredEmployeeDirectory implements EmployeeDirectory
{
    public function search(string $search): array
    {
        throw new HrisUnavailableException;
    }

    public function find(string $employeeId): ?EmployeeData
    {
        throw new HrisUnavailableException;
    }
}
