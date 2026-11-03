<?php

namespace App\Http\Controllers;

use App\Http\Requests\Odometer\IndexOdometerRequest;
use App\Models\OdometerEntry;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OdometerHistoryController extends Controller
{
    public function __invoke(IndexOdometerRequest $request): Response
    {
        $filters = $request->validated();

        $entries = OdometerEntry::query()
            ->select(['id', 'vehicle_id', 'trip_id', 'recorded_by', 'entry_type', 'previous_reading', 'reading', 'notes', 'recorded_at'])
            ->with(['vehicle:id,name,plate_number', 'trip:id,reference_number', 'recorder:id,name'])
            ->when($filters['vehicle_id'] ?? null, fn (Builder $query, int $vehicleId) => $query->where('vehicle_id', $vehicleId))
            ->when($filters['entry_type'] ?? null, fn (Builder $query, string $type) => $query->where('entry_type', $type))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('odometer/index', [
            'entries' => $entries,
            'filters' => $filters,
            'types' => OdometerEntry::types(),
            'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'plate_number']),
            'canCorrect' => Gate::allows('odometer.correct'),
        ]);
    }
}
