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
        Schema::create('canned_replies', function (Blueprint $table) {
            $table->id();
            $table->string('_uid', 45)->unique();
            $table->unsignedInteger('vendors__id');
            $table->string('shortcut', 50);
            $table->text('message');
            $table->timestamps();

            // Foreign key to vendors table
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
        Schema::dropIfExists('canned_replies');
    }
};
