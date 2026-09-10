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
        if (Schema::hasTable('calls')) {
            return;
        }

        Schema::create('calls', function (Blueprint $table) {
            $table->id('_id');
            $table->char('_uid', 36)->unique();
            $table->unsignedInteger('vendors__id');
            $table->unsignedInteger('contacts__id')->nullable();
            $table->string('type', 20); // 'whatsapp' or '3cx'
            $table->string('direction', 20)->nullable(); // 'inbound' or 'outbound'
            $table->string('status', 30)->nullable(); // completed, failed, missed, initiated...
            $table->unsignedInteger('duration')->nullable(); // seconds
            $table->string('call_id')->nullable(); // Meta's wacid, null for 3cx
            $table->unsignedInteger('initiated_by_users__id')->nullable();
            $table->timestamps();

            $table->index(['vendors__id', 'contacts__id']);

            $table->foreign('vendors__id')
                  ->references('_id')
                  ->on('vendors')
                  ->onDelete('cascade');

            $table->foreign('contacts__id')
                  ->references('_id')
                  ->on('contacts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
