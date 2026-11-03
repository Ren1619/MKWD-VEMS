<?php

namespace App\Http\Controllers;

use App\Actions\Drivers\SaveDriver;
use App\Actions\Drivers\SyncDriverEmployee;
use App\Exceptions\HrisUnavailableException;
use App\Http\Requests\Drivers\IndexDriverRequest;
use App\Http\Requests\Drivers\SaveDriverRequest;
use App\Http\Requests\Drivers\SearchEmployeesRequest;
use App\Integrations\Hris\EmployeeData;
use App\Integrations\Hris\EmployeeDirectory;
use App\Models\Driver;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function index(IndexDriverRequest $request): Response
    {
        $filters = $request->validated();
        $drivers = Driver::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Driver $driver): array => $this->profile($driver));

        return Inertia::render('drivers/index', [
            'drivers' => $drivers,
            'filters' => $filters,
            'statuses' => Driver::statuses(),
            'canManage' => Gate::allows('drivers.manage'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('drivers.manage');

        return Inertia::render('drivers/create', [
            'licenseTypes' => Driver::licenseTypes(),
            'statuses' => Driver::statuses(),
        ]);
    }

    public function employees(SearchEmployeesRequest $request, EmployeeDirectory $directory): JsonResponse
    {
        try {
            $employees = $directory->search($request->string('search')->toString());
        } catch (HrisUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $registeredIds = Driver::query()
            ->whereIn('hris_employee_id', array_map(fn (EmployeeData $employee): string => $employee->id, $employees))
            ->pluck('hris_employee_id')
            ->all();

        return response()->json([
            'employees' => array_map(fn (EmployeeData $employee): array => [
                'id' => $employee->id,
                ...$employee->profileAttributes(),
                'is_registered' => in_array($employee->id, $registeredIds, true),
            ], array_slice($employees, 0, 30)),
        ]);
    }

    public function store(SaveDriverRequest $request, SaveDriver $saveDriver): RedirectResponse
    {
        try {
            $driver = $saveDriver->handle($request->validated());
        } catch (HrisUnavailableException $exception) {
            return back()->withErrors(['hris_employee_id' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Driver registered.']);

        return to_route('drivers.show', $driver);
    }

    public function show(Driver $driver): Response
    {
        Gate::authorize('drivers.view');

        return Inertia::render('drivers/show', [
            'currentAssignment' => VehicleDriverAssignment::query()->where('driver_id', $driver->id)->current()
                ->with(['vehicle:id,name,plate_number', 'driver:id,employee_name'])->first(),
            'driver' => $this->profile($driver),
            'licenseTypes' => Driver::licenseTypes(),
            'statuses' => Driver::statuses(),
            'canManage' => Gate::allows('drivers.manage'),
        ]);
    }

    public function update(SaveDriverRequest $request, Driver $driver, SaveDriver $saveDriver): RedirectResponse
    {
        $saveDriver->handle($request->validated(), $driver);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Driver details updated.']);

        return to_route('drivers.show', $driver);
    }

    public function sync(Driver $driver, SyncDriverEmployee $syncDriverEmployee): RedirectResponse
    {
        Gate::authorize('drivers.manage');

        try {
            $found = $syncDriverEmployee->handle($driver);
        } catch (HrisUnavailableException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back()->withErrors(['hris_sync' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => $found ? 'success' : 'warning',
            'message' => $found
                ? 'Employee information refreshed from HRIS.'
                : 'Employee not found in HRIS. The driver was retained for review.',
        ]);

        return to_route('drivers.show', $driver);
    }

    /** @return array<string, mixed> */
    private function profile(Driver $driver): array
    {
        return [
            ...$driver->toArray(),
            'eligible_to_drive' => $driver->isEligibleToDrive(),
            'eligibility_reasons' => $driver->eligibilityReasons(),
        ];
    }
}
