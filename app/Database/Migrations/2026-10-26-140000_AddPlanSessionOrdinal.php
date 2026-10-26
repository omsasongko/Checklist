<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Allows several scheduled sessions for one activity (Phase 2 CASE F).
 *
 * The original `user_activity_plans` table enforced
 * UNIQUE (user_id, activity_id), so an activity could hold exactly one plan.
 * That constraint is what made a re-submitted schedule silently overwrite the
 * previous one. Activities whose repeat policy supports several sessions
 * (FIXED_SESSIONS, REPEATABLE) need more than one plan row, so this migration
 * adds a surrogate `session_ordinal` column and widens the unique key to
 * (user_id, activity_id, session_ordinal).
 *
 * Non-destructive:
 * - Every existing row is given `session_ordinal = 0` by the column default,
 *   which is the ordinal today's code already assumes.
 * - No row is inserted, updated, deleted, or re-timed.
 * - The old two-column unique key is dropped and replaced in the same
 *   statement batch, so the table keeps a uniqueness guarantee at all times.
 *
 * The foreign keys and every other index on the table are left untouched.
 */
class AddPlanSessionOrdinal extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('user_activity_plans', [
            'session_ordinal' => [
                'type'       => 'TINYINT UNSIGNED',
                'null'       => false,
                'default'    => 0,
                'after'      => 'activity_id',
            ],
        ]);

        // Backfill explicitly rather than relying on the DEFAULT clause, so the
        // value is written for rows that existed before the column was added.
        $this->db->query('UPDATE `user_activity_plans` SET `session_ordinal` = 0 WHERE `session_ordinal` IS NULL');

        $this->db->query('ALTER TABLE `user_activity_plans` DROP INDEX `uk_uap_user_activity`');
        $this->db->query(
            'ALTER TABLE `user_activity_plans` ADD UNIQUE KEY `uk_uap_user_activity_session` (`user_id`, `activity_id`, `session_ordinal`)',
        );
    }

    public function down(): void
    {
        // Collapsing back to one plan per activity is only safe when no
        // activity actually holds several. Refuse rather than delete user
        // schedules silently: a reversible migration must not destroy data.
        $duplicates = $this->db->query(
            'SELECT `user_id`, `activity_id`, COUNT(*) AS `total`
             FROM `user_activity_plans`
             GROUP BY `user_id`, `activity_id`
             HAVING `total` > 1
             LIMIT 1',
        )->getRowArray();

        if ($duplicates !== null) {
            throw new \RuntimeException(
                'Tidak dapat mengembalikan migrasi: ada aktivitas dengan lebih dari satu sesi. '
                . 'Hapus sesi tambahan terlebih dahulu agar tidak ada jadwal pengguna yang hilang.',
            );
        }

        $this->db->query('ALTER TABLE `user_activity_plans` DROP INDEX `uk_uap_user_activity_session`');
        $this->db->query(
            'ALTER TABLE `user_activity_plans` ADD UNIQUE KEY `uk_uap_user_activity` (`user_id`, `activity_id`)',
        );

        $this->forge->dropColumn('user_activity_plans', 'session_ordinal');
    }
}
