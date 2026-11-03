<?php

namespace App\Http\Controllers;

use App\Actions\Assignments\AssignDriver;
use App\Actions\Assignments\EndAssignment;
use App\Http\Requests\Assignments\EndAssignmentRequest;
use App\Http\Requests\Assignments\IndexAssignmentRequest;
use App\Http\Requests\Assignments\StoreAssignmentRequest;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VehicleDriverAssignmentController extends Controller
{
    public function index(IndexAssignmentRequest $request): Response
    {
        $filters = $request->validated();
        $filters['vehicle_id'] = $request->filled('vehicle_id') ? $request->integer('vehicle_id') : null;
        $filters['driver_id'] = $request->filled('driver_id') ? $request->integer('driver_id') : null;
        $filters['assign'] = $request->boolean('assign');
        $query = VehicleDriverAssignment::query()->with(['vehicle:id,name,plate_number', 'driver:id,employee_name'])
            ->when($filters['vehicle_id'] ?? null, fn (Builder $query, int $id) => $query->where('vehicle_id', $id))
            ->when($filters['driver_id'] ?? null, fn (Builder $query, int $id) => $query->where('driver_id', $id));

        match ($filters['status'] ?? null) {
            'current' => $query->current(),
            'scheduled' => $query->where('start_date', '>', today()->toDateString()),
            'ended' => $query->where('end_date', '<', today()->toDateString()),
            default => null,
        };

        return Inertia::render('assignments/index', [
            'assignments' => $query->orderByDesc('start_date')->orderByDesc('id')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'canManage' => Gate::allows('assignments.manage'),
            'today' => today()->toDateString(),
            'vehicles' => Gate::allows('assignments.manage') ? Vehicle::query()->orderBy('name')->get(['id', 'name', 'plate_number']) : [],
            'drivers' => Gate::allows('assignments.manage') ? Driver::query()->orderBy('employee_name')->get(['id', 'employee_name', 'employee_number', 'license_number']) : [],
        ]);
    }

    public function create(IndexAssignmentRequest $request): Response
    {
        Gate::authorize('assignments.manage');

        return Inertia::render('assignments/create', [
            'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'plate_number']),
            'drivers' => Driver::query()->orderBy('employee_name')
                ->get(['id', 'employee_name', 'employee_number', 'license_number']),
            'defaults' => $request->validated(),
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreAssignmentRequest $request, AssignDriver $assignDriver): RedirectResponse
    {
        $assignment = $assignDriver->handle([
            'vehicle_id' => $request->integer('vehicle_id'),
            'driver_id' => $request->integer('driver_id'),
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->filled('end_date') ? $request->string('end_date')->toString() : null,
            'assignment_type' => $request->string('assignment_type')->toString(),
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Driver assigned to vehicle.']);

        return to_route('assignments.index', ['vehicle_id' => $assignment->vehicle_id]);
    }

    public function end(EndAssignmentRequest $request, VehicleDriverAssignment $assignment, EndAssignment $endAssignment): RedirectResponse
    {
        $endAssignment->handle($assignment, $request->string('end_date')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Assignment ended. Its history has been preserved.']);

        return back();
    }
}
