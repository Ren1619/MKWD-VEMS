<?php

namespace App\Providers;

use App\Integrations\Hris\EmployeeDirectory;
use App\Integrations\Hris\UnconfiguredEmployeeDirectory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmployeeDirectory::class, UnconfiguredEmployeeDirectory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureVehicleAuthorization();
        Gate::define('drivers.view', fn (User $user): bool => true);
        Gate::define('assignments.view', fn (User $user): bool => true);
        Gate::define('assignments.manage', fn (User $user): bool => $user->can_manage_vehicles);
        Gate::define('trips.view', fn (User $user): bool => true);
        Gate::define('trips.request', fn (User $user): bool => true);
        Gate::define('trips.manage', fn (User $user): bool => $user->can_manage_vehicles);
        Gate::define('odometer.view', fn (User $user): bool => true);
        Gate::define('odometer.correct', fn (User $user): bool => $user->can_manage_vehicles);
        Gate::define('drivers.manage', fn (User $user): bool => $user->can_manage_vehicles);
        Gate::define('maintenance.view', fn (User $user): bool => true);
        Gate::define('maintenance.request', fn (User $user): bool => true);
        Gate::define('maintenance.manage', fn (User $user): bool => $user->can_manage_vehicles);
        Gate::define('fuel.view', fn (User $user): bool => true);
        Gate::define('fuel.manage', fn (User $user): bool => $user->can_manage_vehicles);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureVehicleAuthorization(): void
    {
        Gate::define('vehicles.view', fn (User $user): bool => true);
        Gate::define('vehicles.import', fn (User $user): bool => $user->can_manage_vehicles);
        Gate::define('vehicles.update', fn (User $user): bool => $user->can_manage_vehicles);
        Gate::define('vehicles.sync', fn (User $user): bool => $user->can_manage_vehicles);
    }
}
