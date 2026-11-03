<?php

namespace App\Http\Requests\Drivers;

use App\Models\Driver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('drivers.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('license_number'))) {
            $this->merge(['license_number' => Str::upper(trim($this->input('license_number')))]);
        }
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $driver = $this->route('driver');
        $outsourced = $driver instanceof Driver
            ? $driver->isOutsourced()
            : $this->input('source_type') === Driver::SOURCE_OUTSOURCED;
        $licenseUnique = Rule::unique(Driver::class, 'license_number');

        if ($driver instanceof Driver) {
            $licenseUnique->ignore($driver);
        }

        return [
            'source_type' => $driver instanceof Driver
                ? ['missing']
                : ['sometimes', 'required', Rule::in([Driver::SOURCE_HRIS, Driver::SOURCE_OUTSOURCED])],
            'hris_employee_id' => $driver instanceof Driver || $outsourced
                ? ['missing']
                : ['required', 'string', 'max:100', Rule::unique(Driver::class, 'hris_employee_id')],
            ...($outsourced ? [
                'employee_name' => ['required', 'string', 'max:255'],
                'employee_number' => ['nullable', 'string', 'max:255'],
                'employee_email' => ['nullable', 'email', 'max:255'],
                'employee_phone' => ['nullable', 'string', 'max:50'],
                'department' => ['missing'],
                'position' => ['missing'],
            ] : array_fill_keys(Driver::PROFILE_FIELDS, ['missing'])),
            'license_number' => ['required', 'string', 'max:100', $licenseUnique],
            'license_type' => ['required', Rule::in(array_keys(Driver::licenseTypes()))],
            'license_expiry' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(Driver::statuses())],
            'driving_authorized' => ['required', 'boolean'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_source' => ['required_with:contact_phone,contact_email', 'nullable', 'string', 'max:255'],
            ...array_fill_keys([
                'is_present_in_hris', 'last_synced_at',
                'eligible_to_drive', 'eligibility_reasons',
            ], ['missing']),
        ];
    }
}
