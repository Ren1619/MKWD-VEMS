<?php

namespace App\Actions\Vehicles;

use App\Services\Vehicles\SynchronizeMkwdVehicles;

final class SyncAllVehicles
{
    public function __construct(private readonly SynchronizeMkwdVehicles $synchronizeMkwdVehicles) {}

    /**
     * @return array{created: int, updated: int, unchanged: int, archived: int, reactivated: int, failed: int}
     */
    public function handle(): array
    {
        return $this->synchronizeMkwdVehicles->handle()->toArray();
    }
}
