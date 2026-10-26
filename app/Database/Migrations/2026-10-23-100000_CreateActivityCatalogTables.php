<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the Phase 2 activity catalog tables.
 *
 * These tables are new and reference the Shield `users` table with
 * `INT UNSIGNED` columns. No existing application tables are modified.
 */
class CreateActivityCatalogTables extends Migration
{
    public function up(): void
    {
        $this->createActivityCategories();
        $this->createRewardGroups();
        $this->createVerificationPolicies();
        $this->createActivities();
        $this->createActivitySessions();
    }

    public function down(): void
    {
        $this->forge->dropTable('activity_sessions', true);
        $this->forge->dropTable('activities', true);
        $this->forge->dropTable('verification_policies', true);
        $this->forge->dropTable('reward_groups', true);
        $this->forge->dropTable('activity_categories', true);
    }

    private function createActivityCategories(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'category_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('category_code', 'uk_activity_categories_code');
        $this->forge->addKey('is_active', false, false, 'idx_activity_categories_active');
        $this->forge->createTable('activity_categories', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createRewardGroups(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'group_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'default_daily_rp_budget' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('group_code', 'uk_reward_groups_code');
        $this->forge->addKey('is_active', false, false, 'idx_reward_groups_active');
        $this->forge->createTable('reward_groups', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createVerificationPolicies(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'policy_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'verification_methods' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'requires_evidence' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'requires_approval' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'minimum_duration_seconds' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('policy_code', 'uk_verification_policies_code');
        $this->forge->addKey('is_active', false, false, 'idx_verification_policies_active');
        $this->forge->createTable('verification_policies', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createActivities(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'category_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'reward_group_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'activity_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'activity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'repeat_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'verification_policy_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'default_xp' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'default_rp' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'minimum_duration_seconds' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'cooldown_seconds' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'maximum_daily_completions' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'maximum_weekly_completions' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 7,
            ],
            'created_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'is_active' => [
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
        $this->forge->addUniqueKey('activity_code', 'uk_activities_code');
        $this->forge->addKey('category_id', false, false, 'idx_activities_category');
        $this->forge->addKey('reward_group_id', false, false, 'idx_activities_reward_group');
        $this->forge->addKey('verification_policy_id', false, false, 'idx_activities_policy');
        $this->forge->addKey('created_by_user_id', false, false, 'idx_activities_owner');
        $this->forge->addKey('activity_type', false, false, 'idx_activities_type');
        $this->forge->addKey('repeat_type', false, false, 'idx_activities_repeat');
        $this->forge->addKey('is_active', false, false, 'idx_activities_active');
        $this->forge->addForeignKey('category_id', 'activity_categories', 'id', 'CASCADE', 'SET NULL', 'fk_activities_category');
        $this->forge->addForeignKey('reward_group_id', 'reward_groups', 'id', 'CASCADE', 'SET NULL', 'fk_activities_reward_group');
        $this->forge->addForeignKey('verification_policy_id', 'verification_policies', 'id', 'CASCADE', 'SET NULL', 'fk_activities_policy');
        $this->forge->addForeignKey('created_by_user_id', 'users', 'id', 'CASCADE', 'NO ACTION', 'fk_activities_owner');
        $this->forge->createTable('activities', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createActivitySessions(): void
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
            'session_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'session_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'scheduled_start' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'scheduled_end' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'xp_value' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'rp_value' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['activity_id', 'session_code'], 'uk_activity_sessions_activity_code');
        $this->forge->addKey('activity_id', false, false, 'idx_activity_sessions_activity');
        $this->forge->addKey('is_active', false, false, 'idx_activity_sessions_active');
        $this->forge->addForeignKey('activity_id', 'activities', 'id', 'CASCADE', 'CASCADE', 'fk_activity_sessions_activity');
        $this->forge->createTable('activity_sessions', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }
}
