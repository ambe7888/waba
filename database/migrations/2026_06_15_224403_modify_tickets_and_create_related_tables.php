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
            $table->integer('contacts__id')->unsigned()->nullable()->change();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->increments('_id');
            $table->uuid('_uid')->unique();
            $table->integer('tickets__id')->unsigned()->index();
            $table->integer('users__id')->unsigned()->index();
            $table->text('message');
            $table->longText('__data')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_labels', function (Blueprint $table) {
            $table->increments('_id');
            $table->uuid('_uid')->unique();
            $table->integer('tickets__id')->unsigned()->index();
            $table->integer('labels__id')->unsigned()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_labels');
        Schema::dropIfExists('ticket_replies');

        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('contacts__id')->unsigned()->nullable(false)->change();
        });
    }
};
