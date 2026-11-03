<?php

namespace App\Integrations\Hris;

interface EmployeeDirectory
{
    /**
     * Return up to 30 matching employee profiles, including stable HRIS IDs.
     *
     * @return list<EmployeeData>
     */
    public function search(string $search): array;

    /**
     * Return null only when HRIS definitively confirms the employee is missing.
     */
    public function find(string $employeeId): ?EmployeeData;
}
