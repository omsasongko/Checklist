<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RewardGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['group_code' => 'MORNING', 'name' => 'Rutinitas Pagi', 'description' => 'Aktivitas bangun dan memulai hari.', 'default_daily_rp_budget' => 10],
            ['group_code' => 'HYGIENE', 'name' => 'Kebersihan Pribadi', 'description' => 'Aktivitas kebersihan dan perawatan diri.', 'default_daily_rp_budget' => 10],
            ['group_code' => 'NUTRITION', 'name' => 'Nutrisi', 'description' => 'Aktivitas makan dan pemenuhan nutrisi.', 'default_daily_rp_budget' => 10],
            ['group_code' => 'FITNESS', 'name' => 'Kebugaran', 'description' => 'Aktivitas fisik dan olahraga.', 'default_daily_rp_budget' => 20],
            ['group_code' => 'PRODUCTIVITY', 'name' => 'Produktivitas', 'description' => 'Pekerjaan dan tugas produktif.', 'default_daily_rp_budget' => 20],
            ['group_code' => 'LEARNING', 'name' => 'Pembelajaran', 'description' => 'Belajar dan mengembangkan keterampilan.', 'default_daily_rp_budget' => 15],
            ['group_code' => 'HOUSEHOLD', 'name' => 'Rumah Tangga', 'description' => 'Merawat dan merapikan ruang hidup.', 'default_daily_rp_budget' => 10],
            ['group_code' => 'SOCIAL', 'name' => 'Sosial', 'description' => 'Interaksi sosial dan keluarga.', 'default_daily_rp_budget' => 10],
            ['group_code' => 'EVENING', 'name' => 'Rutinitas Malam', 'description' => 'Evaluasi hari dan bersiap untuk istirahat.', 'default_daily_rp_budget' => 10],
            ['group_code' => 'SLEEP', 'name' => 'Tidur', 'description' => 'Tidur dan pemulihan tubuh.', 'default_daily_rp_budget' => 15],
            ['group_code' => 'STREAK', 'name' => 'Bonus Streak', 'description' => 'Cadangan anggaran untuk bonus konsistensi.', 'default_daily_rp_budget' => 10],
        ];

        foreach ($groups as $group) {
            $exists = $this->db->table('reward_groups')
                ->where('group_code', $group['group_code'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('reward_groups')->insert($group + ['is_active' => 1]);
            }
        }
    }
}
