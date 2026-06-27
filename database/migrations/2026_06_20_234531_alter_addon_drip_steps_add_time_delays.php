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
        // Ensure legacy column naming is upgraded before adding delay_type.
        if (Schema::hasColumn('addon_drip_steps', 'day_delay') && !Schema::hasColumn('addon_drip_steps', 'delay_value')) {
            Schema::table('addon_drip_steps', function (Blueprint $table) {
                $table->renameColumn('day_delay', 'delay_value');
            });
        }

        if (!Schema::hasColumn('addon_drip_steps', 'delay_type')) {
            Schema::table('addon_drip_steps', function (Blueprint $table) {
                $table->string('delay_type', 20)->default('days')->after('delay_value'); // 'minutes', 'hours', 'days'
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('addon_drip_steps', 'delay_type')) {
            Schema::table('addon_drip_steps', function (Blueprint $table) {
                $table->dropColumn('delay_type');
            });
        }

        if (Schema::hasColumn('addon_drip_steps', 'delay_value') && !Schema::hasColumn('addon_drip_steps', 'day_delay')) {
            Schema::table('addon_drip_steps', function (Blueprint $table) {
                $table->renameColumn('delay_value', 'day_delay');
            });
        }
    }
};
