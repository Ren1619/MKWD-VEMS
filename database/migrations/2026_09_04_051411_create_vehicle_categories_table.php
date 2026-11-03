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
        Schema::create('vehicle_categories', function (Blueprint $table) {
            $table->id();
            $table->string('source_system', 50);
            $table->string('source_code');
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['source_system', 'source_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_categories');
    }
};
