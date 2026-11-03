<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\ShowDashboardRequest;
use App\Models\FuelLog;
use App\Models\MaintenanceSchedule;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ShowDashboardRequest $request): Response
    {
        $validated = $request->validated();
        $dateFrom = Date::parse($validated['date_from'] ?? today()->startOfMonth())->startOfDay();
        $dateTo = Date::parse($validated['date_to'] ?? today())->endOfDay();

        $fleet = Vehicle::query()->where('is_present_in_ams', true);
        $operableVehicles = (clone $fleet)->where('operational_status', '!=', Vehicle::STATUS_RETIRED)->count();
        $availableVehicles = (clone $fleet)->where('operational_status', Vehicle::STATUS_AVAILABLE)->count();
        $operationalStatusCounts = (clone $fleet)->select('operational_status')->selectRaw('COUNT(*) as total')->groupBy('operational_status')->pluck('total', 'operational_status');

        $tripQuery = fn (): Builder => Trip::query()->whereBetween('planned_departure', [$dateFrom, $dateTo]);
        $tripStatusCounts = collect(Trip::statuses())->mapWithKeys(fn (string $status): array => [$status => (clone $tripQuery())->where('status', $status)->count()]);

        $fuelQuery = fn (): Builder => FuelLog::query()->whereBetween('refueled_at', [$dateFrom, $dateTo]);
        $consumptionFuel = $fuelQuery()->whereNotNull('distance_since_previous');
        $consumptionLiters = (float) (clone $consumptionFuel)->sum('quantity_liters');
        $recordedDistance = (int) $consumptionFuel->sum('distance_since_previous');

        $maintenanceSchedules = MaintenanceSchedule::query()->where('is_active', true)->with('vehicle:id,current_mileage')->get();

        return Inertia::render('dashboard', [
            'filters' => ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()],
            'availability' => [
                'available' => $availableVehicles,
                'operable' => $operableVehicles,
                'rate' => $operableVehicles > 0 ? round($availableVehicles / $operableVehicles * 100, 1) : 0,
            ],
            'activeAssignments' => VehicleDriverAssignment::query()->current()->count(),
            'trips' => [
                'total' => $tripStatusCounts->sum(),
                'byStatus' => $tripStatusCounts,
                'recent' => $tripQuery()->select(['id', 'reference_number', 'vehicle_id', 'driver_id', 'status', 'destination', 'planned_departure'])->with(['vehicle:id,name,plate_number', 'driver:id,employee_name'])->latest('planned_departure')->limit(6)->get(),
            ],
            'maintenance' => [
                'due' => $maintenanceSchedules->where('due_status', 'due')->count(),
                'overdue' => $maintenanceSchedules->where('due_status', 'overdue')->count(),
            ],
            'fuel' => [
                'quantity' => (float) $fuelQuery()->sum('quantity_liters'),
                'cost' => (float) $fuelQuery()->sum('total_cost'),
                'distance' => $recordedDistance,
                'consumption' => $consumptionLiters > 0 ? round($recordedDistance / $consumptionLiters, 2) : null,
            ],
            'operationalStatuses' => collect(Vehicle::operationalStatuses())->map(fn (string $status): array => ['status' => $status, 'count' => (int) ($operationalStatusCounts[$status] ?? 0)])->values(),
        ]);
    }
}
