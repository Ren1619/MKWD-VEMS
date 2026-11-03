<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_driver_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('assignment_type', 20);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id', 'start_date', 'end_date'], 'assignments_vehicle_dates');
            $table->index(['driver_id', 'start_date', 'end_date'], 'assignments_driver_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_driver_assignments');
    }
};
