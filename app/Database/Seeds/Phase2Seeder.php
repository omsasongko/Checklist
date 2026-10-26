<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Phase2Seeder extends Seeder
{
    public function run(): void
    {
        $this->call('ActivityCategorySeeder');
        $this->call('RewardGroupSeeder');
        $this->call('VerificationPolicySeeder');
        $this->call('ActivityCatalogSeeder');
    }
}
