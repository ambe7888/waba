<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Global/broadcast notifications (vendors__id = null) are relied on
        // by both the admin sender and the API reader, but the pre-existing
        // vendor_notifications table (from the base install schema) had this
        // column as NOT NULL — making that impossible until now.
        DB::statement('ALTER TABLE vendor_notifications MODIFY vendors__id INT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE vendor_notifications MODIFY vendors__id INT UNSIGNED NOT NULL');
    }
};
