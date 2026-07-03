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
        Schema::table('vendors', function (Blueprint $table) {
            $table->integer('plan_ai_credits')->default(0)->comment('Credits allocated by current subscription plan');
            $table->integer('extra_ai_credits')->default(0)->comment('Credits purchased separately by the vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['plan_ai_credits', 'extra_ai_credits']);
        });
    }
};
