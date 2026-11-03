<?php

namespace App\Services\Vehicles;

final readonly class VehicleSyncResult
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $unchanged,
        public int $archived,
        public int $reactivated,
        public int $failed,
    ) {}

    /**
     * @return array{created: int, updated: int, unchanged: int, archived: int, reactivated: int, failed: int}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'archived' => $this->archived,
            'reactivated' => $this->reactivated,
            'failed' => $this->failed,
        ];
    }
}
