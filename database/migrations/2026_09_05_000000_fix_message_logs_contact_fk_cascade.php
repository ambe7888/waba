<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permanently deleting a vendor with any real message history always
 * failed with "SQLSTATE[23000] ... 1452 Cannot add or update a child
 * row", naming the vendors -> whatsapp_message_logs FK even though that
 * FK itself is a plain ON DELETE CASCADE and nothing was wrong with it
 * directly.
 *
 * The real cause: whatsapp_message_logs.contacts__id -> contacts was
 * ON DELETE SET NULL, while contacts.vendors__id -> vendors is ON
 * DELETE CASCADE. Deleting a vendor cascades to its contacts (deleted)
 * AND, independently, cascades to its message logs (deleted) - the
 * SAME message log rows end up needing both a DELETE (via the direct
 * vendors FK) and an UPDATE-to-NULL (via the contacts FK, since their
 * contact is also being deleted in the same statement). InnoDB can't
 * resolve a row being both deleted and updated by two FK actions in
 * one cascading operation, and reports it as this confusing "child
 * row" error - reproduced directly against production data (rolled
 * back, nothing changed) by deleting vendor 94 inside a transaction.
 *
 * Fix: switch the contacts__id FK to CASCADE too, so both paths agree
 * (delete, not delete-vs-update). This also means a message log never
 * outlives the contact it belongs to, which is more sensible anyway -
 * a log row with contacts__id NULL was never meaningfully displayable.
 */
return new class extends Migration
{
    public function up(): void
    {
        $constraint = $this->findConstraintName();
        if ($constraint && $this->currentDeleteRule($constraint) !== 'CASCADE') {
            DB::statement("ALTER TABLE whatsapp_message_logs DROP FOREIGN KEY `{$constraint}`");
            DB::statement("
                ALTER TABLE whatsapp_message_logs
                ADD CONSTRAINT `{$constraint}`
                FOREIGN KEY (contacts__id) REFERENCES contacts (_id)
                ON DELETE CASCADE
            ");
        }
    }

    public function down(): void
    {
        $constraint = $this->findConstraintName();
        if ($constraint && $this->currentDeleteRule($constraint) !== 'SET NULL') {
            DB::statement("ALTER TABLE whatsapp_message_logs DROP FOREIGN KEY `{$constraint}`");
            DB::statement("
                ALTER TABLE whatsapp_message_logs
                ADD CONSTRAINT `{$constraint}`
                FOREIGN KEY (contacts__id) REFERENCES contacts (_id)
                ON DELETE SET NULL
            ");
        }
    }

    private function findConstraintName(): ?string
    {
        $row = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'whatsapp_message_logs'
              AND COLUMN_NAME = 'contacts__id'
              AND REFERENCED_TABLE_NAME = 'contacts'
        ");
        return $row->CONSTRAINT_NAME ?? null;
    }

    private function currentDeleteRule(string $constraint): ?string
    {
        $row = DB::selectOne("
            SELECT DELETE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = ?
        ", [$constraint]);
        return $row->DELETE_RULE ?? null;
    }
};
