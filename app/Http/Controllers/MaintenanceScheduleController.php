<?php

namespace App\Http\Controllers;

use App\Actions\Maintenance\CreateMaintenanceSchedule;
use App\Http\Requests\Maintenance\StoreMaintenanceScheduleRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceScheduleController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('maintenance.manage');

        return Inertia::render('maintenance/schedules/create', ['vehicles' => Vehicle::query()->where('is_present_in_ams', true)->orderBy('name')->get(['id', 'name', 'plate_number', 'current_mileage'])]);
    }

    public function store(StoreMaintenanceScheduleRequest $request, CreateMaintenanceSchedule $create): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $create->handle($user, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Preventive maintenance schedule created.']);

        return to_route('maintenance.index');
    }
}
