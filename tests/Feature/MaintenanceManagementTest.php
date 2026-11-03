<?php

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Inertia::disableSsr();
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(10, 0));
});

test('maintenance dashboard calculates date and mileage due states', function () {
    $vehicle = Vehicle::factory()->create(['current_mileage' => 15000]);
    MaintenanceSchedule::factory()->create(['vehicle_id' => $vehicle->id, 'next_due_date' => '2026-09-07', 'next_due_mileage' => 20000]);
    MaintenanceSchedule::factory()->create(['vehicle_id' => $vehicle->id, 'next_due_date' => '2027-01-01', 'next_due_mileage' => 15000]);

    $this->actingAs(User::factory()->create())->get(route('maintenance.index'))
        ->assertInertia(fn (Assert $page) => $page->component('maintenance/index')
            ->where('summary.overdue', 1)->where('summary.due', 1)->has('schedules', 2));
});

test('authenticated users can submit maintenance requests', function () {
    $vehicle = Vehicle::factory()->create();
    $this->actingAs($user = User::factory()->create())->post(route('maintenance.requests.store'), [
        'vehicle_id' => $vehicle->id, 'priority' => 'high', 'subject' => 'Brake vibration', 'description' => 'Vibration occurs while braking.',
    ])->assertSessionHasNoErrors()->assertRedirect(route('maintenance.index'));

    $request = MaintenanceRequest::query()->firstOrFail();
    expect($request)->requested_by->toBe($user->id)->status->toBe(MaintenanceRequest::STATUS_PENDING)
        ->reference_number->toBe('MR-2026-000001');
});

test('only managers can create preventive schedules', function () {
    $vehicle = Vehicle::factory()->create();
    $data = ['vehicle_id' => $vehicle->id, 'service_type' => 'Oil change', 'interval_days' => 180, 'interval_km' => 5000, 'last_completed_date' => '2026-09-01', 'last_completed_mileage' => 10000];
    $this->actingAs(User::factory()->create())->post(route('maintenance.schedules.store'), $data)->assertForbidden();
    $this->actingAs(User::factory()->vehicleManager()->create())->post(route('maintenance.schedules.store'), $data)->assertSessionHasNoErrors();

    expect(MaintenanceSchedule::query()->firstOrFail())->next_due_date->toDateString()->toBe('2027-02-28')->next_due_mileage->toBe(15000);
});

test('a schedule requires a date or mileage interval', function () {
    $this->actingAs(User::factory()->vehicleManager()->create())->post(route('maintenance.schedules.store'), [
        'vehicle_id' => Vehicle::factory()->create()->id, 'service_type' => 'Inspection',
    ])->assertSessionHasErrors('interval_days');
});

test('maintenance creation formats are validated with server-side regex rules', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(User::factory()->vehicleManager()->create())
        ->post(route('maintenance.schedules.store'), [
            'vehicle_id' => $vehicle->id,
            'service_type' => "Oil\x07change",
            'interval_days' => '180.5',
        ])->assertSessionHasErrors(['service_type', 'interval_days']);

    $this->post(route('maintenance.work-orders.store'), [
        'vehicle_id' => $vehicle->id,
        'title' => 'Inspect brakes',
        'instructions' => "Inspect\x00brakes",
        'scheduled_start' => '09/08/2026',
    ])->assertSessionHasErrors(['instructions', 'scheduled_start']);
});

test('managers review a request and convert it into a work order', function () {
    $manager = User::factory()->vehicleManager()->create();
    $request = MaintenanceRequest::factory()->create();

    $this->actingAs($manager)->patch(route('maintenance.requests.review', $request), ['decision' => 'approved'])->assertSessionHasNoErrors();
    expect($request->refresh())->status->toBe(MaintenanceRequest::STATUS_APPROVED)->reviewed_by->toBe($manager->id);

    $this->post(route('maintenance.work-orders.store'), [
        'vehicle_id' => $request->vehicle_id, 'maintenance_request_id' => $request->id, 'title' => 'Inspect brake system', 'instructions' => 'Inspect, diagnose, and repair.',
    ])->assertSessionHasNoErrors();
    expect($request->refresh()->status)->toBe(MaintenanceRequest::STATUS_CONVERTED);
    expect(MaintenanceWorkOrder::query()->firstOrFail()->reference_number)->toBe('WO-2026-000001');
});

test('work order lifecycle preserves completion and advances its preventive schedule', function () {
    $manager = User::factory()->vehicleManager()->create();
    $vehicle = Vehicle::factory()->create(['current_mileage' => 12000]);
    $schedule = MaintenanceSchedule::factory()->create(['vehicle_id' => $vehicle->id, 'interval_days' => 90, 'interval_km' => 5000]);
    $workOrder = MaintenanceWorkOrder::factory()->create(['vehicle_id' => $vehicle->id, 'maintenance_schedule_id' => $schedule->id]);

    $this->actingAs($manager)->patch(route('maintenance.work-orders.start', $workOrder))->assertSessionHasNoErrors();
    expect($workOrder->refresh()->status)->toBe(MaintenanceWorkOrder::STATUS_IN_PROGRESS);
    expect($vehicle->refresh()->operational_status)->toBe(Vehicle::STATUS_UNDER_MAINTENANCE);

    $this->patch(route('maintenance.work-orders.complete', $workOrder), [
        'completed_at' => '2026-09-08T09:30', 'completion_mileage' => 12500, 'work_performed' => 'Oil and filters replaced; safety inspection completed.', 'cost' => 3500,
    ])->assertSessionHasNoErrors();

    expect($workOrder->refresh())->status->toBe(MaintenanceWorkOrder::STATUS_COMPLETED)->completion_mileage->toBe(12500)->completed_by->toBe($manager->id);
    expect($vehicle->refresh()->operational_status)->toBe(Vehicle::STATUS_AVAILABLE);
    expect($schedule->refresh())->last_completed_date->toDateString()->toBe('2026-09-08')->next_due_date->toDateString()->toBe('2026-12-07')->next_due_mileage->toBe(17500);
});

test('completed work orders cannot be changed again', function () {
    $workOrder = MaintenanceWorkOrder::factory()->create(['status' => MaintenanceWorkOrder::STATUS_COMPLETED, 'completed_at' => now(), 'completion_mileage' => 100, 'work_performed' => 'Done']);
    $this->actingAs(User::factory()->vehicleManager()->create())->patch(route('maintenance.work-orders.complete', $workOrder), [
        'completed_at' => '2026-09-08T09:00', 'completion_mileage' => 100, 'work_performed' => 'Changed',
    ])->assertSessionHasErrors('completed_at');
});

test('guests cannot access maintenance management', function () {
    $this->get(route('maintenance.index'))->assertRedirect(route('login'));
    $this->get(route('maintenance.requests.create'))->assertRedirect(route('login'));
});
