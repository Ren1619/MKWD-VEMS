<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('service_type');
            $table->text('description')->nullable();
            $table->unsignedInteger('interval_days')->nullable();
            $table->unsignedInteger('interval_km')->nullable();
            $table->date('last_completed_date')->nullable();
            $table->unsignedBigInteger('last_completed_mileage')->nullable();
            $table->date('next_due_date')->nullable()->index();
            $table->unsignedBigInteger('next_due_mileage')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['vehicle_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
