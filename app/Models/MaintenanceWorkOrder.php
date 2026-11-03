<?php

namespace App\Models;

use Database\Factories\MaintenanceWorkOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reference_number', 'vehicle_id', 'maintenance_request_id', 'maintenance_schedule_id', 'opened_by', 'status', 'title', 'instructions', 'service_provider', 'scheduled_start', 'scheduled_end', 'started_at', 'completed_at', 'completion_mileage', 'work_performed', 'cost', 'completed_by', 'cancelled_at', 'cancelled_by', 'cancellation_reason'])]
class MaintenanceWorkOrder extends Model
{
    /** @use HasFactory<MaintenanceWorkOrderFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<MaintenanceRequest, $this> */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /** @return BelongsTo<MaintenanceSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['scheduled_start' => 'date:Y-m-d', 'scheduled_end' => 'date:Y-m-d', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'completion_mileage' => 'integer', 'cost' => 'decimal:2', 'cancelled_at' => 'datetime'];
    }
}
