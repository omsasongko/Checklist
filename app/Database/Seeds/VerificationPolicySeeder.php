<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class VerificationPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'policy_code'              => 'SELF_CHECK',
                'verification_methods'     => json_encode(['SELF_CHECK']),
                'requires_evidence'        => 0,
                'requires_approval'        => 0,
                'minimum_duration_seconds' => null,
            ],
            [
                'policy_code'              => 'TIMER',
                'verification_methods'     => json_encode(['TIMER']),
                'requires_evidence'        => 0,
                'requires_approval'        => 0,
                'minimum_duration_seconds' => 300,
            ],
            [
                'policy_code'              => 'PHOTO_PROOF',
                'verification_methods'     => json_encode(['PHOTO_PROOF']),
                'requires_evidence'        => 1,
                'requires_approval'        => 0,
                'minimum_duration_seconds' => null,
            ],
            [
                'policy_code'              => 'ADMIN_APPROVAL',
                'verification_methods'     => json_encode(['ADMIN_APPROVAL']),
                'requires_evidence'        => 1,
                'requires_approval'        => 1,
                'minimum_duration_seconds' => null,
            ],
        ];

        foreach ($policies as $policy) {
            $exists = $this->db->table('verification_policies')
                ->where('policy_code', $policy['policy_code'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('verification_policies')->insert($policy + ['is_active' => 1]);
            }
        }
    }
}
