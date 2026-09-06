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
        Schema::create('delivery_drivers', function (Blueprint $table) {
            $table->id('_id');
            $table->char('_uid', 36)->unique();
            $table->integer('vendors__id')->unsigned()->index();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('zone')->nullable();
            $table->string('phone')->comment('WhatsApp number the driver is reachable/notified on');
            $table->text('address')->nullable();
            $table->string('vehicle_type')->nullable()->comment('Engin: moto, voiture, vélo, à pied, etc.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_drivers');
    }
};
