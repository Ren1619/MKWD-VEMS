<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'source_system',
    'ams_asset_id',
    'property_tag_uuid',
    'property_number',
    'serial_number',
    'plate_number',
    'name',
    'brand',
    'model',
    'vehicle_year',
    'description',
    'category_code',
    'category_name',
    'vehicle_category_id',
    'subcategory_code',
    'subcategory_name',
    'vehicle_subcategory_id',
    'unit_of_measure',
    'location',
    'office_name',
    'nature_of_occupancy',
    'fund_cluster',
    'acquisition_date',
    'acquisition_cost',
    'ams_asset_status',
    'ams_condition_status',
    'ams_custody_status',
    'current_custodian_code',
    'current_custodian',
    'current_borrower_code',
    'current_borrower',
    'ams_created_at',
    'ams_updated_at',
    'current_mileage',
    'fuel_type',
    'operational_status',
    'maintenance_interval_km',
    'operational_notes',
    'is_present_in_ams',
    'last_sync_error',
    'last_synced_at',
    'imported_at',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    public const SOURCE_MKWD_IMS = 'mkwd_ims';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_IN_USE = 'in_use';

    public const STATUS_UNDER_MAINTENANCE = 'under_maintenance';

    public const STATUS_OUT_OF_SERVICE = 'out_of_service';

    public const STATUS_RETIRED = 'retired';

    public const FUEL_DIESEL = 'Diesel';

    public const FUEL_GASOLINE = 'Gasoline';

    public const FUEL_ELECTRIC = 'Electric';

    public const FUEL_HYBRID = 'Hybrid';

    protected $attributes = [
        'source_system' => self::SOURCE_MKWD_IMS,
        'current_mileage' => 0,
        'operational_status' => self::STATUS_AVAILABLE,
        'is_present_in_ams' => true,
    ];

    /**
     * @return list<string>
     */
    public static function operationalStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_RESERVED,
            self::STATUS_IN_USE,
            self::STATUS_UNDER_MAINTENANCE,
            self::STATUS_OUT_OF_SERVICE,
            self::STATUS_RETIRED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function fuelTypes(): array
    {
        return [
            self::FUEL_DIESEL,
            self::FUEL_GASOLINE,
            self::FUEL_ELECTRIC,
            self::FUEL_HYBRID,
        ];
    }

    public static function amsStatusRequiresRetirement(?string $status): bool
    {
        return in_array(Str::lower($status ?? ''), ['disposed', 'retired'], true);
    }

    /**
     * @return BelongsTo<VehicleCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    /**
     * @return BelongsTo<VehicleSubcategory, $this>
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(VehicleSubcategory::class, 'vehicle_subcategory_id');
    }

    /** @return HasMany<MaintenanceSchedule, $this> */
    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /** @return HasMany<MaintenanceRequest, $this> */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /** @return HasMany<MaintenanceWorkOrder, $this> */
    public function maintenanceWorkOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class);
    }

    /** @return HasMany<FuelLog, $this> */
    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_year' => 'integer',
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'ams_updated_at' => 'datetime',
            'ams_created_at' => 'datetime',
            'current_mileage' => 'integer',
            'maintenance_interval_km' => 'integer',
            'is_present_in_ams' => 'boolean',
            'last_synced_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }
}
