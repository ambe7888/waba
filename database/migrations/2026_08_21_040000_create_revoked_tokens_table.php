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
        if (Schema::hasTable('revoked_tokens')) {
            return;
        }

        // Denylist of logged-out auth token jti's, independent of the
        // (disabled, tableless) yes-token-auth token_registry feature.
        // A row here makes verifyToken() reject that token even though its
        // JWT signature/expiry are still otherwise valid.
        Schema::create('revoked_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('jti')->unique();
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revoked_tokens');
    }
};
