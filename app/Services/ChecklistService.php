<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\ScheduleRules;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

class ChecklistService extends BaseService
{
    private ActivityService $activityService;

    public function __construct(?ActivityService $activityService = null, ?\CodeIgniter\Database\BaseConnection $db = null)
    {
        parent::__construct($db);
        $this->activityService = $activityService ?? new ActivityService($this->db);
    }

    public function getChecklist(int $userId, string $date, string $viewTimezone = ScheduleRules::DEFAULT_TIMEZONE): array
    {
        $date         = ScheduleRules::assertDate($date);
        $viewTimezone = $this->normalizeTimezone($viewTimezone);

        $this->ensureInstancesForDate($userId, $date);

        $rows = $this->db->table('daily_activity_instances i')
            ->select('i.*, a.name AS activity_name, a.description AS activity_description, a.activity_type, a.repeat_type, a.default_xp, a.default_rp, a.cooldown_seconds, a.maximum_daily_completions, a.maximum_weekly_completions, a.created_by_user_id, c.name AS category_name, c.icon AS category_icon, s.session_name, s.session_code, s.scheduled_start, s.scheduled_end, s.xp_value AS session_xp, s.rp_value AS session_rp, vp.policy_code, vp.verification_methods, p.timezone AS plan_timezone, p.is_enabled AS plan_enabled, p.preferred_start_time, p.preferred_end_time')
            ->join('activities a', 'a.id = i.activity_id')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('activity_sessions s', 's.id = i.activity_session_id', 'left')
            ->join('verification_policies vp', 'vp.id = a.verification_policy_id', 'left')
            ->join('user_activity_plans p', 'p.user_id = i.user_id AND p.activity_id = i.activity_id', 'left')
            ->where('i.user_id', $userId)
            ->where('i.activity_date', $date)
            ->orderBy('i.scheduled_at', 'ASC')
            ->orderBy('i.occurrence_number', 'ASC')
            ->orderBy('a.name', 'ASC')
            ->get()
            ->getResultArray();

        $dailyCounts  = $this->completionCountsByActivity($userId, $date, $date);
        $weekBounds   = ScheduleRules::weekBounds($date);
        $weeklyCounts = $this->completionCountsByActivity($userId, $weekBounds['start'], $weekBounds['end']);
        $lastCompleted = $this->lastCompletionByActivity($userId);

        $items = [];
        $completed = 0;

        foreach ($rows as $row) {
            $activityId   = (int) $row['activity_id'];
            $isCompleted  = $row['completion_status'] === 'COMPLETED';
            $planTimezone = $this->normalizeTimezone((string) ($row['plan_timezone'] ?? $viewTimezone));
            $isToday      = $date === ScheduleRules::todayInTimezone($planTimezone);
            $planEnabled  = $row['plan_enabled'] !== null && (int) $row['plan_enabled'] === 1;
            $dailyDone    = (int) ($dailyCounts[$activityId] ?? 0);
            $weeklyDone   = (int) ($weeklyCounts[$activityId] ?? 0);
            $maxDaily     = max(1, (int) $row['maximum_daily_completions']);
            $maxWeekly    = max(1, (int) $row['maximum_weekly_completions']);
            $cooldown     = ScheduleRules::cooldownRemainingSeconds(
                $lastCompleted[$activityId] ?? null,
                (int) ($row['cooldown_seconds'] ?? 0),
            );

            if ($isCompleted) {
                $completed++;
            }

            $dailyLimitReached  = ! $isCompleted && $dailyDone >= $maxDaily;
            $weeklyLimitReached = ! $isCompleted && $weeklyDone >= $maxWeekly;

            $allowed = ! $isCompleted
                && $isToday
                && $planEnabled
                && ! $dailyLimitReached
                && ! $weeklyLimitReached
                && $cooldown === 0;

            $reason = null;

            if ($isCompleted) {
                $reason = 'Aktivitas sudah selesai.';
            } elseif (! $isToday) {
                $reason = 'Hanya dapat diselesaikan pada tanggal aktivitas.';
            } elseif (! $planEnabled) {
                $reason = 'Jadwal aktivitas sedang dinonaktifkan.';
            } elseif ($weeklyLimitReached) {
                $reason = 'Batas mingguan telah tercapai.';
            } elseif ($dailyLimitReached) {
                $reason = 'Batas harian telah tercapai.';
            } elseif ($cooldown > 0) {
                $reason = 'Tunggu ' . $this->formatDuration($cooldown) . ' lagi.';
            }

            $startTime = $row['scheduled_start'] ?: $row['preferred_start_time'];
            $endTime   = $row['scheduled_end'] ?: $row['preferred_end_time'];

            $item = [
                'id'                        => (int) $row['id'],
                'activity_id'               => $activityId,
                'activity_name'             => $row['activity_name'],
                'activity_description'      => $row['activity_description'],
                'activity_type'            => $row['activity_type'],
                'repeat_type'               => $row['repeat_type'],
                'category_name'             => $row['category_name'] ?: 'Lainnya',
                'category_icon'             => $row['category_icon'],
                'session_name'              => $row['session_name'],
                'session_code'              => $row['session_code'],
                'occurrence_number'         => $row['occurrence_number'] !== null ? (int) $row['occurrence_number'] : null,
                'occurrence_identity'       => $row['occurrence_identity'],
                'activity_date'             => $row['activity_date'],
                'competition_date'          => $row['competition_date'],
                'plan_timezone'             => $planTimezone,
                'completion_status'         => $row['completion_status'],
                'verification_status'       => $row['verification_status'],
                'reward_status'             => $row['reward_status'],
                'completed_at'              => $row['completed_at'],
                'completed_at_local'        => $this->toLocalDateTime($row['completed_at'], $planTimezone),
                'is_completed'              => $isCompleted,
                'is_today'                  => $isToday,
                'plan_enabled'              => $planEnabled,
                'completion_allowed'        => $allowed,
                'completion_reason'         => $reason,
                'cooldown_remaining_seconds' => $cooldown,
                'daily_completed'           => $dailyDone,
                'daily_max'                 => $maxDaily,
                'weekly_completed'          => $weeklyDone,
                'weekly_max'                => $maxWeekly,
                'schedule_label'            => $this->scheduleLabel($startTime, $endTime),
                'policy_code'               => $row['policy_code'],
                'verification_methods'      => $row['verification_methods'],
                'xp_reference'              => (int) ($row['session_xp'] ?? $row['default_xp']),
                'rp_reference'              => (int) ($row['session_rp'] ?? $row['default_rp']),
                'xp_awarded'                => 0,
                'rp_awarded'                => 0,
            ];

            $items[] = $item;
        }

        $groups = [];
        foreach ($items as $item) {
            $groupName = $item['category_name'];
            $groups[$groupName] ??= [
                'name'  => $groupName,
                'icon'  => $item['category_icon'],
                'items' => [],
            ];
            $groups[$groupName]['items'][] = $item;
        }

        $total = count($items);

        return [
            'date'     => $date,
            'timezone' => $viewTimezone,
            'today'    => ScheduleRules::todayInTimezone($viewTimezone),
            'items'    => $items,
            'groups'   => array_values($groups),
            'progress' => [
                'completed' => $completed,
                'total'     => $total,
                'percent'   => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            ],
        ];
    }

    public function ensureInstancesForDate(int $userId, string $date): void
    {
        $date = ScheduleRules::assertDate($date);

        $plans = $this->db->table('user_activity_plans p')
            ->select('p.*, a.repeat_type, a.activity_type, a.maximum_daily_completions, a.maximum_weekly_completions')
            ->join('activities a', 'a.id = p.activity_id')
            ->where('p.user_id', $userId)
            ->where('p.is_enabled', 1)
            ->where('a.is_active', 1)
            ->get()
            ->getResultArray();

        if ($plans === []) {
            return;
        }

        $now = $this->nowUtc();

        $this->db->transBegin();

        try {
            foreach ($plans as $plan) {
                $recurrence = ScheduleRules::normalizeRecurrence($plan['recurrence_rule']);

                if (! ScheduleRules::isScheduledOn($recurrence, $date)) {
                    continue;
                }

                $activityId = (int) $plan['activity_id'];
                $userIdInt  = (int) $plan['user_id'];
                $timezone   = $this->normalizeTimezone((string) ($plan['timezone'] ?? ScheduleRules::DEFAULT_TIMEZONE));
                $repeatType = (string) $plan['repeat_type'];
                $maxDaily   = max(1, (int) $plan['maximum_daily_completions']);
                $configId   = $this->activityService->ensureConfigurationVersion(
                    $activityId,
                    $plan['activity_type'] === 'CUSTOM' ? $userIdInt : null,
                );
                $competitionDate = ScheduleRules::competitionDate($date, $timezone);

                $sessions = $repeatType === 'FIXED_SESSIONS'
                    ? $this->activityService->sessionsForActivity($activityId, true)
                    : [];

                $slots = [];

                if ($repeatType === 'FIXED_SESSIONS' && $sessions !== []) {
                    foreach ($sessions as $session) {
                        $slots[] = [
                            'session'           => $session,
                            'occurrence_number' => null,
                        ];
                    }
                } elseif ($repeatType === 'REPEATABLE') {
                    for ($number = 1; $number <= $maxDaily; $number++) {
                        $slots[] = [
                            'session'           => null,
                            'occurrence_number' => $number,
                        ];
                    }
                } else {
                    $slots[] = [
                        'session'           => null,
                        'occurrence_number' => null,
                    ];
                }

                foreach ($slots as $slot) {
                    $session = $slot['session'];
                    $sessionCode = $session['session_code'] ?? null;
                    $sessionId   = $session !== null ? (int) $session['id'] : null;
                    $startTime   = $session['scheduled_start'] ?? $plan['preferred_start_time'];
                    $endTime     = $session['scheduled_end'] ?? $plan['preferred_end_time'];

                    $startMinutes = ScheduleRules::timeToMinutes($startTime);
                    $endMinutes   = ScheduleRules::timeToMinutes($endTime);
                    $endNextDay   = $startMinutes !== null && $endMinutes !== null && $endMinutes <= $startMinutes;

                    $occurrenceIdentity = ScheduleRules::occurrenceIdentity(
                        $userIdInt,
                        $activityId,
                        $date,
                        $repeatType,
                        $sessionCode,
                        $slot['occurrence_number'],
                    );

                    $row = [
                        'user_id'                 => $userIdInt,
                        'activity_id'             => $activityId,
                        'activity_session_id'     => $sessionId,
                        'occurrence_number'       => $slot['occurrence_number'],
                        'occurrence_identity'     => $occurrenceIdentity,
                        'activity_date'           => $date,
                        'competition_date'        => $competitionDate,
                        'season_id'               => null,
                        'configuration_version_id' => $configId,
                        'completion_status'       => 'PENDING',
                        'verification_status'     => 'NOT_REQUIRED',
                        'reward_status'           => 'NOT_ELIGIBLE',
                        'scheduled_at'            => ScheduleRules::localDateTimeToUtc($date, $startTime, $timezone),
                        'scheduled_end_at'        => ScheduleRules::localDateTimeToUtc($date, $endTime, $timezone, $endNextDay),
                        'completed_at'            => null,
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ];

                    $this->db->table('daily_activity_instances')
                        ->ignore(true)
                        ->insert($row);
                }
            }

            $this->db->transCommit();
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    public function completeInstance(int $userId, int $instanceId, ?int $expectedActivityId = null): array
    {
        if ($instanceId <= 0) {
            throw new ValidationException('Aktivitas harian tidak valid.');
        }

        $this->db->transBegin();

        try {
            $row = $this->db->query(
                'SELECT i.*, a.name AS activity_name, a.repeat_type, a.maximum_daily_completions, a.maximum_weekly_completions, a.cooldown_seconds, a.is_active AS activity_active, a.activity_type, a.created_by_user_id, vp.policy_code, p.timezone AS plan_timezone, p.is_enabled AS plan_enabled
                 FROM daily_activity_instances i
                 JOIN activities a ON a.id = i.activity_id
                 LEFT JOIN verification_policies vp ON vp.id = a.verification_policy_id
                 LEFT JOIN user_activity_plans p ON p.user_id = i.user_id AND p.activity_id = i.activity_id
                 WHERE i.id = ? AND i.user_id = ?
                 FOR UPDATE',
                [$instanceId, $userId],
            )->getRowArray();

            if ($row === null) {
                throw new NotFoundException('Aktivitas harian tidak ditemukan.');
            }

            if ($expectedActivityId !== null && (int) $row['activity_id'] !== $expectedActivityId) {
                throw new NotFoundException('Aktivitas harian tidak ditemukan.');
            }

            if ($row['activity_type'] === 'CUSTOM'
                && (int) ($row['created_by_user_id'] ?? 0) !== $userId) {
                throw new ForbiddenException('Anda tidak dapat menyelesaikan aktivitas pribadi pengguna lain.');
            }

            if ($row['completion_status'] === 'COMPLETED') {
                $this->db->transCommit();

                return $this->completionResponse($userId, $row, true);
            }

            if (! (int) $row['activity_active']) {
                throw new ConflictException('Aktivitas ini sedang tidak aktif.');
            }

            if ($row['plan_enabled'] === null || ! (int) $row['plan_enabled']) {
                throw new ConflictException('Jadwal aktivitas ini sedang dinonaktifkan.');
            }

            $planTimezone = $this->normalizeTimezone((string) ($row['plan_timezone'] ?? ScheduleRules::DEFAULT_TIMEZONE));

            if ($row['activity_date'] !== ScheduleRules::todayInTimezone($planTimezone)) {
                throw new ConflictException('Aktivitas hanya dapat diselesaikan pada tanggal aktivitasnya.');
            }

            $this->db->query(
                'SELECT id FROM user_activity_plans WHERE user_id = ? AND activity_id = ? FOR UPDATE',
                [$userId, (int) $row['activity_id']],
            );

            $dailyCount = $this->db->table('daily_activity_instances')
                ->where('user_id', $userId)
                ->where('activity_id', (int) $row['activity_id'])
                ->where('activity_date', $row['activity_date'])
                ->where('completion_status', 'COMPLETED')
                ->where('id !=', $instanceId)
                ->countAllResults();

            if ($dailyCount >= max(1, (int) $row['maximum_daily_completions'])) {
                throw new ConflictException('Batas harian aktivitas ini telah tercapai.');
            }

            $weekBounds = ScheduleRules::weekBounds($row['activity_date']);
            $weeklyCount = $this->db->table('daily_activity_instances')
                ->where('user_id', $userId)
                ->where('activity_id', (int) $row['activity_id'])
                ->where('activity_date >=', $weekBounds['start'])
                ->where('activity_date <=', $weekBounds['end'])
                ->where('completion_status', 'COMPLETED')
                ->where('id !=', $instanceId)
                ->countAllResults();

            if ($weeklyCount >= max(1, (int) $row['maximum_weekly_completions'])) {
                throw new ConflictException('Batas mingguan aktivitas ini telah tercapai.');
            }

            $lastCompleted = $this->db->table('daily_activity_instances')
                ->select('completed_at')
                ->where('user_id', $userId)
                ->where('activity_id', (int) $row['activity_id'])
                ->where('completion_status', 'COMPLETED')
                ->where('completed_at IS NOT NULL', null, false)
                ->orderBy('completed_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $cooldown = ScheduleRules::cooldownRemainingSeconds(
                $lastCompleted['completed_at'] ?? null,
                (int) ($row['cooldown_seconds'] ?? 0),
            );

            if ($cooldown > 0) {
                throw new ConflictException('Tunggu ' . $this->formatDuration($cooldown) . ' sebelum menyelesaikan aktivitas ini lagi.');
            }

            $verificationStatus = match ($row['policy_code'] ?? '') {
                'SELF_CHECK'                       => 'AUTO_VALIDATED',
                'TIMER', 'PHOTO_PROOF', 'ADMIN_APPROVAL' => 'PENDING',
                default                            => 'NOT_REQUIRED',
            };

            $completedAt = $this->nowUtc();

            $this->db->table('daily_activity_instances')
                ->where('id', $instanceId)
                ->where('user_id', $userId)
                ->update([
                    'completion_status'   => 'COMPLETED',
                    'verification_status' => $verificationStatus,
                    'reward_status'       => 'NOT_ELIGIBLE',
                    'completed_at'         => $completedAt,
                    'updated_at'          => $completedAt,
                ]);

            $this->db->transCommit();

            $row['completion_status']   = 'COMPLETED';
            $row['verification_status'] = $verificationStatus;
            $row['reward_status']       = 'NOT_ELIGIBLE';
            $row['completed_at']        = $completedAt;

            return $this->completionResponse($userId, $row, false);
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    public function getHistory(
        int $userId,
        string $from,
        string $to,
        string $status = 'ALL',
        int $page = 1,
        int $perPage = 20,
        string $timezone = ScheduleRules::DEFAULT_TIMEZONE,
    ): array {
        $from = ScheduleRules::assertDate($from);
        $to   = ScheduleRules::assertDate($to);

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $timezone = $this->normalizeTimezone($timezone);
        $status   = strtoupper($status);

        if (! in_array($status, ['ALL', 'PENDING', 'COMPLETED'], true)) {
            $status = 'ALL';
        }

        $page    = max(1, $page);
        $perPage = min(100, max(5, $perPage));
        $offset  = ($page - 1) * $perPage;
        $now     = $this->nowUtc();

        $builder = static function () use ($userId, $from, $to, $status) {
            $query = db_connect()->table('daily_activity_instances i')
                ->where('i.user_id', $userId)
                ->where('i.activity_date >=', $from)
                ->where('i.activity_date <=', $to);

            if ($status !== 'ALL') {
                $query->where('i.completion_status', $status);
            }

            return $query;
        };

        $total = $builder()->countAllResults();

        $rows = $builder()
            ->select('i.*, a.name AS activity_name, a.repeat_type, c.name AS category_name, c.icon AS category_icon, s.session_name, p.timezone AS plan_timezone')
            ->join('activities a', 'a.id = i.activity_id')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('activity_sessions s', 's.id = i.activity_session_id', 'left')
            ->join('user_activity_plans p', 'p.user_id = i.user_id AND p.activity_id = i.activity_id', 'left')
            ->orderBy('i.activity_date', 'DESC')
            ->orderBy('i.completed_at', 'DESC')
            ->orderBy('i.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['completed_at_local'] = $this->toLocalDateTime(
                $row['completed_at'],
                $this->normalizeTimezone((string) ($row['plan_timezone'] ?? $timezone)),
            );
        }
        unset($row);

        $summaryRows = $this->db->table('daily_activity_instances')
            ->select('completion_status, COUNT(*) AS total')
            ->where('user_id', $userId)
            ->where('activity_date >=', $from)
            ->where('activity_date <=', $to)
            ->groupBy('completion_status')
            ->get()
            ->getResultArray();

        $summary = ['completed' => 0, 'pending' => 0];

        foreach ($summaryRows as $summaryRow) {
            if ($summaryRow['completion_status'] === 'COMPLETED') {
                $summary['completed'] = (int) $summaryRow['total'];
            } elseif ($summaryRow['completion_status'] === 'PENDING') {
                $summary['pending'] = (int) $summaryRow['total'];
            }
        }

        return [
            'items'     => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'pages'     => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'from'      => $from,
            'to'        => $to,
            'status'    => $status,
            'timezone'  => $timezone,
            'summary'   => $summary,
            'generated_at' => $now,
        ];
    }

    private function completionResponse(int $userId, array $row, bool $alreadyCompleted): array
    {
        $timezone = $this->normalizeTimezone((string) ($row['plan_timezone'] ?? ScheduleRules::DEFAULT_TIMEZONE));
        $checklist = $this->getChecklist($userId, $row['activity_date'], $timezone);

        return [
            'instance_id'        => (int) $row['id'],
            'activity_id'        => (int) $row['activity_id'],
            'activity_name'      => $row['activity_name'],
            'activity_date'      => $row['activity_date'],
            'completion_status'  => 'COMPLETED',
            'verification_status' => $row['verification_status'],
            'reward_status'      => 'NOT_ELIGIBLE',
            'completed_at'       => $row['completed_at'],
            'completed_at_local' => $this->toLocalDateTime($row['completed_at'], $timezone),
            'already_completed'  => $alreadyCompleted,
            'xp_awarded'         => 0,
            'rp_awarded'         => 0,
            'reward_note'        => 'XP dan RP belum diberikan pada Fase 2.',
            'message'            => $alreadyCompleted
                ? 'Aktivitas ini sudah diselesaikan.'
                : 'Aktivitas berhasil diselesaikan. XP/RP belum diberikan pada Fase 2.',
            'progress'           => $checklist['progress'],
        ];
    }

    private function completionCountsByActivity(int $userId, string $from, string $to): array
    {
        $rows = $this->db->table('daily_activity_instances')
            ->select('activity_id, COUNT(*) AS total')
            ->where('user_id', $userId)
            ->where('activity_date >=', $from)
            ->where('activity_date <=', $to)
            ->where('completion_status', 'COMPLETED')
            ->groupBy('activity_id')
            ->get()
            ->getResultArray();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['activity_id']] = (int) $row['total'];
        }

        return $counts;
    }

    private function lastCompletionByActivity(int $userId): array
    {
        $rows = $this->db->table('daily_activity_instances')
            ->select('activity_id, MAX(completed_at) AS last_completed')
            ->where('user_id', $userId)
            ->where('completed_at IS NOT NULL', null, false)
            ->groupBy('activity_id')
            ->get()
            ->getResultArray();

        $last = [];
        foreach ($rows as $row) {
            $last[(int) $row['activity_id']] = $row['last_completed'];
        }

        return $last;
    }

    private function normalizeTimezone(string $timezone): string
    {
        $timezone = trim($timezone);

        if ($timezone === '') {
            return ScheduleRules::DEFAULT_TIMEZONE;
        }

        ScheduleRules::assertTimezone($timezone);

        return $timezone;
    }

    private function toLocalDateTime(?string $utcDateTime, string $timezone): ?string
    {
        if ($utcDateTime === null || $utcDateTime === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone($timezone))
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function scheduleLabel(?string $start, ?string $end): ?string
    {
        $start = ScheduleRules::normalizeTime($start);
        $end   = ScheduleRules::normalizeTime($end);

        if ($start === null && $end === null) {
            return null;
        }

        if ($start !== null && $end !== null) {
            return $start . ' - ' . $end;
        }

        return $start ?? $end;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' detik';
        }

        return (int) ceil($seconds / 60) . ' menit';
    }
}
