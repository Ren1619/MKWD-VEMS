<?php

namespace App\Actions\Trips;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTrip
{
    /** @param array{destination: string, purpose: string, passengers?: ?string, planned_departure: string, planned_return: string} $data */
    public function handle(User $requester, array $data): Trip
    {
        return DB::transaction(function () use ($requester, $data): Trip {
            $trip = Trip::query()->create([...$data, 'requested_by' => $requester->id]);
            $trip->update(['reference_number' => 'TRIP-'.now()->format('Y').'-'.str_pad((string) $trip->id, 6, '0', STR_PAD_LEFT)]);

            return $trip;
        });
    }
}
