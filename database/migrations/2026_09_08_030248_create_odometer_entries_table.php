<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odometer_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('entry_type', 30)->index();
            $table->unsignedBigInteger('previous_reading');
            $table->unsignedBigInteger('reading');
            $table->text('notes')->nullable();
            $table->dateTime('recorded_at')->index();
            $table->timestamps();
            $table->unique(['trip_id', 'entry_type']);
            $table->index(['vehicle_id', 'recorded_at', 'id'], 'odometer_vehicle_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odometer_entries');
    }
};
