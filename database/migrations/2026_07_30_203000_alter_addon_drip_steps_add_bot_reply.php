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
        if (Schema::hasTable('addon_drip_steps') && !Schema::hasColumn('addon_drip_steps', 'bot_replies__id')) {
            Schema::table('addon_drip_steps', function (Blueprint $table) {
                $table->integer('bot_replies__id')->unsigned()->nullable()->after('whatsapp_templates__id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('addon_drip_steps') && Schema::hasColumn('addon_drip_steps', 'bot_replies__id')) {
            Schema::table('addon_drip_steps', function (Blueprint $table) {
                $table->dropColumn('bot_replies__id');
            });
        }
    }
};
