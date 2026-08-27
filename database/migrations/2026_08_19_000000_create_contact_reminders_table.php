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
        if (Schema::hasTable('contact_reminders')) {
            if (!Schema::hasColumn('contact_reminders', '__data')) {
                Schema::table('contact_reminders', function (Blueprint $table) {
                    $table->longText('__data')->nullable()->after('status');
                });
            }
            return;
        }

        Schema::create('contact_reminders', function (Blueprint $table) {
            $table->id('_id');
            $table->char('_uid', 36)->unique();
            $table->unsignedInteger('vendors__id');
            $table->unsignedInteger('contacts__id');
            $table->unsignedInteger('users__id')->nullable();
            $table->dateTime('scheduled_at');
            $table->string('action_type', 50)->default('notification');
            $table->text('title_note')->nullable();
            $table->string('template_name')->nullable();
            $table->string('template_language', 10)->default('fr');
            $table->unsignedTinyInteger('status')->default(1); // 1=pending, 2=executed, 3=cancelled
            $table->longText('__data')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
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
        Schema::dropIfExists('contact_reminders');
    }
};
