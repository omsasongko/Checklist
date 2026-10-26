<?php

namespace App\Database\Seeds;

use App\Services\ActivityService;
use CodeIgniter\Database\Seeder;
use RuntimeException;

class ActivityCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = $this->lookup('activity_categories', 'category_code');
        $groupIds    = $this->lookup('reward_groups', 'group_code');
        $policyIds   = $this->lookup('verification_policies', 'policy_code');

        foreach (['MORNING_ROUTINE', 'PERSONAL_HYGIENE', 'NUTRITION', 'HEALTH_FITNESS', 'WORK_PRODUCTIVITY', 'LEARNING_EDUCATION', 'HOUSEHOLD', 'SOCIAL_FAMILY', 'EVENING_ROUTINE', 'SLEEP_RECOVERY'] as $code) {
            if (! isset($categoryIds[$code])) {
                throw new RuntimeException('Kategori ' . $code . ' belum tersedia. Jalankan Phase2Seeder.');
            }
        }

        foreach (['MORNING', 'HYGIENE', 'NUTRITION', 'FITNESS', 'PRODUCTIVITY', 'LEARNING', 'HOUSEHOLD', 'SOCIAL', 'EVENING', 'SLEEP'] as $code) {
            if (! isset($groupIds[$code])) {
                throw new RuntimeException('Reward group ' . $code . ' belum tersedia. Jalankan Phase2Seeder.');
            }
        }

        foreach (['SELF_CHECK', 'TIMER', 'PHOTO_PROOF', 'ADMIN_APPROVAL'] as $code) {
            if (! isset($policyIds[$code])) {
                throw new RuntimeException('Kebijakan verifikasi ' . $code . ' belum tersedia. Jalankan Phase2Seeder.');
            }
        }

        $now        = gmdate('Y-m-d H:i:s');
        $activities = [
            [
                'activity_code'              => 'WAKE_UP_PERSONAL_SCHEDULE',
                'category_id'                => $categoryIds['MORNING_ROUTINE'],
                'reward_group_id'            => $groupIds['MORNING'],
                'name'                       => 'Bangun Sesuai Jadwal Pribadi',
                'description'                => 'Bangun pada waktu yang Anda rencanakan sendiri dan mulai hari dengan sadar.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['SELF_CHECK'],
                'default_xp'                 => 5,
                'default_rp'                 => 10,
                'minimum_duration_seconds'   => null,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
                'schedule_policy'            => 'EXCLUSIVE',
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'PERSONAL_HYGIENE_ROUTINE',
                'category_id'                => $categoryIds['PERSONAL_HYGIENE'],
                'reward_group_id'            => $groupIds['HYGIENE'],
                'name'                       => 'Selesaikan Rutinitas Kebersihan Pribadi',
                'description'                => 'Selesaikan rutinitas kebersihan pribadi pagi atau malam.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['SELF_CHECK'],
                'default_xp'                 => 10,
                'default_rp'                 => 10,
                'minimum_duration_seconds'   => null,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'PLANNED_BREAKFAST_ROUTINE',
                'category_id'                => $categoryIds['NUTRITION'],
                'reward_group_id'            => $groupIds['NUTRITION'],
                'name'                       => 'Selesaikan Rutinitas Sarapan Terencana',
                'description'                => 'Siapkan dan nikmati sarapan terencana yang sesuai kebutuhan.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['SELF_CHECK'],
                'default_xp'                 => 10,
                'default_rp'                 => 10,
                'minimum_duration_seconds'   => null,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'PHYSICAL_EXERCISE',
                'category_id'                => $categoryIds['HEALTH_FITNESS'],
                'reward_group_id'            => $groupIds['FITNESS'],
                'name'                       => 'Lakukan Latihan Fisik yang Sesuai',
                'description'                => 'Lakukan latihan fisik yang sesuai kemampuan dan kondisi Anda.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['TIMER'],
                'default_xp'                 => 20,
                'default_rp'                 => 20,
                'minimum_duration_seconds'   => 600,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'FOCUSED_WORK_STUDY',
                'category_id'                => $categoryIds['WORK_PRODUCTIVITY'],
                'reward_group_id'            => $groupIds['PRODUCTIVITY'],
                'name'                       => 'Selesaikan Sesi Kerja atau Belajar Fokus',
                'description'                => 'Kerjakan tugas utama dengan fokus tanpa gangguan.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['TIMER'],
                'default_xp'                 => 15,
                'default_rp'                 => 20,
                'minimum_duration_seconds'   => 900,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'LEARN_NEW_SKILL',
                'category_id'                => $categoryIds['LEARNING_EDUCATION'],
                'reward_group_id'            => $groupIds['LEARNING'],
                'name'                       => 'Pelajari atau Latih Keterampilan Baru',
                'description'                => 'Pelajari atau latih keterampilan baru secara terarah.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['TIMER'],
                'default_xp'                 => 10,
                'default_rp'                 => 15,
                'minimum_duration_seconds'   => 600,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'TIDY_LIVING_SPACE',
                'category_id'                => $categoryIds['HOUSEHOLD'],
                'reward_group_id'            => $groupIds['HOUSEHOLD'],
                'name'                       => 'Rapikan Ruang Hunian Pribadi',
                'description'                => 'Rapikan kamar atau ruang hunian pribadi pada sesi pagi dan malam.',
                'repeat_type'                => 'FIXED_SESSIONS',
                'verification_policy_id'     => $policyIds['SELF_CHECK'],
                'default_xp'                 => 10,
                'default_rp'                 => 10,
                'minimum_duration_seconds'   => null,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 2,
                'maximum_weekly_completions' => 14,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'SOCIAL_INTERACTION',
                'category_id'                => $categoryIds['SOCIAL_FAMILY'],
                'reward_group_id'            => $groupIds['SOCIAL'],
                'name'                       => 'Lakukan Interaksi Sosial yang Bermakna',
                'description'                => 'Luangkan waktu untuk interaksi sosial yang bermakna.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['SELF_CHECK'],
                'default_xp'                 => 10,
                'default_rp'                 => 10,
                'minimum_duration_seconds'   => null,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'REVIEW_DAY_PLAN_TOMORROW',
                'category_id'                => $categoryIds['EVENING_ROUTINE'],
                'reward_group_id'            => $groupIds['EVENING'],
                'name'                       => 'Evaluasi Hari dan Rencanakan Besok',
                'description'                => 'Tinjau pencapaian hari ini dan susun rencana untuk besok.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['SELF_CHECK'],
                'default_xp'                 => 5,
                'default_rp'                 => 10,
                'minimum_duration_seconds'   => null,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
            [
                'activity_code'              => 'SLEEP_REST_PLAN',
                'category_id'                => $categoryIds['SLEEP_RECOVERY'],
                'reward_group_id'            => $groupIds['SLEEP'],
                'name'                       => 'Ikuti Rencana Tidur atau Istirahat Pribadi',
                'description'                => 'Ikuti rencana tidur atau istirahat pribadi untuk pemulihan.',
                'repeat_type'                => 'ONCE_DAILY',
                'verification_policy_id'     => $policyIds['SELF_CHECK'],
                'default_xp'                 => 10,
                'default_rp'                 => 15,
                'minimum_duration_seconds'   => null,
                'cooldown_seconds'           => 0,
                'maximum_daily_completions'  => 1,
                'maximum_weekly_completions' => 7,
                'schedule_policy'            => 'EXCLUSIVE',
            ],
        ];

        $activityService = new ActivityService($this->db);

        foreach ($activities as $activity) {
            $existing = $this->db->table('activities')
                ->where('activity_code', $activity['activity_code'])
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $this->db->table('activities')->insert($activity + [
                    'activity_type'       => 'STANDARD',
                    'created_by_user_id'  => null,
                    'is_active'           => 1,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
                $activityId = (int) $this->db->insertID();
            } else {
                $activityId = (int) $existing['id'];

                // Idempotent: keep the canonical compatibility policy on
                // seeded standard activities. Only the policy is touched.
                if (strtoupper((string) ($existing['schedule_policy'] ?? '')) !== $activity['schedule_policy']) {
                    $this->db->table('activities')
                        ->where('id', $activityId)
                        ->update([
                            'schedule_policy' => $activity['schedule_policy'],
                            'updated_at'      => $now,
                        ]);
                }
            }

            if ($activity['activity_code'] === 'TIDY_LIVING_SPACE') {
                $this->seedTidySessions($activityId);
            }

            $activityService->ensureConfigurationVersion($activityId, null);
        }
    }

    private function seedTidySessions(int $activityId): void
    {
        $sessions = [
            [
                'session_code'    => 'MORNING_TIDYING',
                'session_name'    => 'Sesi Pagi',
                'scheduled_start' => '06:00:00',
                'scheduled_end'   => '09:00:00',
                'xp_value'        => 5,
                'rp_value'        => 5,
                'sort_order'      => 10,
            ],
            [
                'session_code'    => 'EVENING_TIDYING',
                'session_name'    => 'Sesi Malam',
                'scheduled_start' => '18:00:00',
                'scheduled_end'   => '22:00:00',
                'xp_value'        => 5,
                'rp_value'        => 5,
                'sort_order'      => 20,
            ],
        ];

        foreach ($sessions as $session) {
            $exists = $this->db->table('activity_sessions')
                ->where('activity_id', $activityId)
                ->where('session_code', $session['session_code'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('activity_sessions')->insert($session + [
                    'activity_id' => $activityId,
                    'is_active'   => 1,
                ]);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function lookup(string $table, string $codeColumn): array
    {
        $rows = $this->db->table($table)
            ->select($codeColumn . ' AS code, id')
            ->get()
            ->getResultArray();

        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row['code']] = (int) $row['id'];
        }

        return $lookup;
    }
}
