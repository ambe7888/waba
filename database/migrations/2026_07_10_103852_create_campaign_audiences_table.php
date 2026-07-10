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
        Schema::create('campaign_audiences', function (Blueprint $table) {
            $table->id('_id');
            $table->uuid('_uid');
            $table->integer('vendors__id')->unsigned()->index('fk_campaign_audiences_vendors_idx');
            $table->string('title', 255);
            $table->json('contacts')->nullable(); // array of contact _id
            $table->json('groups')->nullable(); // array of group _id
            $table->json('labels')->nullable(); // array of label _id
            $table->integer('status')->default(1);
            $table->timestamps();
            
            $table->foreign('vendors__id', 'fk_campaign_audiences_vendors')->references('_id')->on('vendors')->onDelete('cascade')->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_audiences');
    }
};
