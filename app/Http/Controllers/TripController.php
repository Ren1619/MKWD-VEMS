<?php

namespace App\Http\Controllers;

use App\Actions\Trips\CompleteTrip;
use App\Actions\Trips\CreateTrip;
use App\Actions\Trips\DecideTrip;
use App\Actions\Trips\DispatchTrip;
use App\Http\Requests\Trips\CompleteTripRequest;
use App\Http\Requests\Trips\DecideTripRequest;
use App\Http\Requests\Trips\DispatchTripRequest;
use App\Http\Requests\Trips\IndexTripRequest;
use App\Http\Requests\Trips\StoreTripRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function index(IndexTripRequest $request): Response
    {
        $filters = $request->validated();

        $trips = Trip::query()
            ->select(['id', 'reference_number', 'requested_by', 'vehicle_id', 'driver_id', 'status', 'destination', 'planned_departure', 'planned_return'])
            ->with(['requester:id,name', 'vehicle:id,name,plate_number', 'driver:id,employee_name'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('trips/index', [
            'trips' => $trips,
            'filters' => $filters,
            'statuses' => Trip::statuses(),
            'canManage' => Gate::allows('trips.manage'),
            'defaultDeparture' => now()->addHour()->format('Y-m-d\TH:i'),
            'defaultReturn' => now()->addHours(2)->format('Y-m-d\TH:i'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('trips.request');

        return Inertia::render('trips/create', [
            'defaultDeparture' => now()->addHour()->format('Y-m-d\TH:i'),
            'defaultReturn' => now()->addHours(2)->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreTripRequest $request, CreateTrip $createTrip): RedirectResponse
    {
        $trip = $createTrip->handle($this->user($request), [
            'destination' => $request->string('destination')->toString(),
            'purpose' => $request->string('purpose')->toString(),
            'passengers' => $request->filled('passengers') ? $request->string('passengers')->toString() : null,
            'planned_departure' => $request->string('planned_departure')->toString(),
            'planned_return' => $request->string('planned_return')->toString(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Trip request submitted for approval.']);

        return to_route('trips.show', $trip);
    }

    public function show(Trip $trip): Response
    {
        Gate::authorize('trips.view');

        $trip->load([
            'requester:id,name,email', 'vehicle:id,name,plate_number,current_mileage,operational_status',
            'driver:id,employee_name,employee_number,license_number,license_expiry,status,driving_authorized',
            'decisionMaker:id,name', 'dispatcher:id,name', 'returnRecorder:id,name',
        ]);

        return Inertia::render('trips/show', [
            'trip' => $trip,
            'vehicles' => $trip->status === Trip::STATUS_PENDING
                ? Vehicle::query()->where('is_present_in_ams', true)->whereNotIn('operational_status', [
                    Vehicle::STATUS_UNDER_MAINTENANCE, Vehicle::STATUS_OUT_OF_SERVICE, Vehicle::STATUS_RETIRED,
                ])->orderBy('name')->get(['id', 'name', 'plate_number', 'current_mileage', 'operational_status'])
                : [],
            'drivers' => $trip->status === Trip::STATUS_PENDING
                ? Driver::query()->where('status', Driver::STATUS_ACTIVE)->orderBy('employee_name')
                    ->get(['id', 'employee_name', 'employee_number', 'license_number', 'license_type', 'license_expiry', 'status', 'driving_authorized', 'is_present_in_hris', 'source_type'])
                    ->map(fn (Driver $driver): array => [...$driver->toArray(), 'eligible_to_drive' => $driver->isEligibleToDrive()])
                : [],
            'canManage' => Gate::allows('trips.manage'),
            'now' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function decide(DecideTripRequest $request, Trip $trip, DecideTrip $decideTrip): RedirectResponse
    {
        $decideTrip->handle($trip, $this->user($request), [
            'decision' => $request->string('decision')->toString(),
            'vehicle_id' => $request->filled('vehicle_id') ? $request->integer('vehicle_id') : null,
            'driver_id' => $request->filled('driver_id') ? $request->integer('driver_id') : null,
            'decision_notes' => $request->filled('decision_notes') ? $request->string('decision_notes')->toString() : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $request->input('decision') === Trip::STATUS_APPROVED ? 'Trip approved and resources assigned.' : 'Trip request rejected.']);

        return back();
    }

    public function dispatch(DispatchTripRequest $request, Trip $trip, DispatchTrip $dispatchTrip): RedirectResponse
    {
        $dispatchTrip->handle($trip, $this->user($request), $request->string('actual_departure')->toString(), $request->integer('start_odometer'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Trip departure recorded.']);

        return back();
    }

    public function complete(CompleteTripRequest $request, Trip $trip, CompleteTrip $completeTrip): RedirectResponse
    {
        $completeTrip->handle($trip, $this->user($request), $request->string('actual_return')->toString(), $request->integer('end_odometer'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Trip return recorded and vehicle mileage updated.']);

        return back();
    }

    private function user(IndexTripRequest|StoreTripRequest|DecideTripRequest|DispatchTripRequest|CompleteTripRequest $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
