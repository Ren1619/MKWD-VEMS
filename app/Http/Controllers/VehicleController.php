<?php

namespace App\Http\Controllers;

use App\Actions\Vehicles\FetchVehicles;
use App\Actions\Vehicles\ImportVehicle;
use App\Actions\Vehicles\UpdateVehicleOperationalInformation;
use App\Http\Requests\Vehicles\IndexVehicleRequest;
use App\Http\Requests\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Vehicles\UpdateVehicleRequest;
use App\Integrations\MkwdIms\MkwdImsException;
use App\Models\OdometerEntry;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(IndexVehicleRequest $request): Response
    {
        $filters = $request->validated();

        $vehicles = Vehicle::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('plate_number', 'like', "%{$search}%")
                        ->orWhere('property_number', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->when($filters['operational_status'] ?? null, fn (Builder $query, string $status) => $query->where('operational_status', $status))
            ->when($filters['ams_asset_status'] ?? null, fn (Builder $query, string $status) => $query->where('ams_asset_status', $status))
            ->when($filters['office_name'] ?? null, fn (Builder $query, string $office) => $query->where('office_name', $office))
            ->latest('imported_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle): array => $vehicle->toArray());

        return Inertia::render('vehicles/index', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'filterOptions' => [
                'operationalStatuses' => Vehicle::operationalStatuses(),
                'amsAssetStatuses' => Vehicle::query()->whereNotNull('ams_asset_status')->distinct()->orderBy('ams_asset_status')->pluck('ams_asset_status'),
                'offices' => Vehicle::query()->whereNotNull('office_name')->distinct()->orderBy('office_name')->pluck('office_name'),
            ],
            'summary' => [
                'total' => Vehicle::query()->count(),
                'available' => Vehicle::query()->where('operational_status', Vehicle::STATUS_AVAILABLE)->count(),
                'maintenance' => Vehicle::query()->where('operational_status', Vehicle::STATUS_UNDER_MAINTENANCE)->count(),
                'needsReview' => Vehicle::query()->where(function (Builder $query): void {
                    $query->where('is_present_in_ams', false)->orWhereNotNull('last_sync_error');
                })->count(),
            ],
            'canManage' => Gate::allows('vehicles.import'),
        ]);
    }

    public function create(FetchVehicles $fetchVehicles): Response
    {
        Gate::authorize('vehicles.import');

        try {
            $amsVehicles = $fetchVehicles->handle();
            $sourceError = null;
        } catch (MkwdImsException $exception) {
            report($exception);

            $amsVehicles = [];
            $sourceError = 'AMS vehicle data is temporarily unavailable. Confirm that the AMS API is running, then try again.';
        }

        $importedAmsIds = Vehicle::query()
            ->where('source_system', Vehicle::SOURCE_MKWD_IMS)
            ->pluck('ams_asset_id')
            ->all();

        $amsVehicles = collect($amsVehicles)->map(fn (array $amsVehicle): array => [
            ...$amsVehicle,
            'is_imported' => in_array($amsVehicle['id'], $importedAmsIds, true),
            'is_eligible' => ! in_array($amsVehicle['id'], $importedAmsIds, true)
                && ! Vehicle::amsStatusRequiresRetirement($amsVehicle['lifecycle_status']),
        ])->values()->all();

        return Inertia::render('vehicles/import', [
            'amsVehicles' => $amsVehicles,
            'sourceError' => $sourceError,
            'operationalStatuses' => Vehicle::operationalStatuses(),
            'fuelTypes' => Vehicle::fuelTypes(),
        ]);
    }

    public function store(StoreVehicleRequest $request, ImportVehicle $importVehicle): RedirectResponse
    {
        try {
            $vehicle = $importVehicle->handle($request->validated());
        } catch (MkwdImsException $exception) {
            report($exception);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'The vehicle could not be imported because AMS is unavailable.',
            ]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehicle imported from AMS.']);

        return to_route('vehicles.show', $vehicle);
    }

    public function show(Vehicle $vehicle): Response
    {
        Gate::authorize('vehicles.view');

        return Inertia::render('vehicles/show', [
            'currentAssignment' => VehicleDriverAssignment::query()->where('vehicle_id', $vehicle->id)->current()
                ->with(['vehicle:id,name,plate_number', 'driver:id,employee_name'])->first(),
            'vehicle' => $vehicle,
            'recentOdometerEntries' => OdometerEntry::query()->where('vehicle_id', $vehicle->id)
                ->with(['trip:id,reference_number', 'recorder:id,name'])
                ->orderByDesc('recorded_at')->orderByDesc('id')->limit(5)->get(),
            'operationalStatuses' => Vehicle::operationalStatuses(),
            'fuelTypes' => Vehicle::fuelTypes(),
            'canUpdate' => Gate::allows('vehicles.update'),
            'canSync' => Gate::allows('vehicles.sync'),
        ]);
    }

    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
        UpdateVehicleOperationalInformation $updateVehicleOperationalInformation,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $updateVehicleOperationalInformation->handle($vehicle, $request->validated(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Operational information updated.']);

        return back();
    }
}
