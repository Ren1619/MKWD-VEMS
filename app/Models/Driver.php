<?php

namespace App\Models;

use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

#[Fillable([
    'source_type', 'hris_employee_id', 'employee_number', 'employee_name', 'employee_email',
    'employee_phone', 'department', 'position', 'license_number', 'license_type',
    'license_expiry', 'status', 'driving_authorized', 'contact_email',
    'contact_phone', 'contact_source', 'is_present_in_hris', 'last_synced_at',
])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const SOURCE_HRIS = 'hris';

    public const SOURCE_OUTSOURCED = 'outsourced';

    public const PROFILE_FIELDS = [
        'employee_name', 'employee_number', 'employee_email', 'employee_phone',
        'department', 'position',
    ];

    public const LOCAL_FIELDS = [
        'license_number', 'license_type', 'license_expiry', 'status',
        'driving_authorized', 'contact_email', 'contact_phone', 'contact_source',
    ];

    protected $attributes = [
        'source_type' => self::SOURCE_HRIS,
        'status' => self::STATUS_INACTIVE,
        'driving_authorized' => false,
        'is_present_in_hris' => true,
    ];

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }

    /** @return array<string, string> */
    public static function licenseTypes(): array
    {
        return [
            'professional' => 'Professional',
            'non_professional' => 'Non-professional',
        ];
    }

    /** @return list<string> */
    public function eligibilityReasons(): array
    {
        $reasons = [];

        if ($this->status !== self::STATUS_ACTIVE) {
            $reasons[] = 'Driver is inactive.';
        }

        if (! $this->driving_authorized) {
            $reasons[] = 'Driving approval has not been granted.';
        }

        if (! $this->isOutsourced() && ! $this->is_present_in_hris) {
            $reasons[] = 'Employee was not found in HRIS; review is required.';
        }

        if (! $this->license_number || ! array_key_exists($this->license_type, self::licenseTypes())) {
            $reasons[] = 'License information is incomplete.';
        }

        if (Date::parse($this->license_expiry)->lt(today())) {
            $reasons[] = 'License has expired.';
        }

        return $reasons;
    }

    public function isEligibleToDrive(): bool
    {
        return $this->eligibilityReasons() === [];
    }

    public function isOutsourced(): bool
    {
        return $this->source_type === self::SOURCE_OUTSOURCED;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'license_expiry' => 'date:Y-m-d',
            'driving_authorized' => 'boolean',
            'is_present_in_hris' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }
}
