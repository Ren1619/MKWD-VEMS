<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('maintenance.view');
        $schedules = MaintenanceSchedule::query()->with('vehicle:id,name,plate_number,current_mileage')->where('is_active', true)->orderBy('next_due_date')->orderBy('next_due_mileage')->get();
        $requests = MaintenanceRequest::query()->with(['vehicle:id,name,plate_number', 'requester:id,name', 'workOrder:id,maintenance_request_id,reference_number'])->latest('id')->limit(20)->get();
        $workOrders = MaintenanceWorkOrder::query()->with(['vehicle:id,name,plate_number', 'schedule:id,service_type'])->latest('id')->limit(30)->get();

        return Inertia::render('maintenance/index', [
            'schedules' => $schedules,
            'requests' => $requests,
            'workOrders' => $workOrders,
            'summary' => [
                'due' => $schedules->where('due_status', 'due')->count(),
                'overdue' => $schedules->where('due_status', 'overdue')->count(),
                'pendingRequests' => $requests->where('status', MaintenanceRequest::STATUS_PENDING)->count(),
                'activeWorkOrders' => $workOrders->whereIn('status', [MaintenanceWorkOrder::STATUS_OPEN, MaintenanceWorkOrder::STATUS_IN_PROGRESS])->count(),
            ],
            'canManage' => Gate::allows('maintenance.manage'),
            'vehicles' => Vehicle::query()->where('is_present_in_ams', true)->orderBy('name')->get(['id', 'name', 'plate_number', 'fuel_type', 'current_mileage', 'operational_status']),
            'approvedRequests' => MaintenanceRequest::query()->where('status', MaintenanceRequest::STATUS_APPROVED)->with('vehicle:id,name')->latest('id')->get(),
            'now' => now()->format('Y-m-d\TH:i'),
        ]);
    }
}
