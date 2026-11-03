<?php

namespace App\Models;

use Database\Factories\VehicleDriverAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

#[Fillable(['vehicle_id', 'driver_id', 'start_date', 'end_date', 'assignment_type', 'closed_at'])]
class VehicleDriverAssignment extends Model
{
    /** @use HasFactory<VehicleDriverAssignmentFactory> */
    use HasFactory;

    protected $appends = ['status', 'can_end'];

    /** @return list<string> */
    public static function types(): array
    {
        return ['primary', 'temporary'];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** @param Builder<VehicleDriverAssignment> $query */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('start_date', '<=', today()->toDateString())
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', today()->toDateString()));
    }

    public function getStatusAttribute(): string
    {
        if (Date::parse($this->start_date)->gt(today())) {
            return 'scheduled';
        }

        return $this->end_date !== null && Date::parse($this->end_date)->lt(today()) ? 'ended' : 'current';
    }

    public function getCanEndAttribute(): bool
    {
        return $this->closed_at === null && $this->getStatusAttribute() === 'current';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'closed_at' => 'datetime'];
    }
}
