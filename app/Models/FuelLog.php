<?php

namespace App\Models;

use Database\Factories\FuelLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'odometer_entry_id', 'recorded_by', 'quantity_liters', 'total_cost', 'odometer', 'refueled_at', 'station', 'receipt_reference', 'notes', 'distance_since_previous', 'consumption_km_per_liter', 'cost_per_km', 'price_per_liter'])]
class FuelLog extends Model
{
    /** @use HasFactory<FuelLogFactory> */
    use HasFactory;

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<OdometerEntry, $this> */
    public function odometerEntry(): BelongsTo
    {
        return $this->belongsTo(OdometerEntry::class);
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
            'quantity_liters' => 'decimal:3',
            'total_cost' => 'decimal:2',
            'odometer' => 'integer',
            'refueled_at' => 'datetime',
            'distance_since_previous' => 'integer',
            'consumption_km_per_liter' => 'decimal:3',
            'cost_per_km' => 'decimal:3',
            'price_per_liter' => 'decimal:3',
        ];
    }
}
