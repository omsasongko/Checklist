<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ActivityCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_code' => 'MORNING_ROUTINE', 'name' => 'Rutinitas Pagi', 'icon' => 'bi-sunrise', 'sort_order' => 10],
            ['category_code' => 'PERSONAL_HYGIENE', 'name' => 'Kebersihan Pribadi', 'icon' => 'bi-droplet', 'sort_order' => 20],
            ['category_code' => 'NUTRITION', 'name' => 'Nutrisi', 'icon' => 'bi-egg-fried', 'sort_order' => 30],
            ['category_code' => 'HEALTH_FITNESS', 'name' => 'Kesehatan & Kebugaran', 'icon' => 'bi-heart-pulse', 'sort_order' => 40],
            ['category_code' => 'WORK_PRODUCTIVITY', 'name' => 'Pekerjaan & Produktivitas', 'icon' => 'bi-briefcase', 'sort_order' => 50],
            ['category_code' => 'LEARNING_EDUCATION', 'name' => 'Belajar & Pendidikan', 'icon' => 'bi-book', 'sort_order' => 60],
            ['category_code' => 'HOUSEHOLD', 'name' => 'Rumah Tangga', 'icon' => 'bi-house-heart', 'sort_order' => 70],
            ['category_code' => 'SOCIAL_FAMILY', 'name' => 'Sosial & Keluarga', 'icon' => 'bi-people', 'sort_order' => 80],
            ['category_code' => 'EVENING_ROUTINE', 'name' => 'Rutinitas Malam', 'icon' => 'bi-moon-stars', 'sort_order' => 90],
            ['category_code' => 'SLEEP_RECOVERY', 'name' => 'Tidur & Pemulihan', 'icon' => 'bi-bed', 'sort_order' => 100],
        ];

        foreach ($categories as $category) {
            $exists = $this->db->table('activity_categories')
                ->where('category_code', $category['category_code'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('activity_categories')->insert($category + ['is_active' => 1]);
            }
        }
    }
}
