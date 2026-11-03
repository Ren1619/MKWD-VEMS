<?php

namespace App\Models;

use Database\Factories\MaintenanceScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;

#[Fillable(['vehicle_id', 'created_by', 'service_type', 'description', 'interval_days', 'interval_km', 'last_completed_date', 'last_completed_mileage', 'next_due_date', 'next_due_mileage', 'is_active'])]
class MaintenanceSchedule extends Model
{
    /** @use HasFactory<MaintenanceScheduleFactory> */
    use HasFactory;

    protected $appends = ['due_status'];

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<MaintenanceWorkOrder, $this> */
    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class);
    }

    public function getDueStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $mileage = $this->vehicle->current_mileage ?? 0;
        if (($this->next_due_date && Date::parse($this->next_due_date)->lt(today())) || ($this->next_due_mileage !== null && $mileage > $this->next_due_mileage)) {
            return 'overdue';
        }
        if (($this->next_due_date && Date::parse($this->next_due_date)->isToday()) || ($this->next_due_mileage !== null && $mileage === $this->next_due_mileage)) {
            return 'due';
        }

        return 'upcoming';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['interval_days' => 'integer', 'interval_km' => 'integer', 'last_completed_date' => 'date:Y-m-d', 'last_completed_mileage' => 'integer', 'next_due_date' => 'date:Y-m-d', 'next_due_mileage' => 'integer', 'is_active' => 'boolean'];
    }
}
