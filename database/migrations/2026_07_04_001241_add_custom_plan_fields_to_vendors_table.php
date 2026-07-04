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
            $table->json('plan_custom_limits')->nullable()->comment('Custom overrides for plan limits');
            $table->decimal('custom_plan_charge', 10, 2)->nullable()->comment('Custom manual charge for the plan');
            $table->string('custom_plan_frequency')->nullable()->comment('Custom manual charge frequency for the plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('plan_custom_limits');
            $table->dropColumn('custom_plan_charge');
            $table->dropColumn('custom_plan_frequency');
        });
    }
};
