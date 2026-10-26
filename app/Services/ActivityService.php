<?php

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\ScheduleConflictResolver;
use App\Libraries\ScheduleRules;
use Throwable;

class ActivityService extends BaseService
{
    public function categories(): array
    {
        return $this->db->table('activity_categories')
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function rewardGroups(): array
    {
        return $this->db->table('reward_groups')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function verificationPolicies(): array
    {
        return $this->db->table('verification_policies')
            ->where('is_active', 1)
            ->orderBy('policy_code', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @param array{q?: string, category_id?: int|string, type?: string} $filters
     */
    public function activitiesForUser(int $userId, array $filters = [], bool $onlyActive = true): array
    {
        $builder = $this->db->table('activities a')
            ->select('a.*, c.name AS category_name, c.icon AS category_icon, rg.name AS reward_group_name, vp.policy_code, vp.verification_methods, vp.requires_evidence, vp.requires_approval, (SELECT COUNT(*) FROM activity_sessions s WHERE s.activity_id = a.id AND s.is_active = 1) AS active_session_count')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('reward_groups rg', 'rg.id = a.reward_group_id', 'left')
            ->join('verification_policies vp', 'vp.id = a.verification_policy_id', 'left')
            ->groupStart()
                ->where('a.activity_type !=', 'CUSTOM')
                ->orWhere('a.created_by_user_id', $userId)
            ->groupEnd();

        if ($onlyActive) {
            $builder->where('a.is_active', 1);
        }

        $query = trim((string) ($filters['q'] ?? ''));

        if ($query !== '') {
            $builder->groupStart()
                ->like('a.name', $query)
                ->orLike('a.description', $query)
                ->orLike('a.activity_code', $query)
                ->groupEnd();
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);

        if ($categoryId > 0) {
            $builder->where('a.category_id', $categoryId);
        }

        $type = strtoupper(trim((string) ($filters['type'] ?? '')));

        if ($type !== '' && in_array($type, ScheduleRules::ACTIVITY_TYPES, true)) {
            $builder->where('a.activity_type', $type);
        }

        return $builder
            ->orderBy('c.sort_order', 'ASC')
            ->orderBy('a.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function findVisibleActivity(int $activityId, int $userId, bool $includeInactive = false): array
    {
        $builder = $this->db->table('activities a')
            ->select('a.*, c.name AS category_name, c.icon AS category_icon, rg.name AS reward_group_name, vp.policy_code, vp.verification_methods, vp.requires_evidence, vp.requires_approval, (SELECT COUNT(*) FROM activity_sessions s WHERE s.activity_id = a.id AND s.is_active = 1) AS active_session_count')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('reward_groups rg', 'rg.id = a.reward_group_id', 'left')
            ->join('verification_policies vp', 'vp.id = a.verification_policy_id', 'left')
            ->where('a.id', $activityId)
            ->groupStart()
                ->where('a.activity_type !=', 'CUSTOM')
                ->orWhere('a.created_by_user_id', $userId)
            ->groupEnd();

        if (! $includeInactive) {
            $builder->where('a.is_active', 1);
        }

        $activity = $builder->get()->getRowArray();

        if ($activity === null) {
            throw new NotFoundException('Aktivitas tidak ditemukan.');
        }

        return $activity;
    }

    public function findActivityRecord(int $activityId): array
    {
        $activity = $this->db->table('activities')
            ->where('id', $activityId)
            ->get()
            ->getRowArray();

        if ($activity === null) {
            throw new NotFoundException('Aktivitas tidak ditemukan.');
        }

        return $activity;
    }

    public function sessionsForActivity(int $activityId, bool $activeOnly = true): array
    {
        $builder = $this->db->table('activity_sessions')
            ->where('activity_id', $activityId);

        if ($activeOnly) {
            $builder->where('is_active', 1);
        }

        return $builder
            ->orderBy('sort_order', 'ASC')
            ->orderBy('session_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function canViewActivity(array $activity, int $userId): bool
    {
        return $activity['activity_type'] !== 'CUSTOM'
            || (int) ($activity['created_by_user_id'] ?? 0) === $userId;
    }

    public function canModifyActivity(array $activity, int $userId): bool
    {
        return $activity['activity_type'] === 'CUSTOM'
            && (int) ($activity['created_by_user_id'] ?? 0) === $userId;
    }

    public function createCustomActivity(int $userId, array $input): int
    {
        $data = $this->validateCustomInput($input);
        $policyId = $this->policyId('SELF_CHECK');
        $now      = $this->nowUtc();

        $this->db->transBegin();

        try {
            $this->db->table('activities')->insert([
                'category_id'               => $data['category_id'],
                'reward_group_id'           => null,
                'activity_code'             => $this->generateCustomCode($userId),
                'name'                      => $data['name'],
                'description'               => $data['description'],
                'activity_type'             => 'CUSTOM',
                'repeat_type'               => $data['repeat_type'],
                'verification_policy_id'    => $policyId,
                'default_xp'                => 0,
                'default_rp'                => 0,
                'minimum_duration_seconds'  => null,
                'cooldown_seconds'          => $data['cooldown_seconds'],
                'maximum_daily_completions' => $data['maximum_daily_completions'],
                'maximum_weekly_completions' => $data['maximum_weekly_completions'],
                'schedule_policy'           => ScheduleConflictResolver::POLICY_EXCLUSIVE,
                'created_by_user_id'        => $userId,
                'is_active'                 => 1,
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
            $activityId = (int) $this->db->insertID();

            $this->createConfigurationVersion($activityId, $userId);
            $this->db->transCommit();

            return $activityId;
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    public function updateCustomActivity(int $userId, int $activityId, array $input): void
    {
        $activity = $this->findActivityRecord($activityId);

        if (! $this->canModifyActivity($activity, $userId)) {
            throw new ForbiddenException('Aktivitas bawaan atau milik pengguna lain tidak dapat diubah.');
        }

        $data = $this->validateCustomInput($input);

        $this->db->transBegin();

        try {
            $this->db->table('activities')
                ->where('id', $activityId)
                ->where('created_by_user_id', $userId)
                ->where('activity_type', 'CUSTOM')
                ->update([
                    'category_id'               => $data['category_id'],
                    'name'                      => $data['name'],
                    'description'               => $data['description'],
                    'repeat_type'               => $data['repeat_type'],
                    'cooldown_seconds'          => $data['cooldown_seconds'],
                    'maximum_daily_completions' => $data['maximum_daily_completions'],
                    'maximum_weekly_completions' => $data['maximum_weekly_completions'],
                    'updated_at'                => $this->nowUtc(),
                ]);

            $this->createConfigurationVersion($activityId, $userId);
            $this->db->transCommit();
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    public function deactivateCustomActivity(int $userId, int $activityId): void
    {
        $activity = $this->findActivityRecord($activityId);

        if (! $this->canModifyActivity($activity, $userId)) {
            throw new ForbiddenException('Aktivitas bawaan atau milik pengguna lain tidak dapat dinonaktifkan.');
        }

        $now = $this->nowUtc();

        $this->db->transBegin();

        try {
            $this->db->table('activities')
                ->where('id', $activityId)
                ->where('created_by_user_id', $userId)
                ->update([
                    'is_active'  => 0,
                    'updated_at' => $now,
                ]);

            $this->db->table('user_activity_plans')
                ->where('user_id', $userId)
                ->where('activity_id', $activityId)
                ->update([
                    'is_enabled' => 0,
                    'updated_at' => $now,
                ]);

            $this->db->transCommit();
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    public function latestConfigurationVersion(int $activityId): ?array
    {
        $version = $this->db->table('activity_configuration_versions')
            ->where('activity_id', $activityId)
            ->orderBy('version_number', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $version ?: null;
    }

    public function ensureConfigurationVersion(int $activityId, ?int $createdBy = null): int
    {
        $latest = $this->latestConfigurationVersion($activityId);

        if ($latest !== null) {
            return (int) $latest['id'];
        }

        return $this->createConfigurationVersion($activityId, $createdBy);
    }

    public function createConfigurationVersion(int $activityId, ?int $createdBy = null): int
    {
        $activity = $this->findActivityRecord($activityId);
        $sessions = $this->sessionsForActivity($activityId, false);
        $policy   = null;
        $group    = null;

        if ($activity['verification_policy_id'] !== null) {
            $policy = $this->db->table('verification_policies')
                ->where('id', $activity['verification_policy_id'])
                ->get()
                ->getRowArray();
        }

        if ($activity['reward_group_id'] !== null) {
            $group = $this->db->table('reward_groups')
                ->where('id', $activity['reward_group_id'])
                ->get()
                ->getRowArray();
        }

        $snapshot = [
            'activity'             => $activity,
            'sessions'             => $sessions,
            'verification_policy'  => $policy,
            'reward_group'         => $group,
            'captured_at'          => $this->nowUtc(),
        ];

        $latestVersion = $this->db->table('activity_configuration_versions')
            ->selectMax('version_number', 'max_version')
            ->where('activity_id', $activityId)
            ->get()
            ->getRowArray();

        $nextVersion = ((int) ($latestVersion['max_version'] ?? 0)) + 1;

        try {
            $this->db->table('activity_configuration_versions')->insert([
                'activity_id'            => $activityId,
                'version_number'         => $nextVersion,
                'configuration_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'effective_season_id'    => null,
                'created_by'             => $createdBy,
                'created_at'             => $this->nowUtc(),
            ]);

            return (int) $this->db->insertID();
        } catch (Throwable $exception) {
            if ($this->isDuplicateKey($exception)) {
                $latest = $this->latestConfigurationVersion($activityId);

                if ($latest !== null) {
                    return (int) $latest['id'];
                }
            }

            throw $exception;
        }
    }

    /**
     * @return array{name: string, description: string, category_id: int, repeat_type: string, maximum_daily_completions: int, maximum_weekly_completions: int, cooldown_seconds: int}
     */
    private function validateCustomInput(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));

        if (mb_strlen($name) < 3 || mb_strlen($name) > 255) {
            throw new ValidationException('Nama aktivitas harus 3 sampai 255 karakter.');
        }

        $description = trim((string) ($input['description'] ?? ''));

        if (mb_strlen($description) > 2000) {
            throw new ValidationException('Deskripsi maksimal 2000 karakter.');
        }

        $categoryId = (int) ($input['category_id'] ?? 0);
        $category   = $this->db->table('activity_categories')
            ->where('id', $categoryId)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if ($category === null) {
            throw new ValidationException('Kategori aktivitas tidak valid.');
        }

        $repeatType = strtoupper(trim((string) ($input['repeat_type'] ?? 'ONCE_DAILY')));

        if (! in_array($repeatType, ScheduleRules::CUSTOM_REPEAT_TYPES, true)) {
            throw new ValidationException('Jenis pengulangan aktivitas pribadi tidak valid.');
        }

        $maximumDaily = (int) ($input['maximum_daily_completions'] ?? 1);
        $maximumWeekly = (int) ($input['maximum_weekly_completions'] ?? 7);
        $cooldown     = (int) ($input['cooldown_seconds'] ?? 0);

        if ($maximumDaily < 1 || $maximumDaily > 20) {
            throw new ValidationException('Batas harian harus antara 1 dan 20.');
        }

        if ($maximumWeekly < 1 || $maximumWeekly > 100) {
            throw new ValidationException('Batas mingguan harus antara 1 dan 100.');
        }

        if ($cooldown < 0 || $cooldown > 86400) {
            throw new ValidationException('Jeda minimal harus antara 0 dan 86400 detik.');
        }

        if ($repeatType === 'ONCE_DAILY') {
            $maximumDaily = 1;
        }

        if ($repeatType !== 'REPEATABLE') {
            $cooldown = 0;
        }

        if ($maximumWeekly < $maximumDaily) {
            throw new ValidationException('Batas mingguan tidak boleh lebih kecil dari batas harian.');
        }

        return [
            'name'                       => $name,
            'description'                => $description,
            'category_id'                => $categoryId,
            'repeat_type'                => $repeatType,
            'maximum_daily_completions'  => $maximumDaily,
            'maximum_weekly_completions' => $maximumWeekly,
            'cooldown_seconds'           => $cooldown,
        ];
    }

    private function policyId(string $policyCode): int
    {
        $policy = $this->db->table('verification_policies')
            ->where('policy_code', $policyCode)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if ($policy === null) {
            throw new ValidationException('Kebijakan verifikasi belum tersedia.');
        }

        return (int) $policy['id'];
    }

    private function generateCustomCode(int $userId): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = sprintf(
                'CUSTOM_%d_%s',
                $userId,
                strtoupper(bin2hex(random_bytes(4))),
            );

            $exists = $this->db->table('activities')
                ->where('activity_code', $code)
                ->countAllResults() > 0;

            if (! $exists) {
                return $code;
            }
        }

        throw new ValidationException('Kode aktivitas pribadi tidak dapat dibuat. Silakan coba lagi.');
    }
}
