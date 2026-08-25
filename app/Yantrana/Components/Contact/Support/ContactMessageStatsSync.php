<?php

namespace App\Yantrana\Components\Contact\Support;

use Illuminate\Support\Facades\DB;

/**
 * Keeps the denormalised message columns on `contacts` in step with the
 * message log.
 *
 * These columns exist so the discussions list can be a plain indexed read
 * instead of re-aggregating the vendor's whole history on every open and
 * poll (measured: 3394 ms -> 0.61 ms on the largest account). That only
 * holds if the values stay correct, which is what this class is for.
 *
 * Two safety properties are deliberate:
 *
 *  - Nothing here ever throws into the caller. A failed statistics update
 *    must never lose an actual message, so every entry point swallows and
 *    logs its errors; worst case a row goes stale until reconciliation.
 *  - `contacts:backfill-message-columns` recomputes everything from
 *    scratch and is safe to re-run, so any drift (bulk inserts that skip
 *    model events, a missed edge case) is repairable rather than
 *    permanent. Run it nightly.
 */
class ContactMessageStatsSync
{
    /**
     * Advance a contact's counters for a newly stored message.
     *
     * Uses GREATEST rather than a plain assignment so an out-of-order or
     * backfilled webhook can never move `last_message_at` backwards and
     * shuffle the discussions list under the user.
     */
    public static function messageStored(
        ?int $contactId,
        $messagedAt,
        bool $isIncoming,
        ?string $status
    ): void {
        if (!$contactId || !$messagedAt) {
            return;
        }

        try {
            $sets = ["last_message_at = GREATEST(COALESCE(last_message_at, '1000-01-01 00:00:00'), ?)"];
            $bindings = [$messagedAt];

            if ($isIncoming) {
                $sets[] = "last_incoming_message_at = GREATEST(COALESCE(last_incoming_message_at, '1000-01-01 00:00:00'), ?)";
                $bindings[] = $messagedAt;

                // 'received' is the status an inbound message carries until
                // the conversation is opened - i.e. exactly what the unread
                // badge counts.
                if ($status === 'received') {
                    $sets[] = "unread_messages_count = unread_messages_count + 1";
                }
            }

            $bindings[] = $contactId;

            DB::update(
                'UPDATE contacts SET ' . implode(', ', $sets) . ' WHERE _id = ?',
                $bindings
            );
        } catch (\Throwable $e) {
            \Log::warning('ContactMessageStatsSync::messageStored failed for contact ' . $contactId . ': ' . $e->getMessage());
        }
    }

    /**
     * Recount one contact's unread messages from the log.
     *
     * Used when a status transition makes an incremental delta unsafe (a
     * webhook can deliver the same status twice, which would double-count).
     * Covered by idx_logs_contact_incoming, so it stays cheap.
     */
    public static function recomputeUnread(?int $contactId): void
    {
        if (!$contactId) {
            return;
        }

        try {
            DB::update("
                UPDATE contacts c
                SET c.unread_messages_count = (
                    SELECT COUNT(*)
                    FROM whatsapp_message_logs m
                    WHERE m.contacts__id = c._id
                      AND m.is_incoming_message = 1
                      AND m.status = 'received'
                )
                WHERE c._id = ?
            ", [$contactId]);
        } catch (\Throwable $e) {
            \Log::warning('ContactMessageStatsSync::recomputeUnread failed for contact ' . $contactId . ': ' . $e->getMessage());
        }
    }

    /**
     * Clear the unread badge after the conversation has been read.
     *
     * markAsRead() flips the rows with a query-builder update, which fires
     * no model events - so this has to be called explicitly alongside it.
     */
    public static function markedAllRead(?int $contactId): void
    {
        if (!$contactId) {
            return;
        }

        try {
            DB::update('UPDATE contacts SET unread_messages_count = 0 WHERE _id = ?', [$contactId]);
        } catch (\Throwable $e) {
            \Log::warning('ContactMessageStatsSync::markedAllRead failed for contact ' . $contactId . ': ' . $e->getMessage());
        }
    }
}
