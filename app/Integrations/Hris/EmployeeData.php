<?php

namespace App\Integrations\Hris;

final readonly class EmployeeData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $employeeNumber = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $department = null,
        public ?string $position = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function profileAttributes(): array
    {
        return [
            'employee_name' => $this->name,
            'employee_number' => $this->employeeNumber,
            'employee_email' => $this->email,
            'employee_phone' => $this->phone,
            'department' => $this->department,
            'position' => $this->position,
        ];
    }
}
