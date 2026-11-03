<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateMaintenanceWorkOrder
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): MaintenanceWorkOrder
    {
        return DB::transaction(function () use ($user, $data): MaintenanceWorkOrder {
            $request = isset($data['maintenance_request_id'])
                ? MaintenanceRequest::query()->lockForUpdate()->findOrFail((int) $data['maintenance_request_id'])
                : null;

            $workOrder = MaintenanceWorkOrder::query()->create([...$data, 'opened_by' => $user->id, 'status' => MaintenanceWorkOrder::STATUS_OPEN]);
            $workOrder->update(['reference_number' => sprintf('WO-%s-%06d', now()->format('Y'), $workOrder->id)]);
            $request?->update(['status' => MaintenanceRequest::STATUS_CONVERTED]);

            return $workOrder;
        });
    }
}
