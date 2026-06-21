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
        // Day_delay is already renamed to delay_value in the previous failed attempt
        Schema::table('addon_drip_steps', function (Blueprint $table) {
            $table->string('delay_type', 20)->default('days')->after('delay_value'); // 'minutes', 'hours', 'days'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addon_drip_steps', function (Blueprint $table) {
            $table->dropColumn('delay_type');
        });
        
        Schema::table('addon_drip_steps', function (Blueprint $table) {
            $table->renameColumn('delay_value', 'day_delay');
        });
    }
};
