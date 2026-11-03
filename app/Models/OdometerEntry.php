<?php

namespace App\Models;

use Database\Factories\OdometerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_id', 'trip_id', 'recorded_by', 'entry_type', 'previous_reading',
    'reading', 'notes', 'recorded_at',
])]
class OdometerEntry extends Model
{
    /** @use HasFactory<OdometerEntryFactory> */
    use HasFactory;

    public const TYPE_TRIP_DEPARTURE = 'trip_departure';

    public const TYPE_TRIP_RETURN = 'trip_return';

    public const TYPE_MANUAL_CORRECTION = 'manual_correction';

    public const TYPE_REFUEL = 'refuel';

    /** @return list<string> */
    public static function types(): array
    {
        return [self::TYPE_TRIP_DEPARTURE, self::TYPE_TRIP_RETURN, self::TYPE_MANUAL_CORRECTION, self::TYPE_REFUEL];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Trip, $this> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'previous_reading' => 'integer',
            'reading' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }
}
