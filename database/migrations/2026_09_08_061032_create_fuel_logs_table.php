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
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('odometer_entry_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->decimal('quantity_liters', 10, 3);
            $table->decimal('total_cost', 12, 2);
            $table->unsignedBigInteger('odometer');
            $table->dateTime('refueled_at')->index();
            $table->string('station')->nullable();
            $table->string('receipt_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('distance_since_previous')->nullable();
            $table->decimal('consumption_km_per_liter', 10, 3)->nullable();
            $table->decimal('cost_per_km', 10, 3)->nullable();
            $table->decimal('price_per_liter', 10, 3);
            $table->timestamps();
            $table->index(['vehicle_id', 'refueled_at', 'id'], 'fuel_logs_vehicle_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_logs');
    }
};
