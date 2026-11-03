<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FuelLogController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MaintenanceWorkOrderController;
use App\Http\Controllers\OdometerHistoryController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleDriverAssignmentController;
use App\Http\Controllers\VehicleSyncController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('fuel-logs', FuelLogController::class)->only(['index', 'create', 'store']);
    Route::get('maintenance', MaintenanceController::class)->name('maintenance.index');
    Route::get('maintenance/schedules/create', [MaintenanceScheduleController::class, 'create'])->name('maintenance.schedules.create');
    Route::post('maintenance/schedules', [MaintenanceScheduleController::class, 'store'])->name('maintenance.schedules.store');
    Route::get('maintenance/requests/create', [MaintenanceRequestController::class, 'create'])->name('maintenance.requests.create');
    Route::post('maintenance/requests', [MaintenanceRequestController::class, 'store'])->name('maintenance.requests.store');
    Route::patch('maintenance/requests/{maintenanceRequest}/review', [MaintenanceRequestController::class, 'review'])->name('maintenance.requests.review');
    Route::get('maintenance/work-orders/create', [MaintenanceWorkOrderController::class, 'create'])->name('maintenance.work-orders.create');
    Route::post('maintenance/work-orders', [MaintenanceWorkOrderController::class, 'store'])->name('maintenance.work-orders.store');
    Route::get('maintenance/work-orders/{workOrder}', [MaintenanceWorkOrderController::class, 'show'])->name('maintenance.work-orders.show');
    Route::patch('maintenance/work-orders/{workOrder}/start', [MaintenanceWorkOrderController::class, 'start'])->name('maintenance.work-orders.start');
    Route::patch('maintenance/work-orders/{workOrder}/complete', [MaintenanceWorkOrderController::class, 'complete'])->name('maintenance.work-orders.complete');
    Route::patch('maintenance/work-orders/{workOrder}/cancel', [MaintenanceWorkOrderController::class, 'cancel'])->name('maintenance.work-orders.cancel');
    Route::get('odometer-history', OdometerHistoryController::class)->name('odometer.index');
    Route::patch('trips/{trip}/decision', [TripController::class, 'decide'])->name('trips.decide');
    Route::patch('trips/{trip}/dispatch', [TripController::class, 'dispatch'])->name('trips.dispatch');
    Route::patch('trips/{trip}/complete', [TripController::class, 'complete'])->name('trips.complete');
    Route::resource('trips', TripController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('assignments', VehicleDriverAssignmentController::class)->only(['index', 'create', 'store']);
    Route::patch('assignments/{assignment}/end', [VehicleDriverAssignmentController::class, 'end'])->name('assignments.end');
    Route::get('drivers/employees', [DriverController::class, 'employees'])->middleware('throttle:60,1')->name('drivers.employees');
    Route::post('drivers/{driver}/sync', [DriverController::class, 'sync'])->middleware('throttle:10,1')->name('drivers.sync');
    Route::resource('drivers', DriverController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('vehicles/sync', [VehicleSyncController::class, 'storeAll'])->name('vehicles.sync_all');
    Route::post('vehicles/{vehicle}/sync', [VehicleSyncController::class, 'store'])->name('vehicles.sync');
    Route::resource('vehicles', VehicleController::class)->only(['index', 'create', 'store', 'show', 'update']);
});

require __DIR__.'/settings.php';
