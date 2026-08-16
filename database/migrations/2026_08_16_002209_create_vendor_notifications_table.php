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
        Schema::create('vendor_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendors__id')->nullable()->index(); // Nullable for global notifications
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, success, warning, danger
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Note: Since this might be applied retroactively, we don't strictly require foreign key to vendors table to avoid issues if vendors table has a different engine or collation.
            // But usually we would do: $table->foreign('vendors__id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_notifications');
    }
};
