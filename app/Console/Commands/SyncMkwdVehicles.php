<?php

namespace App\Console\Commands;

use App\Services\Vehicles\SynchronizeMkwdVehicles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('mkwd:sync-vehicles')]
#[Description('Synchronize the complete MKWD IMS vehicle snapshot')]
class SyncMkwdVehicles extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SynchronizeMkwdVehicles $synchronizeMkwdVehicles): int
    {
        try {
            $result = $synchronizeMkwdVehicles->handle();
        } catch (Throwable $exception) {
            report($exception);

            $this->error('Vehicle synchronization failed.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'created=%d updated=%d unchanged=%d archived=%d reactivated=%d failed=%d',
            $result->created,
            $result->updated,
            $result->unchanged,
            $result->archived,
            $result->reactivated,
            $result->failed,
        ));

        return self::SUCCESS;
    }
}
