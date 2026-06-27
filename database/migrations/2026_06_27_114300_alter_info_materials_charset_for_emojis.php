<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE info_materials CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En cas de rollback, on peut éventuellement revenir à l'encodage précédent (utf8)
        DB::statement('ALTER TABLE info_materials CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
    }
};
