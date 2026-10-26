<?php

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ScheduleConfirmationException;
use App\Exceptions\ScheduleConflictException;
use App\Exceptions\ValidationException;
use App\Libraries\ScheduleConflictResolver;
use App\Libraries\ScheduleRules;
use Throwable;

/**
 * Personal activity plans (Phase 2).
 *
 * Every write path that can create or activate an overlap is serialized per
 * user with a row lock on the Shield `users` row, then validated against the
 * user's other enabled plans using ScheduleConflictResolver. The lock is
 * released on commit/rollback, so two simultaneous submissions cannot both
 * pass validation.
 *
 * Schedules are never modified or deleted automatically: an existing schedule
 * is only replaced when the user explicitly confirms, and existing overlaps are
 * only reported.
 */
class ActivityScheduleService extends BaseService
{
    /**
     * Explicit plan operations. The operation decides what the write does; it
     * is never inferred from whichever row happens to match.
     */
    public const OPERATION_CREATE      = 'CREATE';
    public const OPERATION_REPLACE     = 'REPLACE';
    public const OPERATION_ADD_SESSION = 'ADD_SESSION';

    public const OPERATIONS = [
        self::OPERATION_CREATE,
        self::OPERATION_REPLACE,
        self::OPERATION_ADD_SESSION,
    ];

    /**
     * Row-lock keys that gate plan writes, ordered so that the activity
     * deactivation path and the plan paths never deadlock each other.
     */
    private const LOCK_REFERENCES = ['user', 'activity'];

    private ActivityService $activityService;

    public function __construct(?ActivityService $activityService = null, ?\CodeIgniter\Database\BaseConnection $db = null)
    {
        parent::__construct($db);
        $this->activityService = $activityService ?? new ActivityService($this->db);
    }

    public function plans(int $userId): array
    {
        $plans = $this->db->table('user_activity_plans p')
            ->select('p.*, a.name AS activity_name, a.activity_code, a.description, a.activity_type, a.repeat_type, a.default_xp, a.default_rp, a.schedule_policy, a.is_active AS activity_active, c.id AS category_id, c.name AS category_name, c.icon AS category_icon, vp.policy_code')
            ->join('activities a', 'a.id = p.activity_id')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('verification_policies vp', 'vp.id = a.verification_policy_id', 'left')
            ->where('p.user_id', $userId)
            ->orderBy('p.is_enabled', 'DESC')
            ->orderBy('a.name', 'ASC')
            ->get()
            ->getResultArray();

        $normalized = [];

        foreach ($plans as &$plan) {
            $recurrence               = ScheduleRules::normalizeRecurrence($plan['recurrence_rule']);
            $plan['recurrence']       = $recurrence;
            $plan['recurrence_label'] = self::recurrenceLabel($recurrence);
            $plan['activity_sessions'] = $this->activityService->sessionsForActivity((int) $plan['activity_id']);
            $normalized[]             = $this->normalizeConflictPlan($plan);
        }
        unset($plan);

        $index = [];
        foreach ($normalized as $offset => $entry) {
            if (! $this->isConflictEligible($entry)) {
                continue;
            }

            foreach ($normalized as $otherOffset => $other) {
                if ($offset === $otherOffset || ! $this->isConflictEligible($other)) {
                    continue;
                }

                $conflict = ScheduleConflictResolver::evaluatePair($entry, $other);

                if ($conflict === null || $conflict['classification'] !== ScheduleConflictResolver::HARD_CONFLICT) {
                    continue;
                }

                $index[(int) $entry['plan_id']][] = $conflict;
            }
        }

        foreach ($plans as &$plan) {
            $plan['conflicts'] = $index[(int) $plan['id']] ?? [];
        }
        unset($plan);

        return $plans;
    }

    /**
     * Read-only report of currently overlapping enabled schedules.
     *
     * Each pair is reported once, keyed by the two plan IDs. Nothing is
     * modified or deleted.
     *
     * @return list<array<string, mixed>>
     */
    public function existingConflicts(int $userId): array
    {
        $plans = $this->db->table('user_activity_plans p')
            ->select('p.*, a.name AS activity_name, a.schedule_policy, c.id AS category_id, vp.policy_code')
            ->join('activities a', 'a.id = p.activity_id')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('verification_policies vp', 'vp.id = a.verification_policy_id', 'left')
            ->where('p.user_id', $userId)
            ->where('p.is_enabled', 1)
            ->where('a.is_active', 1)
            ->orderBy('p.preferred_start_time', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->get()
            ->getResultArray();

        $normalized = array_values(array_filter(
            array_map(fn (array $plan): array => $this->normalizeConflictPlan(
                $plan + ['activity_sessions' => $this->activityService->sessionsForActivity((int) $plan['activity_id'])],
            ), $plans),
            fn (array $plan): bool => $this->isConflictEligible($plan),
        ));

        $reports = [];
        $count   = count($normalized);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $conflict = ScheduleConflictResolver::evaluatePair($normalized[$i], $normalized[$j]);

                if ($conflict === null || $conflict['classification'] !== ScheduleConflictResolver::HARD_CONFLICT) {
                    continue;
                }

                $reports[] = $conflict;
            }
        }

        return $reports;
    }

    public function findPlan(int $userId, int $planId): array
    {
        $plan = $this->db->table('user_activity_plans p')
            ->select('p.*, a.name AS activity_name, a.activity_type, a.repeat_type, a.schedule_policy, a.is_active AS activity_active')
            ->join('activities a', 'a.id = p.activity_id')
            ->where('p.id', $planId)
            ->where('p.user_id', $userId)
            ->get()
            ->getRowArray();

        if ($plan === null) {
            throw new NotFoundException('Jadwal aktivitas tidak ditemukan.');
        }

        $plan['recurrence'] = ScheduleRules::normalizeRecurrence($plan['recurrence_rule']);

        return $plan;
    }

    /**
     * Create or update a plan.
     *
     * The operation is explicit and never inferred from the row that happens to
     * match:
     *
     * - `plan_id > 0`            -> UPDATE that plan (ownership checked).
     * - `operation = ADD_SESSION`-> INSERT a new session row.
     * - otherwise                -> CREATE. If the activity already has a
     *                               schedule this does NOT overwrite it; it
     *                               throws a ScheduleConfirmationException so
     *                               the user can choose replace / add / cancel.
     *
     * A CREATE that matches an existing plan exactly is reported as a duplicate
     * and changes nothing.
     *
     * @return array{plan_id: int, conflicts: list<array<string, mixed>>, duplicate?: bool, added_session?: bool}
     */
    public function savePlan(int $userId, array $input): array
    {
        $planId     = (int) ($input['plan_id'] ?? 0);
        $activityId = (int) ($input['activity_id'] ?? 0);

        if ($activityId <= 0) {
            throw new ValidationException('Pilih aktivitas yang akan dijadwalkan.');
        }

        $operation = strtoupper(trim((string) ($input['operation'] ?? '')));

        if (! in_array($operation, self::OPERATIONS, true)) {
            throw new ValidationException('Operasi jadwal tidak valid.');
        }

        if ($planId > 0) {
            $existingPlan = $this->findPlan($userId, $planId);

            if ((int) $existingPlan['activity_id'] !== $activityId) {
                throw new ValidationException('Aktivitas pada jadwal tidak dapat diganti saat mengubah jadwal.');
            }
        }

        $activity = $this->activityService->findVisibleActivity($activityId, $userId);

        if (! (int) $activity['is_active']) {
            throw new ValidationException('Aktivitas ini sedang tidak aktif.');
        }

        if ($activity['activity_type'] === 'CUSTOM'
            && (int) ($activity['created_by_user_id'] ?? 0) !== $userId) {
            throw new ForbiddenException('Anda tidak dapat menjadwalkan aktivitas pribadi pengguna lain.');
        }

        $timezone = trim((string) ($input['timezone'] ?? ScheduleRules::DEFAULT_TIMEZONE));
        ScheduleRules::assertTimezone($timezone);

        $recurrenceType = strtoupper(trim((string) ($input['recurrence_type'] ?? 'DAILY')));
        $days           = $input['recurrence_days'] ?? [];
        $days           = is_array($days) ? $days : [$days];
        $weekday        = (int) ($input['recurrence_weekday'] ?? 1);
        $recurrence     = ScheduleRules::buildRecurrence($recurrenceType, $days, $weekday);
        $startTime      = ScheduleRules::normalizeTime($input['preferred_start_time'] ?? null);
        $endTime        = ScheduleRules::normalizeTime($input['preferred_end_time'] ?? null);

        $this->assertTimeOrder($startTime, $endTime);

        $isEnabled = (int) ($input['is_enabled'] ?? 1) === 1 ? 1 : 0;
        $now       = $this->nowUtc();
        $payload   = [
            'user_id'              => $userId,
            'activity_id'          => $activityId,
            'recurrence_rule'      => json_encode($recurrence, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'timezone'             => $timezone,
            'preferred_start_time' => $startTime,
            'preferred_end_time'   => $endTime,
            'is_enabled'           => $isEnabled,
            'updated_at'           => $now,
        ];

        return $this->withUserScheduleLock($userId, $activityId, function () use ($userId, $planId, $activityId, $activity, $payload, $now, $isEnabled, $operation, $input): array {
            $candidate = $this->normalizeConflictPlan([
                'plan_id'              => 0,
                'user_id'              => $userId,
                'activity_id'          => $activityId,
                'recurrence_rule'      => $payload['recurrence_rule'],
                'timezone'             => $payload['timezone'],
                'preferred_start_time' => $payload['preferred_start_time'],
                'preferred_end_time'   => $payload['preferred_end_time'],
                'is_enabled'           => $isEnabled,
                'activity_name'        => $activity['name'],
                'schedule_policy'      => $activity['schedule_policy'] ?? ScheduleConflictResolver::POLICY_EXCLUSIVE,
                'category_id'          => $activity['category_id'] ?? null,
                'policy_code'          => $activity['policy_code'] ?? 'SELF_CHECK',
                'activity_sessions'    => $this->activityService->sessionsForActivity($activityId),
            ]);

            // ---------------------------------------------------------------
            // Explicit UPDATE of a known plan.
            //
            // The row is compared against everything else, never against
            // itself, so validation cannot be skipped by keeping its own id.
            // ---------------------------------------------------------------
            if ($planId > 0) {
                $conflicts = $isEnabled === 1
                    ? $this->validateCandidate($userId, $candidate, $planId)
                    : [];

                $this->db->table('user_activity_plans')
                    ->where('id', $planId)
                    ->where('user_id', $userId)
                    ->update($payload);

                return ['plan_id' => $planId, 'conflicts' => $conflicts];
            }

            // Re-read the activity's plans inside the lock: another request may
            // have created one since the page was rendered.
            $existingPlans = $this->plansForActivity($userId, $activityId);

            // ---------------------------------------------------------------
            // CASE B: an identical schedule already exists. Change nothing.
            //
            // Only a CREATE can be a duplicate: an explicit REPLACE or
            // ADD_SESSION carrying the same values is an idempotent retry of a
            // decision the user already made, so it is applied normally.
            //
            // Checked before conflict validation, because the identical
            // schedule overlaps itself and would otherwise be reported as a
            // conflict rather than as a duplicate.
            // ---------------------------------------------------------------
            if ($operation === self::OPERATION_CREATE) {
                foreach ($existingPlans as $existingPlan) {
                    if ($this->planMatchesPayload($existingPlan, $payload)) {
                        return [
                            'plan_id'   => (int) $existingPlan['id'],
                            'conflicts' => [],
                            'duplicate' => true,
                        ];
                    }
                }
            }

            // Conflict validation runs once, on the plan this request would
            // actually write. The plan being replaced is excluded explicitly;
            // every unrelated active schedule is still evaluated.
            $replacedPlanId = $operation === self::OPERATION_REPLACE
                ? $this->resolvePlanChoice($userId, $activityId, $input, $existingPlans)['id']
                : 0;

            $conflicts = $isEnabled === 1 && $this->isConflictEligible($candidate)
                ? $this->validateCandidate($userId, $candidate, (int) $replacedPlanId)
                : [];

            // ---------------------------------------------------------------
            // CASE A: nothing scheduled yet for this activity.
            // ---------------------------------------------------------------
            if ($existingPlans === []) {
                if ($operation === self::OPERATION_ADD_SESSION) {
                    throw new ValidationException(
                        'Aktivitas ini belum memiliki jadwal, jadi belum ada sesi yang dapat ditambah.',
                    );
                }

                $payload['session_ordinal'] = 0;
                $payload['created_at']      = $now;

                try {
                    $this->db->table('user_activity_plans')->insert($payload);

                    return ['plan_id' => (int) $this->db->insertID(), 'conflicts' => $conflicts];
                } catch (Throwable $exception) {
                    if ($this->isDuplicateKey($exception)) {
                        // Lost a race with an identical insert: report it as a
                        // duplicate rather than surfacing a database error.
                        $current = $this->plansForActivity($userId, $activityId);

                        return [
                            'plan_id'   => (int) ($current[0]['id'] ?? 0),
                            'conflicts' => [],
                            'duplicate' => true,
                        ];
                    }

                    throw $exception;
                }
            }

            $target = $this->choiceTarget($existingPlans);

            // ---------------------------------------------------------------
            // CASE C without an explicit choice: never overwrite, never insert.
            // ---------------------------------------------------------------
            if ($operation === self::OPERATION_CREATE) {
                return [
                    'plan_id'      => 0,
                    'conflicts'    => [],
                    'requires_confirmation' => true,
                    'existing'     => $this->describePlanChoice($target),
                    'existing_plans' => array_map(fn (array $plan): array => $this->describePlanChoice($plan), $existingPlans),
                    'proposed'     => $this->describePlanChoice($candidate),
                    'operations'   => $this->availableOperations($activity, $existingPlans),
                    'add_session_disabled_reason' => $this->additionalSessionDisabledReason($activity, $existingPlans),
                ];
            }

            // ---------------------------------------------------------------
            // CASE E: confirmed replacement.
            // ---------------------------------------------------------------
            if ($operation === self::OPERATION_REPLACE) {
                $target = $this->resolvePlanChoice($userId, $activityId, $input, $existingPlans);

                $expectedUpdatedAt = trim((string) ($input['confirm_plan_updated_at'] ?? ''));
                if ($expectedUpdatedAt === '' || (string) ($target['updated_at'] ?? '') !== $expectedUpdatedAt) {
                    throw new ValidationException('Jadwal yang dipilih sudah berubah di tab lain. Muat ulang halaman dan pilih jadwal pengganti lagi.');
                }

                if ($this->planMatchesPayload($target, $payload)) {
                    throw new ValidationException('Jadwal yang diajukan sama dengan jadwal yang dipilih dan tidak mengubah apa pun.');
                }

                // Ordering note: assertReplaceAllowed() is intentionally not
                // called here. validateCandidate() above already compared the
                // candidate against every other active plan while excluding
                // only the row being replaced, so re-running the same check
                // would add a second query without adding coverage.
                unset($payload['user_id'], $payload['activity_id'], $payload['session_ordinal']);

                $this->db->table('user_activity_plans')
                    ->where('id', (int) $replacedPlanId)
                    ->where('user_id', $userId)
                    ->update($payload + ['updated_at' => $now]);

                return [
                    'plan_id'       => (int) $replacedPlanId,
                    'conflicts'     => $conflicts,
                    'replaced'      => true,
                    'replaced_plan' => $this->describePlanChoice($target),
                ];
            }

            // ---------------------------------------------------------------
            // CASE F: confirmed additional session.
            // ---------------------------------------------------------------
            $this->assertSessionAllowed($activity, $existingPlans, $candidate, $isEnabled);

            $payload['session_ordinal'] = $this->nextSessionOrdinal($existingPlans);
            $payload['created_at']      = $now;

            $this->db->table('user_activity_plans')->insert($payload);

            return [
                'plan_id'       => (int) $this->db->insertID(),
                'conflicts'     => $conflicts,
                'added_session' => true,
            ];
        });
    }

    /**
     * Plans belonging to one activity for one user.
     *
     * @return list<array<string, mixed>>
     */
    private function plansForActivity(int $userId, int $activityId): array
    {
        // The activity name is selected so the replaced/duplicate plan can be
        // described in audit output without a second query.
        return $this->db->table('user_activity_plans p')
            ->select('p.*, a.name AS activity_name')
            ->join('activities a', 'a.id = p.activity_id')
            ->where('p.user_id', $userId)
            ->where('p.activity_id', $activityId)
            ->orderBy('p.session_ordinal', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * True when a stored plan already holds exactly the proposed schedule, so
     * the submission is a duplicate rather than a change.
     *
     * `is_enabled` is part of the comparison: asking to re-enable an existing
     * schedule is a change, not a duplicate.
     *
     * @param array<string, mixed> $existingPlan
     * @param array<string, mixed> $payload
     */
    private function planMatchesPayload(array $existingPlan, array $payload): bool
    {
        return ScheduleRules::normalizeRecurrence($existingPlan['recurrence_rule'] ?? null)
                === ScheduleRules::normalizeRecurrence($payload['recurrence_rule'] ?? null)
            && (string) ($existingPlan['timezone'] ?? '') === (string) $payload['timezone']
            && ScheduleRules::normalizeTime($existingPlan['preferred_start_time'] ?? null) === $payload['preferred_start_time']
            && ScheduleRules::normalizeTime($existingPlan['preferred_end_time'] ?? null) === $payload['preferred_end_time']
            && (int) ($existingPlan['is_enabled'] ?? 0) === (int) $payload['is_enabled'];
    }

    /**
     * Which existing plan a replacement should target when the user confirmed
     * without naming one.
     *
     * Only used to seed the confirmation dialog, so a deterministic choice is
     * enough. A confirmed replacement must name the plan explicitly; see
     * resolvePlanChoice().
     *
     * @param list<array<string, mixed>> $existingPlans
     *
     * @return array<string, mixed>
     */
    private function choiceTarget(array $existingPlans): array
    {
        foreach ($existingPlans as $plan) {
            if ((int) ($plan['is_enabled'] ?? 0) === 1) {
                return $plan;
            }
        }

        return $existingPlans[0];
    }

    /**
     * Resolve which plan a confirmed replacement applies to.
     *
     * The submitted id must belong to this user's activity. A missing id is
     * only accepted when there is exactly one candidate, so an ambiguous
     * replacement can never silently overwrite the wrong session.
     *
     * @param array<string, mixed>       $input
     * @param list<array<string, mixed>> $existingPlans
     *
     * @return array<string, mixed>
     */
    private function resolvePlanChoice(int $userId, int $activityId, array $input, array $existingPlans): array
    {
        $targetId = (int) ($input['confirm_plan_id'] ?? 0);

        if ($targetId <= 0) {
            if (count($existingPlans) !== 1) {
                throw new ValidationException('Pilih jadwal mana yang ingin diganti.');
            }

            return $existingPlans[0];
        }

        foreach ($existingPlans as $plan) {
            if ((int) $plan['id'] === $targetId) {
                return $plan;
            }
        }

        // Either the id belongs to another user, another activity, or no longer
        // exists (stale dialog from another tab). Never fall back to a fresh
        // guess: that is exactly the silent overwrite this fix removes.
        throw new ValidationException(
            'Jadwal yang ingin diganti tidak ditemukan atau sudah berubah di tab lain. Muat ulang halaman dan coba lagi.',
        );
    }

    /**
     * Operations the user may choose for this activity.
     *
     * @param array<string, mixed>       $activity
     * @param list<array<string, mixed>> $existingPlans
     *
     * @return list<string>
     */
    private function availableOperations(array $activity, array $existingPlans): array
    {
        $operations = [self::OPERATION_REPLACE];

        if ($this->supportsAdditionalSessions($activity, $existingPlans)) {
            $operations[] = self::OPERATION_ADD_SESSION;
        }

        return $operations;
    }

    /**
     * Whether an activity's recurrence and completion policy allow another
     * scheduled session (PRD CASE F).
     *
     * - FIXED_SESSIONS: the activity is defined by its sessions; a plan may
     *   cover the sessions it does not already cover, up to the number of
     *   active sessions. The checklist already generates one instance per
     *   session, so this only aligns the plan rows with that behaviour.
     * - REPEATABLE: only when the activity is configured for more than one
     *   completion per day.
     * - ONCE_DAILY and WEEKLY: never. Their configured limits describe a single
     *   scheduled occurrence, and allowing more would contradict them.
     *
     * @param array<string, mixed>       $activity
     * @param list<array<string, mixed>> $existingPlans
     */
    private function supportsAdditionalSessions(array $activity, array $existingPlans): bool
    {
        $repeatType = strtoupper((string) ($activity['repeat_type'] ?? ''));

        if ($repeatType === 'FIXED_SESSIONS') {
            // Already carries its own per-session occurrences.
            if (count($existingPlans) >= 1) {
                return false;
            }

            $sessions = $this->activityService->sessionsForActivity((int) ($activity['id'] ?? 0), true);

            return count($sessions) > 0;
        }

        if ($repeatType === 'REPEATABLE') {
            $maxDaily = max(1, (int) ($activity['maximum_daily_completions'] ?? 1));

            return $maxDaily > 1 && count($existingPlans) < $maxDaily;
        }

        return false;
    }

    /**
     * Explain why the modal cannot add another schedule for this activity.
     * This is presentation only; add attempts remain validated by the service.
     *
     * @param array<string, mixed>       $activity
     * @param list<array<string, mixed>> $existingPlans
     */
    private function additionalSessionDisabledReason(array $activity, array $existingPlans): string
    {
        $repeatType = strtoupper((string) ($activity['repeat_type'] ?? ''));
        $name = (string) ($activity['name'] ?? 'Aktivitas ini');

        if ($repeatType === 'ONCE_DAILY'
            || ($repeatType === 'REPEATABLE' && (int) ($activity['maximum_daily_completions'] ?? 1) <= 1)) {
            return sprintf('Batas harian "%s" adalah satu kali, jadi jadwal tambahan tidak diizinkan.', $name);
        }

        if ($repeatType === 'WEEKLY') {
            return 'Aktivitas dengan kebijakan WEEKLY tidak mendukung jadwal tambahan.';
        }

        if ($repeatType === 'FIXED_SESSIONS') {
            return 'Sesi aktivitas ini ditentukan oleh sesi tetap; jadwal tambahan tidak tersedia.';
        }

        if ($repeatType === 'REPEATABLE') {
            $maxDaily = max(1, (int) ($activity['maximum_daily_completions'] ?? 1));

            if (count($existingPlans) >= $maxDaily) {
                return sprintf('Batas harian "%s" sudah mencapai %d jadwal.', $name, $maxDaily);
            }
        }

        return 'Kebijakan aktivitas ini tidak mengizinkan jadwal tambahan.';
    }

    /**
     * @param array<string, mixed>       $activity
     * @param list<array<string, mixed>> $existingPlans
     * @param array<string, mixed>       $candidate
     */
    private function assertSessionAllowed(array $activity, array $existingPlans, array $candidate, int $isEnabled): void
    {
        $name = (string) ($activity['name'] ?? 'Aktivitas ini');

        if ((int) ($activity['maximum_daily_completions'] ?? 1) <= 1
            && strtoupper((string) ($activity['repeat_type'] ?? '')) !== 'FIXED_SESSIONS') {
            throw new ValidationException(
                sprintf('"%s" hanya dapat dijalankan sekali sehari, jadi sesi tambahan tidak didukung.', $name),
            );
        }

        $repeatType = strtoupper((string) ($activity['repeat_type'] ?? ''));

        if ($repeatType === 'FIXED_SESSIONS') {
            if ($existingPlans !== []) {
                throw new ValidationException(
                    sprintf('"%s" sudah memiliki sesi terjadwal. Ubah jadwal yang ada daripada menambah sesi baru.', $name),
                );
            }

            $sessions = $this->activityService->sessionsForActivity((int) ($activity['id'] ?? 0), true);

            if ($sessions === []) {
                throw new ValidationException('Aktivitas ini tidak memiliki sesi terjadwal.');
            }
        } elseif ($repeatType === 'REPEATABLE') {
            $maxDaily = max(1, (int) ($activity['maximum_daily_completions'] ?? 1));

            if ($maxDaily <= 1) {
                throw new ValidationException(
                    sprintf('Batas harian "%s" adalah satu kali, jadi sesi tambahan tidak didukung.', $name),
                );
            }

            if (count($existingPlans) >= $maxDaily) {
                throw new ValidationException(
                    sprintf('Sesi untuk "%s" sudah mencapai batas harian (%d sesi).', $name, $maxDaily),
                );
            }
        } else {
            throw new ValidationException(
                sprintf('Aktivitas "%s" tidak mendukung lebih dari satu sesi terjadwal.', $name),
            );
        }

        // A second occurrence must not sit on top of the first, and the
        // recurrence must actually be capable of producing two occurrences.
        $this->assertSessionDoesNotDuplicate($existingPlans, $candidate);

        if ($isEnabled === 1 && $this->isConflictEligible($candidate)) {
            $this->validateCandidate((int) $candidate['user_id'], $candidate);
        }
    }

    /**
     * Reject a second session that repeats an existing one, or that reuses a
     * schedule incapable of producing a distinct occurrence.
     *
     * @param list<array<string, mixed>> $existingPlans
     * @param array<string, mixed>       $candidate
     */
    private function assertSessionDoesNotDuplicate(array $existingPlans, array $candidate): void
    {
        foreach ($existingPlans as $existingPlan) {
            $sameSchedule = ScheduleRules::normalizeRecurrence($existingPlan['recurrence_rule'] ?? null)
                    === ScheduleRules::normalizeRecurrence($candidate['recurrence_rule'] ?? null)
                && ScheduleRules::normalizeTime($existingPlan['preferred_start_time'] ?? null) === $candidate['preferred_start_time']
                && ScheduleRules::normalizeTime($existingPlan['preferred_end_time'] ?? null) === $candidate['preferred_end_time'];

            if ($sameSchedule) {
                throw new ValidationException(
                    'Sesi ini sama persis dengan jadwal yang sudah ada, jadi tidak ada sesi baru yang perlu ditambahkan.',
                );
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $existingPlans
     */
    private function nextSessionOrdinal(array $existingPlans): int
    {
        $highest = -1;

        foreach ($existingPlans as $plan) {
            $highest = max($highest, (int) ($plan['session_ordinal'] ?? 0));
        }

        return $highest + 1;
    }

    /**
     * Validate a confirmed replacement against every other active plan.
     *
     * The candidate is built without the historical `plan_id`, because
     * ScheduleConflictResolver::evaluatePair() treats a matching non-zero
     * plan_id as "the same plan" and would skip the comparison. Excluding the
     * replaced plan's own row explicitly (rather than by plan_id) guarantees
     * that unrelated active schedules are never ignored.
     *
     * @param array<string, mixed> $candidate
     */
    private function assertReplaceAllowed(int $userId, array $candidate, int $targetPlanId, int $isEnabled): void
    {
        if ($isEnabled !== 1 || ! $this->isConflictEligible($candidate)) {
            return;
        }

        $others = $this->db->table('user_activity_plans p')
            ->select('p.*, a.name AS activity_name, a.schedule_policy, c.id AS category_id, vp.policy_code')
            ->join('activities a', 'a.id = p.activity_id')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('verification_policies vp', 'vp.id = a.verification_policy_id', 'left')
            ->where('p.user_id', $userId)
            ->where('p.is_enabled', 1)
            ->where('a.is_active', 1)
            ->get()
            ->getResultArray();

        $normalized = [];

        foreach ($others as $plan) {
            if ((int) $plan['id'] === $targetPlanId) {
                continue;
            }

            $entry = $this->normalizeConflictPlan($plan + [
                'activity_sessions' => $this->activityService->sessionsForActivity((int) $plan['activity_id']),
            ]);

            if ($this->isConflictEligible($entry)) {
                $normalized[] = $entry;
            }
        }

        $candidate['plan_id'] = 0;

        $evaluation = ScheduleConflictResolver::evaluate($candidate, $normalized);

        ScheduleConflictResolver::assertNoHardConflict($candidate, $evaluation);
    }

    /**
     * @param array<string, mixed> $plan
     *
     * @return array<string, mixed>
     */
    private function describePlanChoice(array $plan): array
    {
        $recurrence = ScheduleRules::normalizeRecurrence($plan['recurrence_rule'] ?? null);

        return [
            'plan_id'              => (int) ($plan['plan_id'] ?? $plan['id'] ?? 0),
            'activity_id'          => (int) ($plan['activity_id'] ?? 0),
            'activity_name'        => (string) ($plan['activity_name'] ?? ''),
            'recurrence_rule'      => $recurrence,
            'recurrence_label'     => self::recurrenceLabel($recurrence),
            'timezone'             => (string) ($plan['timezone'] ?? ScheduleRules::DEFAULT_TIMEZONE),
            'preferred_start_time' => ScheduleRules::normalizeTime($plan['preferred_start_time'] ?? null),
            'preferred_end_time'   => ScheduleRules::normalizeTime($plan['preferred_end_time'] ?? null),
            'is_enabled'           => (int) ($plan['is_enabled'] ?? 0),
            'updated_at'           => (string) ($plan['updated_at'] ?? ''),
        ];
    }

    public function setEnabled(int $userId, int $planId, bool $enabled): void
    {
        $plan = $this->findPlan($userId, $planId);

        $this->withUserScheduleLock($userId, (int) $plan['activity_id'], function () use ($userId, $planId, $enabled, $plan): void {
            if ($enabled) {
                // array_replace, not `+`: the plan row already carries the
                // stored is_enabled (0), which `+` would keep, making the
                // candidate look ineligible and skipping validation.
                $candidate = $this->normalizeConflictPlan(array_replace($plan, [
                    'is_enabled'        => 1,
                    'activity_sessions' => $this->activityService->sessionsForActivity((int) $plan['activity_id']),
                ]));

                if ($this->isConflictEligible($candidate)) {
                    $this->validateCandidate($userId, $candidate);
                }
            }

            $this->db->table('user_activity_plans')
                ->where('id', $planId)
                ->where('user_id', $userId)
                ->update([
                    'is_enabled' => $enabled ? 1 : 0,
                    'updated_at' => $this->nowUtc(),
                ]);
        });
    }

    public function disableAllForActivity(int $userId, int $activityId): void
    {
        $this->withUserScheduleLock($userId, $activityId, function () use ($userId, $activityId): void {
            $this->db->table('user_activity_plans')
                ->where('user_id', $userId)
                ->where('activity_id', $activityId)
                ->update([
                    'is_enabled' => 0,
                    'updated_at' => $this->nowUtc(),
                ]);
        });
    }

    /**
     * Validate a candidate plan against the user's other enabled plans.
     *
     * @param array<string, mixed> $candidate
     * @param int                  $excludePlanId Row id of the plan being replaced,
     *                                            whose own row must never be
     *                                            compared against itself.
     *
     * @return list<array<string, mixed>> Soft conflicts (hard conflicts throw)
     */
    private function validateCandidate(int $userId, array $candidate, int $excludePlanId = 0): array
    {
        $others = $this->db->table('user_activity_plans p')
            ->select('p.*, a.name AS activity_name, a.schedule_policy, c.id AS category_id, vp.policy_code')
            ->join('activities a', 'a.id = p.activity_id')
            ->join('activity_categories c', 'c.id = a.category_id', 'left')
            ->join('verification_policies vp', 'vp.id = a.verification_policy_id', 'left')
            ->where('p.user_id', $userId)
            ->where('p.is_enabled', 1)
            ->where('a.is_active', 1)
            ->get()
            ->getResultArray();

        // Exclude the row being replaced by its explicit id. The candidate is
        // built with plan_id = 0 on purpose, because
        // ScheduleConflictResolver::evaluatePair() treats a matching non-zero
        // plan_id as "the same plan" and would skip the comparison. Every other
        // active schedule is still compared.
        $excludeIds = array_filter([$excludePlanId, (int) ($candidate['plan_id'] ?? 0)]);

        $normalized = [];

        foreach ($others as $plan) {
            if (in_array((int) $plan['id'], $excludeIds, true)) {
                continue;
            }

            $entry = $this->normalizeConflictPlan($plan + [
                'activity_sessions' => $this->activityService->sessionsForActivity((int) $plan['activity_id']),
            ]);

            if ($this->isConflictEligible($entry)) {
                $normalized[] = $entry;
            }
        }

        $evaluation = ScheduleConflictResolver::evaluate($candidate, $normalized);

        $soft = ScheduleConflictResolver::assertNoHardConflict($candidate, $evaluation);

        return array_values(array_filter(
            $soft,
            static fn (array $conflict): bool => $conflict['classification'] === ScheduleConflictResolver::SOFT_CONFLICT,
        ));
    }

    /**
     * Serialize all schedule writes for one user, then run the callback in a
     * single transaction so validation and write are atomic.
     *
     * Row locks are taken on immutable shared reference rows (the user row and
     * the activity row), not on the plan rows themselves, because locking a
     * missing plan row would require gap locks. The plan rows are re-read
     * inside the transaction after the locks are held.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    private function withUserScheduleLock(int $userId, ?int $activityId, callable $callback): mixed
    {
        $this->db->transBegin();

        try {
            $this->acquireLock('users', $userId);

            if ($activityId !== null) {
                $this->acquireLock('activities', $activityId);
            }

            $result = $callback();

            $this->db->transCommit();

            return $result;
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    private function acquireLock(string $table, int $id): void
    {
        $this->db->query(
            sprintf('SELECT `id` FROM `%s` WHERE `id` = ? FOR UPDATE', $table),
            [$id],
        );
    }

    private function assertTimeOrder(?string $startTime, ?string $endTime): void
    {
        if ($startTime === null || $endTime === null) {
            return;
        }

        if (ScheduleRules::timeToMinutes($endTime) <= ScheduleRules::timeToMinutes($startTime)) {
            throw new ValidationException(
                'Jam selesai harus lebih lambat daripada jam mulai. Jadwal yang melewati tengah malam belum didukung.',
            );
        }
    }

    /**
     * @param array<string, mixed> $plan
     *
     * @return array<string, mixed>
     */
    private function normalizeConflictPlan(array $plan): array
    {
        $sessions = $plan['activity_sessions'] ?? [];

        return [
            'plan_id'              => (int) ($plan['plan_id'] ?? $plan['id'] ?? 0),
            'user_id'              => (int) ($plan['user_id'] ?? 0),
            'activity_id'          => (int) ($plan['activity_id'] ?? 0),
            'activity_name'        => (string) ($plan['activity_name'] ?? ''),
            'activity_active'      => (int) ($plan['activity_active'] ?? 1),
            'is_enabled'           => (int) ($plan['is_enabled'] ?? 0),
            'recurrence_rule'      => $plan['recurrence_rule'] ?? null,
            'timezone'             => (string) ($plan['timezone'] ?? ScheduleRules::DEFAULT_TIMEZONE),
            'preferred_start_time' => $plan['preferred_start_time'] ?? null,
            'preferred_end_time'   => $plan['preferred_end_time'] ?? null,
            'schedule_policy'      => $plan['schedule_policy'] ?? ScheduleConflictResolver::POLICY_EXCLUSIVE,
            'category_id'          => $plan['category_id'] ?? null,
            'policy_code'          => $plan['policy_code'] ?? 'SELF_CHECK',
            'session_start'        => $this->sessionBound($sessions, 'scheduled_start', true),
            'session_end'          => $this->sessionBound($sessions, 'scheduled_end', false),
        ];
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function isConflictEligible(array $plan): bool
    {
        return (int) ($plan['is_enabled'] ?? 0) === 1
            && (int) ($plan['activity_active'] ?? 1) === 1;
    }

    /**
     * @param list<array<string, mixed>> $sessions
     */
    private function sessionBound(array $sessions, string $column, bool $minimum): ?int
    {
        $values = [];

        foreach ($sessions as $session) {
            $minutes = ScheduleRules::timeToMinutes($session[$column] ?? null);

            if ($minutes !== null) {
                $values[] = $minutes;
            }
        }

        if ($values === []) {
            return null;
        }

        return $minimum ? min($values) : max($values);
    }

    public static function recurrenceLabel(array|string|null $rule): string
    {
        $rule = ScheduleRules::normalizeRecurrence($rule);

        return match ($rule['type']) {
            'WEEKDAYS'    => 'Senin sampai Jumat',
            'WEEKLY'      => 'Setiap ' . self::dayName((int) ($rule['weekday'] ?? 1)),
            'CUSTOM_DAYS' => 'Hari ' . implode(', ', array_map(
                static fn (int $day): string => self::dayName($day),
                $rule['days'] ?? [],
            )),
            default       => 'Setiap hari',
        };
    }

    public static function dayName(int $day): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$day] ?? 'Senin';
    }
}
