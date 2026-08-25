<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fills the denormalised last_message_at / last_incoming_message_at /
 * unread_messages_count columns on contacts from the message log.
 *
 * Safe to re-run at any time: it recomputes from scratch rather than
 * incrementing, so it doubles as the reconciliation job that repairs any
 * drift if a live update is ever missed.
 */
class BackfillContactMessageColumns extends Command
{
    protected $signature = 'contacts:backfill-message-columns
                            {--vendor= : Only process this vendor id}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Recompute the denormalised last message / unread columns on contacts';

    public function handle()
    {
        foreach (['last_message_at', 'last_incoming_message_at', 'unread_messages_count'] as $column) {
            if (!Schema::hasColumn('contacts', $column)) {
                $this->error("Column contacts.$column is missing - run the migration first.");
                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');

        $vendorIds = $this->option('vendor')
            ? [(int) $this->option('vendor')]
            : DB::table('contacts')->distinct()->orderBy('vendors__id')->pluck('vendors__id')->all();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Processing ' . count($vendorIds) . ' vendor(s).');

        $totalContacts = 0;
        $startedAll = microtime(true);

        foreach ($vendorIds as $vendorId) {
            $started = microtime(true);

            if ($dryRun) {
                $pending = DB::table('contacts')->where('vendors__id', $vendorId)->count();
                $this->line("  vendor $vendorId: $pending contact(s) would be recomputed");
                $totalContacts += $pending;
                continue;
            }

            // One aggregate pass over this vendor's messages, applied in a
            // single UPDATE. Scoped per vendor so no statement ever locks the
            // whole table, and so a failure only affects one vendor.
            $affected = DB::update("
                UPDATE contacts c
                LEFT JOIN (
                    SELECT contacts__id,
                           MAX(messaged_at) AS last_at,
                           MAX(CASE WHEN is_incoming_message = 1 THEN messaged_at END) AS last_incoming_at,
                           SUM(CASE WHEN is_incoming_message = 1 AND status = 'received' THEN 1 ELSE 0 END) AS unread
                    FROM whatsapp_message_logs
                    WHERE vendors__id = ?
                    GROUP BY contacts__id
                ) m ON m.contacts__id = c._id
                SET c.last_message_at          = m.last_at,
                    c.last_incoming_message_at = m.last_incoming_at,
                    c.unread_messages_count    = COALESCE(m.unread, 0)
                WHERE c.vendors__id = ?
            ", [$vendorId, $vendorId]);

            $elapsed = round((microtime(true) - $started) * 1000);
            $totalContacts += $affected;
            $this->line("  vendor $vendorId: $affected contact(s) in {$elapsed} ms");
        }

        $totalElapsed = round(microtime(true) - $startedAll, 1);
        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Done - $totalContacts contact(s) in {$totalElapsed}s.");

        return self::SUCCESS;
    }
}
