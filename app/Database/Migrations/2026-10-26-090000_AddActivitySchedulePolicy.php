<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the schedule compatibility policy used by Phase 2 conflict
 * validation.
 *
 * Additive only: one column and one index on `activities`. Existing rows
 * default to `EXCLUSIVE`, which is the safe behaviour for the activities
 * already seeded. No data is removed or rewritten.
 */
class AddActivitySchedulePolicy extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('activities', [
            'schedule_policy' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'EXCLUSIVE',
                'after'      => 'maximum_weekly_completions',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `activities` ADD INDEX `idx_activities_schedule_policy` (`schedule_policy`)',
        );
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `activities` DROP INDEX `idx_activities_schedule_policy`');
        $this->forge->dropColumn('activities', 'schedule_policy');
    }
}
