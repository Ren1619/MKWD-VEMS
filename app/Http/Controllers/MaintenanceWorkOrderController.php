<?php

namespace App\Http\Controllers;

use App\Actions\Maintenance\CancelMaintenanceWorkOrder;
use App\Actions\Maintenance\CompleteMaintenanceWorkOrder;
use App\Actions\Maintenance\CreateMaintenanceWorkOrder;
use App\Actions\Maintenance\StartMaintenanceWorkOrder;
use App\Http\Requests\Maintenance\CancelMaintenanceWorkOrderRequest;
use App\Http\Requests\Maintenance\CompleteMaintenanceWorkOrderRequest;
use App\Http\Requests\Maintenance\StartMaintenanceWorkOrderRequest;
use App\Http\Requests\Maintenance\StoreMaintenanceWorkOrderRequest;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceWorkOrderController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('maintenance.manage');

        return Inertia::render('maintenance/work-orders/create', [
            'vehicles' => Vehicle::query()->where('is_present_in_ams', true)->orderBy('name')->get(['id', 'name', 'plate_number']),
            'requests' => MaintenanceRequest::query()->where('status', MaintenanceRequest::STATUS_APPROVED)->with('vehicle:id,name')->latest('id')->get(),
            'schedules' => MaintenanceSchedule::query()->where('is_active', true)->with('vehicle:id,name')->orderBy('service_type')->get(),
        ]);
    }

    public function store(StoreMaintenanceWorkOrderRequest $request, CreateMaintenanceWorkOrder $create): RedirectResponse
    {
        $workOrder = $create->handle($this->user($request), array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== ''));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance work order created.']);

        return to_route('maintenance.work-orders.show', $workOrder);
    }

    public function show(MaintenanceWorkOrder $workOrder): Response
    {
        Gate::authorize('maintenance.view');
        $workOrder->load(['vehicle:id,name,plate_number,current_mileage,operational_status', 'maintenanceRequest', 'schedule', 'opener:id,name', 'completer:id,name', 'canceller:id,name']);

        return Inertia::render('maintenance/work-orders/show', ['workOrder' => $workOrder, 'canManage' => Gate::allows('maintenance.manage'), 'now' => now()->format('Y-m-d\TH:i')]);
    }

    public function start(StartMaintenanceWorkOrderRequest $request, MaintenanceWorkOrder $workOrder, StartMaintenanceWorkOrder $start): RedirectResponse
    {
        $start->handle($workOrder);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Work started; vehicle marked under maintenance.']);

        return back();
    }

    public function complete(CompleteMaintenanceWorkOrderRequest $request, MaintenanceWorkOrder $workOrder, CompleteMaintenanceWorkOrder $complete): RedirectResponse
    {
        $complete->handle($workOrder, $this->user($request), $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Work order completed and recorded in maintenance history.']);

        return back();
    }

    public function cancel(CancelMaintenanceWorkOrderRequest $request, MaintenanceWorkOrder $workOrder, CancelMaintenanceWorkOrder $cancel): RedirectResponse
    {
        $cancel->handle($workOrder, $this->user($request), $request->string('cancellation_reason')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Work order cancelled; history was preserved.']);

        return back();
    }

    private function user(StoreMaintenanceWorkOrderRequest|CompleteMaintenanceWorkOrderRequest|CancelMaintenanceWorkOrderRequest $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
