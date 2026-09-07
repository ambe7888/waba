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
        Schema::table('delivery_drivers', function (Blueprint $table) {
            $table->timestamp('last_message_at')->nullable()->after('is_active')
                ->comment('Last inbound WhatsApp message from this driver -- used for the 24h customer service window rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_drivers', function (Blueprint $table) {
            $table->dropColumn('last_message_at');
        });
    }
};
