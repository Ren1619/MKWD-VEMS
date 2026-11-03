<?php

namespace App\Http\Controllers;

use App\Actions\Maintenance\ReviewMaintenanceRequest;
use App\Http\Requests\Maintenance\ReviewMaintenanceRequestRequest;
use App\Http\Requests\Maintenance\StoreMaintenanceRequestRequest;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('maintenance.request');

        return Inertia::render('maintenance/requests/create', ['vehicles' => Vehicle::query()->where('is_present_in_ams', true)->orderBy('name')->get(['id', 'name', 'plate_number', 'operational_status'])]);
    }

    public function store(StoreMaintenanceRequestRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $maintenanceRequest = MaintenanceRequest::query()->create([...$request->validated(), 'requested_by' => $user->id, 'status' => MaintenanceRequest::STATUS_PENDING]);
        $maintenanceRequest->update(['reference_number' => sprintf('MR-%s-%06d', now()->format('Y'), $maintenanceRequest->id)]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance request submitted.']);

        return to_route('maintenance.index');
    }

    public function review(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest, ReviewMaintenanceRequest $review): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $review->handle($maintenanceRequest, $user, $request->string('decision')->toString(), $request->filled('review_notes') ? $request->string('review_notes')->toString() : null);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance request reviewed.']);

        return back();
    }
}
