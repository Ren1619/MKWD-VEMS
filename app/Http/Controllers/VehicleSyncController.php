<?php

namespace App\Http\Controllers;

use App\Actions\Vehicles\SyncAllVehicles;
use App\Actions\Vehicles\SyncVehicle;
use App\Integrations\MkwdIms\MkwdImsException;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VehicleSyncController extends Controller
{
    public function store(Vehicle $vehicle, SyncVehicle $syncVehicle): RedirectResponse
    {
        Gate::authorize('vehicles.sync');

        try {
            $wasFound = $syncVehicle->handle($vehicle);
        } catch (MkwdImsException $exception) {
            Log::warning('Vehicle synchronization with AMS failed.', [
                'vehicle_id' => $vehicle->id,
                'ams_asset_id' => $vehicle->ams_asset_id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            Inertia::flash('toast', ['type' => 'error', 'message' => 'Vehicle sync failed. The last known data was preserved.']);
            Inertia::flash('vehicleSyncWarning', [
                'message' => 'AMS could not be reached. Asset information shown may be outdated.',
                'lastSuccessfulSync' => $vehicle->last_synced_at,
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => $wasFound ? 'success' : 'warning',
            'message' => $wasFound
                ? 'Vehicle synchronized with AMS.'
                : 'The vehicle was not returned by AMS and has been flagged for review.',
        ]);

        return back();
    }

    public function storeAll(SyncAllVehicles $syncAllVehicles): RedirectResponse
    {
        Gate::authorize('vehicles.sync');

        try {
            $result = $syncAllVehicles->handle();
        } catch (MkwdImsException $exception) {
            Log::warning('Fleet synchronization with AMS failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            Inertia::flash('toast', ['type' => 'error', 'message' => 'Fleet sync failed. All last known data was preserved.']);

            return back();
        }

        Inertia::flash('toast', [
            'type' => $result['archived'] === 0 && $result['failed'] === 0 ? 'success' : 'warning',
            'message' => "Created {$result['created']}, updated {$result['updated']}, unchanged {$result['unchanged']}, archived {$result['archived']}, reactivated {$result['reactivated']}.",
        ]);

        return back();
    }
}
