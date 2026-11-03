<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewMaintenanceRequest
{
    public function handle(MaintenanceRequest $maintenanceRequest, User $reviewer, string $decision, ?string $notes): void
    {
        DB::transaction(function () use ($maintenanceRequest, $reviewer, $decision, $notes): void {
            $locked = MaintenanceRequest::query()->lockForUpdate()->findOrFail($maintenanceRequest->id);
            if ($locked->status !== MaintenanceRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['decision' => 'Only pending requests may be reviewed.']);
            }
            $locked->update(['status' => $decision, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $notes]);
        });
    }
}
