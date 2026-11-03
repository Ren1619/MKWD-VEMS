<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 30)->nullable()->unique();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('destination');
            $table->text('purpose');
            $table->text('passengers')->nullable();
            $table->dateTime('planned_departure')->index();
            $table->dateTime('planned_return');
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('actual_departure')->nullable();
            $table->unsignedBigInteger('start_odometer')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('actual_return')->nullable();
            $table->unsignedBigInteger('end_odometer')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id', 'status', 'planned_departure', 'planned_return'], 'trips_vehicle_schedule');
            $table->index(['driver_id', 'status', 'planned_departure', 'planned_return'], 'trips_driver_schedule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
