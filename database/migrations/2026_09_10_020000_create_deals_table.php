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
        if (Schema::hasTable('deals')) {
            return;
        }

        Schema::create('deals', function (Blueprint $table) {
            $table->id('_id');
            $table->char('_uid', 36)->unique();
            $table->unsignedInteger('vendors__id');
            $table->unsignedInteger('contacts__id');
            $table->unsignedBigInteger('pipeline_stages__id');
            $table->unsignedInteger('assigned_users__id')->nullable();
            $table->string('title');
            $table->decimal('value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0); // order within its stage column
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['vendors__id', 'pipeline_stages__id']);
            $table->index(['vendors__id', 'contacts__id']);

            $table->foreign('vendors__id')
                  ->references('_id')
                  ->on('vendors')
                  ->onDelete('cascade');

            $table->foreign('contacts__id')
                  ->references('_id')
                  ->on('contacts')
                  ->onDelete('cascade');

            $table->foreign('pipeline_stages__id')
                  ->references('_id')
                  ->on('pipeline_stages')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
