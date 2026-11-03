<?php

namespace App\Http\Controllers;

use App\Actions\FuelLogs\CreateFuelLog;
use App\Http\Requests\FuelLogs\IndexFuelLogRequest;
use App\Http\Requests\FuelLogs\StoreFuelLogRequest;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FuelLogController extends Controller
{
    public function index(IndexFuelLogRequest $request): Response
    {
        $filters = $request->validated();
        $filtered = fn () => FuelLog::query()
            ->when($filters['vehicle_id'] ?? null, fn (Builder $query, int $vehicleId) => $query->where('vehicle_id', $vehicleId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('refueled_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('refueled_at', '<=', $date));

        $logs = $filtered()->select(['id', 'vehicle_id', 'recorded_by', 'quantity_liters', 'total_cost', 'odometer', 'refueled_at', 'station', 'receipt_reference', 'notes', 'distance_since_previous', 'consumption_km_per_liter', 'cost_per_km', 'price_per_liter'])
            ->with(['vehicle:id,name,plate_number,fuel_type', 'recorder:id,name'])
            ->orderByDesc('refueled_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $consumptionLogs = $filtered()->whereNotNull('distance_since_previous');
        $litersForConsumption = (float) (clone $consumptionLogs)->sum('quantity_liters');
        $distance = (int) $consumptionLogs->sum('distance_since_previous');

        return Inertia::render('fuel-logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'vehicles' => Vehicle::query()->where('is_present_in_ams', true)->orderBy('name')->get(['id', 'name', 'plate_number', 'fuel_type', 'current_mileage']),
            'summary' => [
                'quantity' => (float) $filtered()->sum('quantity_liters'),
                'cost' => (float) $filtered()->sum('total_cost'),
                'distance' => $distance,
                'consumption' => $litersForConsumption > 0 ? $distance / $litersForConsumption : null,
            ],
            'canManage' => Gate::allows('fuel.manage'),
            'now' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('fuel.manage');

        return Inertia::render('fuel-logs/create', [
            'vehicles' => Vehicle::query()->where('is_present_in_ams', true)->orderBy('name')->get(['id', 'name', 'plate_number', 'fuel_type', 'current_mileage']),
            'now' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreFuelLogRequest $request, CreateFuelLog $createFuelLog): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $createFuelLog->handle($user, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fuel log recorded and consumption figures updated.']);

        return to_route('fuel-logs.index', ['vehicle_id' => $request->integer('vehicle_id')]);
    }
}
