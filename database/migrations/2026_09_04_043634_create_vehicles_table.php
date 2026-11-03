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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('ams_asset_id', 100)->unique();

            $table->uuid('property_tag_uuid')->nullable()->index();
            $table->string('property_number')->nullable()->index();
            $table->string('serial_number')->nullable();
            $table->string('plate_number')->nullable()->index();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('vehicle_year')->nullable();
            $table->text('description')->nullable();
            $table->string('category_code')->nullable();
            $table->string('category_name')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->string('location')->nullable();
            $table->string('office_name')->nullable()->index();
            $table->string('nature_of_occupancy')->nullable();
            $table->string('fund_cluster')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 15, 2)->nullable();
            $table->string('ams_asset_status')->nullable()->index();
            $table->string('ams_condition_status')->nullable();
            $table->string('ams_custody_status')->nullable();
            $table->string('current_custodian')->nullable();
            $table->string('current_borrower')->nullable();
            $table->timestamp('ams_updated_at')->nullable();

            $table->unsignedBigInteger('current_mileage')->default(0);
            $table->string('fuel_type')->nullable();
            $table->string('operational_status')->default('available')->index();
            $table->unsignedInteger('maintenance_interval_km')->nullable();
            $table->text('operational_notes')->nullable();

            $table->boolean('is_present_in_ams')->default(true)->index();
            $table->text('last_sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamp('imported_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
