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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['ams_asset_id']);
            $table->string('source_system', 50)->default('mkwd_ims')->after('id');
            $table->foreignId('vehicle_category_id')->nullable()->after('category_name')->constrained()->nullOnDelete();
            $table->string('subcategory_code')->nullable()->after('vehicle_category_id');
            $table->string('subcategory_name')->nullable()->after('subcategory_code');
            $table->foreignId('vehicle_subcategory_id')->nullable()->after('subcategory_name')->constrained()->nullOnDelete();
            $table->string('current_custodian_code')->nullable()->after('ams_custody_status');
            $table->string('current_borrower_code')->nullable()->after('current_custodian');
            $table->timestamp('ams_created_at')->nullable()->after('current_borrower');

            $table->unique(['source_system', 'ams_asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['source_system', 'ams_asset_id']);
            $table->dropConstrainedForeignId('vehicle_category_id');
            $table->dropConstrainedForeignId('vehicle_subcategory_id');
            $table->dropColumn([
                'source_system',
                'subcategory_code',
                'subcategory_name',
                'current_custodian_code',
                'current_borrower_code',
                'ams_created_at',
            ]);
            $table->unique('ams_asset_id');
        });
    }
};
