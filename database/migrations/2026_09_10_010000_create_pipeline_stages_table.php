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
        if (Schema::hasTable('pipeline_stages')) {
            return;
        }

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id('_id');
            $table->char('_uid', 36)->unique();
            $table->unsignedInteger('vendors__id');
            $table->string('title');
            $table->unsignedInteger('position')->default(0);
            $table->string('color', 20)->default('#64748b');
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();

            $table->index(['vendors__id', 'position']);

            $table->foreign('vendors__id')
                  ->references('_id')
                  ->on('vendors')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
