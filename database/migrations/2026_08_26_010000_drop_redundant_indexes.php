<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes 14 indexes that were exact duplicates of, or leading prefixes
 * of, another index on the same table.
 *
 * They served no read: MySQL can satisfy the same lookups from the wider
 * index that remains. They were pure write cost - every message insert and
 * every delivery-status webhook had to maintain all of them.
 *
 * Measured on production: index footprint went from 600 MB to 479 MB on
 * whatsapp_message_logs and 108 MB to 90 MB on contacts (~139 MB), and
 * the message table went from 25 indexes to 17.
 *
 * Every drop was checked first against the foreign keys on these tables:
 * none of these was the only index whose leading column backed an FK, and
 * all 7 constraints were verified still present afterwards.
 *
 * Already applied by hand on production before this migration was written,
 * so the existence check makes it a no-op there while still bringing any
 * other environment to the same schema.
 */
return new class extends Migration
{
    /**
     * index name => the index that already covers it.
     */
    private array $plan = [
        'contacts' => [
            'fk_contacts_vendors1_idx'   => ['vendors__id', 'covered by idx_contacts_vendor_pinned_lastmsg and 4 others'],
            'idx_assigned_user'          => ['assigned_users__id', 'exact duplicate of fk_contacts_users1_idx'],
            'idx_contacts_id'            => ['_id', 're-declares the primary key'],
            'idx_contacts_uid'           => ['_uid', 'third index on a column that already has two UNIQUEs'],
            'idx_vendor_contact_status'  => ['vendors__id,status', 'prefix of idx_vendor_contact_status_created'],
            'idx_wa_id_on_contacts'      => ['wa_id', 'exact duplicate of idx_wa_id'],
        ],
        'whatsapp_message_logs' => [
            'fk_whatsapp_message_status_logs_campaigns1_idx' => ['campaigns__id', 'covered by idx_campaign_status_name'],
            'fk_whatsapp_message_status_logs_contacts1_idx'  => ['contacts__id', 'covered by idx_contact_incoming'],
            'fk_whatsapp_message_status_logs_vendors1_idx'   => ['vendors__id', 'covered by idx_vendor_msgtime and 6 others'],
            'idx_campaign_status'                            => ['campaigns__id,status', 'prefix of idx_campaign_status_name'],
            'idx_contact_incoming_msgtime'                   => ['contacts__id,is_incoming_message,messaged_at', 'exact duplicate of idx_contact_incoming'],
            'idx_messages_contact_incoming'                  => ['contacts__id,is_incoming_message', 'prefix of idx_contact_incoming'],
            'idx_vendor_message_log_campaign'                => ['vendors__id,campaigns__id', 'prefix of idx_vendor_message_log_campaign_msgtime'],
            'idx_vendor_message_log_campaign_status'         => ['vendors__id,campaigns__id,status', 'prefix of idx_whatsapp_message_log_vendor_status_name'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->plan as $table => $indexes) {
            foreach (array_keys($indexes) as $index) {
                if ($this->hasIndex($table, $index)) {
                    DB::statement("ALTER TABLE `$table` DROP INDEX `$index`");
                }
            }
        }
    }

    /**
     * Recreates them exactly as they were, so this is reversible even
     * though they are not worth having.
     */
    public function down(): void
    {
        foreach ($this->plan as $table => $indexes) {
            foreach ($indexes as $index => [$columns, $_reason]) {
                if ($this->hasIndex($table, $index)) {
                    continue;
                }
                $cols = implode(', ', array_map(
                    fn ($c) => '`' . trim($c) . '`',
                    explode(',', $columns)
                ));
                DB::statement("ALTER TABLE `$table` ADD INDEX `$index` ($cols)");
            }
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]));
    }
};
