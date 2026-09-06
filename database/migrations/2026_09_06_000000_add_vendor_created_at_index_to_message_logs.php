<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The vendor dashboard's "messages today/yesterday" and "7-day history"
 * queries both filter whatsapp_message_logs on vendors__id + a created_at
 * range. Every existing time-based index on this table is built on
 * messaged_at, not created_at - none of them cover this filter, so MySQL
 * fell back to a plan that only uses vendors__id and then checks
 * created_at row by row.
 *
 * Measured on vendor 32 (349,601 message rows, 351,279 for this vendor):
 * both queries scanned effectively the whole vendor's history in the
 * chosen "vendors__id"-only plan - 2212 ms and 1853 ms respectively,
 * accounting for ~4s of an observed 5-10s total dashboard load when
 * viewed from the super-admin panel (Vendor::vendorDashboard() calls the
 * same DashboardEngine::prepareVendorDashboardData() the vendor's own
 * login uses).
 *
 * Switching the filter to messaged_at instead was considered and
 * rejected: 10.5% of this vendor's rows (and ~13% globally) have
 * messaged_at NULL on an ongoing basis (still happening within the hour
 * before this was written, not just historical rows), so filtering on it
 * would silently undercount "today"/"last 7 days" stats. Adding the
 * matching index instead needs no behaviour change.
 *
 * Verified with a throwaway index before writing this migration: 2212 ms
 * -> 63 ms and 1853 ms -> 4.8 ms on vendor 32, MySQL picks it correctly
 * (range scan on ~31k rows instead of an effective full scan of 351k).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!$this->hasIndex('idx_vendor_created_at')) {
            Schema::table('whatsapp_message_logs', function (Blueprint $table) {
                $table->index(['vendors__id', 'created_at'], 'idx_vendor_created_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('idx_vendor_created_at')) {
            Schema::table('whatsapp_message_logs', function (Blueprint $table) {
                $table->dropIndex('idx_vendor_created_at');
            });
        }
    }

    private function hasIndex(string $indexName): bool
    {
        return !empty(DB::select(
            'SHOW INDEX FROM whatsapp_message_logs WHERE Key_name = ?',
            [$indexName]
        ));
    }
};
