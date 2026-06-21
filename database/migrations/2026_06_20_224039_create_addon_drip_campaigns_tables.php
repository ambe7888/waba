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
        Schema::create('addon_drip_campaigns', function (Blueprint $table) {
            $table->id('_id');
            $table->uuid('_uid')->unique();
            $table->integer('vendors__id')->unsigned()->index();
            $table->string('title');
            $table->tinyInteger('status')->default(1)->comment('1: Active, 2: Inactive');
            $table->timestamps();
        });

        Schema::create('addon_drip_steps', function (Blueprint $table) {
            $table->id('_id');
            $table->uuid('_uid')->unique();
            $table->bigInteger('addon_drip_campaigns__id')->unsigned()->index();
            $table->integer('day_delay')->default(0)->comment('0 = immediate, 1 = 1 day later, etc.');
            $table->integer('whatsapp_templates__id')->unsigned()->nullable();
            $table->text('custom_message')->nullable(); // In case they want to send non-template (inside 24h window), but we'll focus on templates usually. Let's keep it just in case.
            $table->timestamps();

            $table->foreign('addon_drip_campaigns__id')->references('_id')->on('addon_drip_campaigns')->onDelete('cascade');
        });

        Schema::create('addon_drip_subscribers', function (Blueprint $table) {
            $table->id('_id');
            $table->uuid('_uid')->unique();
            $table->bigInteger('addon_drip_campaigns__id')->unsigned()->index();
            $table->integer('contacts__id')->unsigned()->index();
            $table->dateTime('start_date');
            $table->bigInteger('last_step_id')->unsigned()->nullable(); // Tracks the last step completed
            $table->tinyInteger('status')->default(1)->comment('1: Active, 2: Completed, 3: Unsubscribed');
            $table->timestamps();

            $table->foreign('addon_drip_campaigns__id')->references('_id')->on('addon_drip_campaigns')->onDelete('cascade');
        });
        
        // Add drip campaign selection to bot_replies if possible, but bot_replies is existing table.
        // We will modify bot_replies table to add addon_drip_campaigns__id
        Schema::table('bot_replies', function (Blueprint $table) {
            $table->bigInteger('addon_drip_campaigns__id')->unsigned()->nullable();
            $table->foreign('addon_drip_campaigns__id', 'fk_bot_replies_drip_campaign_id')->references('_id')->on('addon_drip_campaigns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_replies', function (Blueprint $table) {
            $table->dropForeign('fk_bot_replies_drip_campaign_id');
            $table->dropColumn('addon_drip_campaigns__id');
        });
        
        Schema::dropIfExists('addon_drip_subscribers');
        Schema::dropIfExists('addon_drip_steps');
        Schema::dropIfExists('addon_drip_campaigns');
    }
};
