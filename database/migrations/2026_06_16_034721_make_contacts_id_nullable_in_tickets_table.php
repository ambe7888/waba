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
        Schema::table('tickets', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_tickets_contacts1');
            } catch (\Exception $e) {
                // Ignore if it doesn't exist
            }
            
            $table->integer('contacts__id')->unsigned()->nullable()->change();
            
            $table->foreign('contacts__id', 'fk_tickets_contacts1')
                  ->references('_id')->on('contacts')
                  ->onDelete('cascade')->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_tickets_contacts1');
            } catch (\Exception $e) {
                // Ignore
            }
            
            $table->integer('contacts__id')->unsigned()->nullable(false)->change();
            
            $table->foreign('contacts__id', 'fk_tickets_contacts1')
                  ->references('_id')->on('contacts')
                  ->onDelete('cascade')->onUpdate('no action');
        });
    }
};
