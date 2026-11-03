<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            $table->string('source_type', 20)->default('hris')->index();
            $table->string('hris_employee_id', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('drivers')->whereNull('hris_employee_id')->exists()) {
            throw new RuntimeException('Cannot roll back outsourced driver support while drivers without HRIS IDs exist.');
        }

        Schema::table('drivers', function (Blueprint $table): void {
            $table->string('hris_employee_id', 100)->nullable(false)->change();
            $table->dropIndex(['source_type']);
            $table->dropColumn('source_type');
        });
    }
};
