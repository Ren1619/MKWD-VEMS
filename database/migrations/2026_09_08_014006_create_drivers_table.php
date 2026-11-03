<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->string('hris_employee_id', 100)->unique();
            $table->string('employee_number')->nullable()->index();
            $table->string('employee_name')->index();
            $table->string('employee_email')->nullable();
            $table->string('employee_phone', 50)->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->string('license_number', 100)->unique();
            $table->string('license_type', 50);
            $table->date('license_expiry')->index();
            $table->string('status', 20)->default('inactive')->index();
            $table->boolean('driving_authorized')->default(false);
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_source')->nullable();
            $table->boolean('is_present_in_hris')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
