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
        if (!Schema::hasTable('vendor_notifications')) {
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
            return;
        }

        // A vendor_notifications table already existed (from the base install
        // schema, unrelated to this feature) without the columns this feature
        // needs — add just those instead of failing on "table already exists".
        Schema::table('vendor_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_notifications', 'title')) {
                $table->string('title')->nullable()->after('vendors__id');
            }
            if (!Schema::hasColumn('vendor_notifications', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('type');
            }
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
