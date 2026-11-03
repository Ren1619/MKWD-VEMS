<?php

namespace App\Models;

use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reference_number', 'requested_by', 'vehicle_id', 'driver_id', 'status', 'destination',
    'purpose', 'passengers', 'planned_departure', 'planned_return', 'decided_by', 'decided_at',
    'decision_notes', 'dispatched_by', 'actual_departure', 'start_odometer', 'returned_by',
    'actual_return', 'end_odometer',
])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_COMPLETED = 'completed';

    protected $attributes = ['status' => self::STATUS_PENDING];

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_DISPATCHED, self::STATUS_COMPLETED];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
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

    /** @return BelongsTo<User, $this> */
    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<User, $this> */
    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    /** @return BelongsTo<User, $this> */
    public function returnRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'planned_departure' => 'datetime',
            'planned_return' => 'datetime',
            'decided_at' => 'datetime',
            'actual_departure' => 'datetime',
            'actual_return' => 'datetime',
            'start_odometer' => 'integer',
            'end_odometer' => 'integer',
        ];
    }
}
