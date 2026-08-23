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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id('_id');
            $table->char('_uid', 36)->unique();
            $table->integer('vendors__id')->unsigned()->index();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('product_categories__id')->unsigned()->nullable()->index()->after('vendors__id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_categories__id');
        });

        Schema::dropIfExists('product_categories');
    }
};
