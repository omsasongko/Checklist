<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the Phase 2 planning and daily-instance tables.
 *
 * `seasons` is created as a minimal forward-compatible table so that
 * activity instances and configuration snapshots can carry an optional
 * season reference. No season data or season lifecycle logic is created
 * in Phase 2.
 */
class CreateActivityPlanningTables extends Migration
{
    public function up(): void
    {
        $this->createSeasons();
        $this->createActivityConfigurationVersions();
        $this->createUserActivityPlans();
        $this->createDailyActivityInstances();
    }

    public function down(): void
    {
        $this->forge->dropTable('daily_activity_instances', true);
        $this->forge->dropTable('user_activity_plans', true);
        $this->forge->dropTable('activity_configuration_versions', true);
        $this->forge->dropTable('seasons', true);
    }

    private function createSeasons(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'season_number' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'season_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'starts_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'ends_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'SCHEDULED',
                'null'       => false,
            ],
            'submissions_closed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'finalized_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('season_number', 'uk_seasons_number');
        $this->forge->addKey('status', false, false, 'idx_seasons_status');
        $this->forge->addKey(['starts_at', 'ends_at'], false, false, 'idx_seasons_dates');
        $this->forge->createTable('seasons', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createActivityConfigurationVersions(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'activity_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'version_number' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'configuration_snapshot' => [
                'type' => 'JSON',
                'null' => false,
            ],
            'effective_season_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['activity_id', 'version_number'], 'uk_acv_activity_version');
        $this->forge->addKey('activity_id', false, false, 'idx_acv_activity');
        $this->forge->addKey('effective_season_id', false, false, 'idx_acv_season');
        $this->forge->addForeignKey('activity_id', 'activities', 'id', 'CASCADE', 'CASCADE', 'fk_acv_activity');
        $this->forge->addForeignKey('effective_season_id', 'seasons', 'id', 'CASCADE', 'NO ACTION', 'fk_acv_season');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'NO ACTION', 'fk_acv_creator');
        $this->forge->createTable('activity_configuration_versions', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createUserActivityPlans(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'activity_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'recurrence_rule' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'timezone' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => 'Asia/Jakarta',
                'null'       => false,
            ],
            'preferred_start_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'preferred_end_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'is_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'activity_id'], 'uk_uap_user_activity');
        $this->forge->addKey('user_id', false, false, 'idx_uap_user');
        $this->forge->addKey('activity_id', false, false, 'idx_uap_activity');
        $this->forge->addKey(['user_id', 'is_enabled'], false, false, 'idx_uap_user_enabled');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'NO ACTION', 'fk_uap_user');
        $this->forge->addForeignKey('activity_id', 'activities', 'id', 'CASCADE', 'CASCADE', 'fk_uap_activity');
        $this->forge->createTable('user_activity_plans', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createDailyActivityInstances(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'activity_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'activity_session_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'occurrence_number' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'occurrence_identity' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'activity_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'competition_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'season_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'configuration_version_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'completion_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'PENDING',
                'null'       => false,
            ],
            'verification_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'NOT_REQUIRED',
                'null'       => false,
            ],
            'reward_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'NOT_ELIGIBLE',
                'null'       => false,
            ],
            'scheduled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'scheduled_end_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('occurrence_identity', 'uk_dai_occurrence');
        $this->forge->addKey(['user_id', 'activity_date'], false, false, 'idx_dai_user_date');
        $this->forge->addKey(['user_id', 'activity_id', 'activity_date'], false, false, 'idx_dai_user_activity_date');
        $this->forge->addKey('competition_date', false, false, 'idx_dai_competition');
        $this->forge->addKey('verification_status', false, false, 'idx_dai_verification');
        $this->forge->addKey('reward_status', false, false, 'idx_dai_reward');
        $this->forge->addKey('season_id', false, false, 'idx_dai_season');
        $this->forge->addKey(['activity_date', 'completion_status'], false, false, 'idx_dai_status_date');
        $this->forge->addKey('scheduled_at', false, false, 'idx_dai_scheduled');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'NO ACTION', 'fk_dai_user');
        $this->forge->addForeignKey('activity_id', 'activities', 'id', 'CASCADE', 'NO ACTION', 'fk_dai_activity');
        $this->forge->addForeignKey('activity_session_id', 'activity_sessions', 'id', 'CASCADE', 'SET NULL', 'fk_dai_session');
        $this->forge->addForeignKey('season_id', 'seasons', 'id', 'CASCADE', 'NO ACTION', 'fk_dai_season');
        $this->forge->addForeignKey('configuration_version_id', 'activity_configuration_versions', 'id', 'CASCADE', 'NO ACTION', 'fk_dai_config');
        $this->forge->createTable('daily_activity_instances', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }
}
