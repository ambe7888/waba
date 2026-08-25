<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalises the three per-contact facts the discussions list needs, so
 * that list stops re-aggregating the vendor's whole message history on
 * every open/poll.
 *
 * Measured before this change (production, vendor 32 - 321k messages,
 * 24k contacts): the list query took 3394 ms, because it builds two
 * derived tables over every message the vendor ever exchanged, joins them
 * to contacts, sorts, and then keeps only 12 rows. A small vendor (6k
 * messages) took 17 ms - the cost scales with history, not with what's
 * displayed.
 *
 * This migration only adds the columns and their index. Nothing reads them
 * yet, so it is safe to deploy on its own: the list keeps using the old
 * joins until the read path is switched over in a follow-up change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Sort key for the discussions list (any direction).
            if (!Schema::hasColumn('contacts', 'last_message_at')) {
                $table->dateTime('last_message_at')->nullable()->after('bsuid');
            }
            // Replaces the has('lastIncomingMessage') EXISTS check - the list
            // only shows contacts who have written to us at least once.
            if (!Schema::hasColumn('contacts', 'last_incoming_message_at')) {
                $table->dateTime('last_incoming_message_at')->nullable()->after('last_message_at');
            }
            // Unread badge. Named to match the alias the old query exposed,
            // so nothing downstream (mobile app included) has to change when
            // the read path switches over.
            if (!Schema::hasColumn('contacts', 'unread_messages_count')) {
                $table->unsignedInteger('unread_messages_count')->default(0)->after('last_incoming_message_at');
            }
        });

        // Matches "WHERE vendors__id = ? ORDER BY is_pinned DESC,
        // last_message_at DESC, _id DESC" - InnoDB appends the primary key
        // to secondary indexes, so _id is already covered and MySQL can walk
        // this index backwards to satisfy the whole ORDER BY.
        if (!$this->hasIndex('idx_contacts_vendor_pinned_lastmsg')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(
                    ['vendors__id', 'is_pinned', 'last_message_at'],
                    'idx_contacts_vendor_pinned_lastmsg'
                );
            });
        }
    }

    /**
     * Raw SHOW INDEX rather than Schema::getIndexes(), so this migration
     * does not depend on a specific Laravel schema-builder version.
     */
    private function hasIndex(string $indexName): bool
    {
        return !empty(DB::select(
            'SHOW INDEX FROM contacts WHERE Key_name = ?',
            [$indexName]
        ));
    }

    public function down(): void
    {
        if ($this->hasIndex('idx_contacts_vendor_pinned_lastmsg')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropIndex('idx_contacts_vendor_pinned_lastmsg');
            });
        }

        Schema::table('contacts', function (Blueprint $table) {
            foreach (['unread_messages_count', 'last_incoming_message_at', 'last_message_at'] as $column) {
                if (Schema::hasColumn('contacts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
