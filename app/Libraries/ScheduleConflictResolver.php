<?php

namespace App\Libraries;

use App\Exceptions\ScheduleConflictException;
use App\Exceptions\ValidationException;

/**
 * Pure schedule conflict detection for Phase 2 plan planning.
 *
 * Evaluates whether two user plans may occupy the same clock interval on the
 * same local day. All inputs are arrays; timezone conversion is delegated to
 * ScheduleRules. No database access happens here, which keeps the rules
 * unit-testable and deterministic.
 *
 * Conflict classifications:
 * - HARD_CONFLICT: at least one activity is EXCLUSIVE and the intervals overlap.
 * - SOFT_CONFLICT: every activity is PARALLEL_ALLOWED, the intervals overlap,
 *   and at least one documented parallel restriction is violated (see
 *   PARALLEL_RESTRICTIONS). Reported for information only.
 * - NO_CONFLICT: no overlap, or all parallel restrictions are satisfied.
 *
 * Intervals are half-open ([start, end)), so an interval ending at 06:00 is
 * adjacent to one starting at 06:00 and does not conflict.
 */
final class ScheduleConflictResolver
{
    public const HARD_CONFLICT = 'HARD_CONFLICT';
    public const SOFT_CONFLICT = 'SOFT_CONFLICT';
    public const NO_CONFLICT   = 'NO_CONFLICT';

    public const POLICY_EXCLUSIVE        = 'EXCLUSIVE';
    public const POLICY_PARALLEL_ALLOWED = 'PARALLEL_ALLOWED';

    public const COMPATIBILITY_POLICIES = [
        self::POLICY_EXCLUSIVE,
        self::POLICY_PARALLEL_ALLOWED,
    ];

    /**
     * Soft restrictions for PARALLEL_ALLOWED activities.
     *
     * A pair of parallel activities may share a time window only while every
     * restriction below holds:
     * 1. They belong to different categories (two tasks from the same life
     *    domain overlap almost always because one was duplicated).
     * 2. They use the same verification policy family (a timer-based task
     *    cannot be trusted while a self-check task competes for attention).
     * 3. At most one of the two produces evidence (PHOTO_PROOF or
     *    ADMIN_APPROVAL), because one capture session cannot serve two tasks.
     *
     * @var array<string, string>
     */
    public const PARALLEL_RESTRICTIONS = [
        'DIFFERENT_CATEGORY'      => 'Aktivitas paralel harus berasal dari kategori berbeda.',
        'SAME_VERIFICATION_FAMILY' => 'Aktivitas paralel harus memakai metode verifikasi yang sejenis.',
        'SINGLE_EVIDENCE_ACTIVITY' => 'Paling banyak satu aktivitas paralel yang membutuhkan bukti.',
    ];

    /**
     * Category codes considered passive enough to run beside other tasks.
     *
     * @var list<string>
     */
    public const PARALLEL_CATEGORY_CODES = ['LEARNING_EDUCATION', 'SLEEP_RECOVERY'];

    /**
     * @param array<string, mixed> $candidate Normalized candidate plan
     * @param list<array<string, mixed>> $existing Normalized existing plans
     *
     * @return array{classification: string, worst: string, conflicts: list<array<string, mixed>>}
     */
    public static function evaluate(array $candidate, array $existing): array
    {
        $conflicts = [];

        foreach ($existing as $plan) {
            $conflict = self::evaluatePair($candidate, $plan);

            if ($conflict !== null) {
                $conflicts[] = $conflict;
            }
        }

        $worst = self::worstClassification($conflicts);

        return [
            'classification' => $worst,
            'worst'          => $worst,
            'conflicts'      => $conflicts,
        ];
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     *
     * @return array<string, mixed>|null
     */
    public static function evaluatePair(array $left, array $right): ?array
    {
        if ((int) ($left['plan_id'] ?? 0) !== 0
            && (int) ($left['plan_id'] ?? 0) === (int) ($right['plan_id'] ?? 0)) {
            return null;
        }

        if (! self::recurrenceIntersects($left, $right)) {
            return null;
        }

        $leftWindow  = self::windowFor($left);
        $rightWindow = self::windowFor($right);

        if (! self::windowsOverlap($leftWindow, $rightWindow)) {
            return null;
        }

        $leftPolicy  = self::policyFor($left);
        $rightPolicy = self::policyFor($right);
        $reasons     = [];

        if ($leftPolicy === self::POLICY_EXCLUSIVE || $rightPolicy === self::POLICY_EXCLUSIVE) {
            $classification = self::HARD_CONFLICT;
            $reasons[]      = 'Salah satu aktivitas bersifat eksklusif sehingga tidak boleh berjalan bersamaan.';
            $codes          = ['EXCLUSIVE_OVERLAP'];
        } else {
            $reasons        = self::parallelViolations($left, $right);
            $classification = $reasons === [] ? self::NO_CONFLICT : self::SOFT_CONFLICT;
            $codes          = self::parallelReasonCodes($left, $right);
        }

        if ($classification === self::NO_CONFLICT) {
            return null;
        }

        return [
            'classification'            => $classification,
            'reason_codes'              => $codes,
            'reasons'                   => $reasons,
            'overlap_start'             => self::minutesToTime(max($leftWindow['start'], $rightWindow['start'])),
            'overlap_end'               => self::minutesToTime(min($leftWindow['end'], $rightWindow['end'])),
            'left'                      => self::describe($left, $leftWindow),
            'right'                     => self::describe($right, $rightWindow),
        ];
    }

    /**
     * Half-open overlap test on same-day minute windows.
     *
     * ORDERED and SESSION_DEFINED windows describe the same local clock, so
     * they may overlap. UNBOUNDED is the whole day, so it overlaps every
     * other window it shares a day with.
     *
     * @param array{basis: string, start: int, end: int} $left
     * @param array{basis: string, start: int, end: int} $right
     */
    public static function windowsOverlap(array $left, array $right): bool
    {
        return $left['start'] < $right['end'] && $right['start'] < $left['end'];
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    public static function recurrenceIntersects(array $left, array $right): bool
    {
        $leftDays  = self::activeWeekdays($left);
        $rightDays = self::activeWeekdays($right);

        if ($leftDays === [] || $rightDays === []) {
            return false;
        }

        return array_intersect($leftDays, $rightDays) !== [];
    }

    /**
     * @param array<string, mixed> $plan
     *
     * @return list<int>
     */
    public static function activeWeekdays(array $plan): array
    {
        $rule = ScheduleRules::normalizeRecurrence($plan['recurrence_rule'] ?? null);

        return match ($rule['type']) {
            'DAILY'      => [1, 2, 3, 4, 5, 6, 7],
            'WEEKDAYS'   => [1, 2, 3, 4, 5],
            'WEEKLY'     => [(int) ($rule['weekday'] ?? 1)],
            'CUSTOM_DAYS' => array_map('intval', $rule['days'] ?? [1]),
            default      => [1, 2, 3, 4, 5, 6, 7],
        };
    }

    /**
     * Resolve the effective interval for a plan.
     *
     * Basis ORDERED (explicit plan times): the interval is preferred_start_time
     * exclusive to preferred_end_time. Missing endpoints fall back to the
     * documented defaults below.
     *
     * Basis SESSION_DEFINED (no explicit plan times, activity has fixed
     * sessions): the interval is the earliest session start to the latest
     * session end for that activity.
     *
     * Basis UNBOUNDED (no explicit times and no sessions): the interval is the
     * whole local day (00:00-24:00), so the plan conflicts with every other
     * exclusive plan it shares a day with.
     *
     * @param array<string, mixed> $plan
     *
     * @return array{basis: string, start: int, end: int}
     */
    public static function windowFor(array $plan): array
    {
        $start = ScheduleRules::timeToMinutes($plan['preferred_start_time'] ?? null);
        $end   = ScheduleRules::timeToMinutes($plan['preferred_end_time'] ?? null);

        if ($start !== null || $end !== null) {
            return [
                'basis' => 'ORDERED',
                'start' => $start ?? self::DEFAULT_UNSPECIFIED_START,
                'end'   => $end ?? self::DEFAULT_UNSPECIFIED_END,
            ];
        }

        $sessionStart = $plan['session_start'] ?? null;
        $sessionEnd   = $plan['session_end'] ?? null;

        if ($sessionStart !== null && $sessionEnd !== null) {
            return [
                'basis' => 'SESSION_DEFINED',
                'start' => (int) $sessionStart,
                'end'   => (int) $sessionEnd,
            ];
        }

        return [
            'basis' => 'UNBOUNDED',
            'start' => 0,
            'end'   => 1440,
        ];
    }

    /**
     * Documented fallbacks when only one endpoint is provided.
     */
    public const DEFAULT_UNSPECIFIED_START = 00 * 60;
    public const DEFAULT_UNSPECIFIED_END   = 24 * 60;

    /**
     * @param array<string, mixed> $plan
     */
    public static function policyFor(array $plan): string
    {
        $policy = strtoupper((string) ($plan['schedule_policy'] ?? self::POLICY_EXCLUSIVE));

        return in_array($policy, self::COMPATIBILITY_POLICIES, true)
            ? $policy
            : self::POLICY_EXCLUSIVE;
    }

    /**
     * @return list<string>
     */
    private static function parallelViolations(array $left, array $right): array
    {
        $violations = [];

        if ((int) ($left['category_id'] ?? 0) === (int) ($right['category_id'] ?? 0)) {
            $violations[] = self::PARALLEL_RESTRICTIONS['DIFFERENT_CATEGORY'];
        }

        if (self::verificationFamily($left) !== self::verificationFamily($right)) {
            $violations[] = self::PARALLEL_RESTRICTIONS['SAME_VERIFICATION_FAMILY'];
        }

        if (self::requiresEvidence($left) && self::requiresEvidence($right)) {
            $violations[] = self::PARALLEL_RESTRICTIONS['SINGLE_EVIDENCE_ACTIVITY'];
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private static function parallelReasonCodes(array $left, array $right): array
    {
        $codes = [];

        if ((int) ($left['category_id'] ?? 0) === (int) ($right['category_id'] ?? 0)) {
            $codes[] = 'DIFFERENT_CATEGORY';
        }

        if (self::verificationFamily($left) !== self::verificationFamily($right)) {
            $codes[] = 'SAME_VERIFICATION_FAMILY';
        }

        if (self::requiresEvidence($left) && self::requiresEvidence($right)) {
            $codes[] = 'SINGLE_EVIDENCE_ACTIVITY';
        }

        return $codes;
    }

    /**
     * @param array<string, mixed> $plan
     */
    private static function verificationFamily(array $plan): string
    {
        return match (strtoupper((string) ($plan['policy_code'] ?? 'SELF_CHECK'))) {
            'TIMER' => 'TIMER',
            default => 'ATTESTATION',
        };
    }

    /**
     * @param array<string, mixed> $plan
     */
    private static function requiresEvidence(array $plan): bool
    {
        return in_array(strtoupper((string) ($plan['policy_code'] ?? '')), ['PHOTO_PROOF', 'ADMIN_APPROVAL'], true);
    }

    /**
     * @param array<string, mixed> $plan
     * @param array{basis: string, start: int, end: int} $window
     *
     * @return array<string, mixed>
     */
    private static function describe(array $plan, array $window): array
    {
        return [
            'plan_id'    => (int) ($plan['plan_id'] ?? 0),
            'activity_id' => (int) ($plan['activity_id'] ?? 0),
            'activity_name' => (string) ($plan['activity_name'] ?? ''),
            'schedule_policy' => self::policyFor($plan),
            'window_basis' => $window['basis'],
            'window_start' => self::minutesToTime($window['start']),
            'window_end'   => self::minutesToTime($window['end']),
        ];
    }

    /**
     * @param list<array<string, mixed>> $conflicts
     */
    private static function worstClassification(array $conflicts): string
    {
        $worst = self::NO_CONFLICT;

        foreach ($conflicts as $conflict) {
            if ($conflict['classification'] === self::HARD_CONFLICT) {
                return self::HARD_CONFLICT;
            }

            $worst = self::SOFT_CONFLICT;
        }

        return $worst;
    }

    public static function minutesToTime(int $minutes): string
    {
        $minutes = max(0, min(1439, $minutes));

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Build the user-facing Indonesian explanation for hard conflicts.
     *
     * @param array<string, mixed> $candidate
     * @param list<array<string, mixed>> $conflicts
     */
    public static function hardConflictMessage(array $candidate, array $conflicts): string
    {
        $candidateName = (string) ($candidate['activity_name'] ?? 'Aktivitas');
        $lines         = [];

        foreach ($conflicts as $conflict) {
            if ($conflict['classification'] !== self::HARD_CONFLICT) {
                continue;
            }

            $other = $conflict['right'];

            $lines[] = sprintf(
                '"%s" (%s-%s) bertabrakan dengan "%s" (%s-%s) pada %s.',
                $candidateName,
                $conflict['left']['window_start'],
                $conflict['left']['window_end'],
                (string) $other['activity_name'],
                $other['window_start'],
                $other['window_end'],
                self::recurrenceLabel($other),
            );
        }

        $recurrence = ScheduleRules::normalizeRecurrence($candidate['recurrence_rule'] ?? null);
        $dayHint    = $recurrence['type'] === 'DAILY' ? ' setiap hari' : ' pada hari yang sama';

        return 'Jadwal tidak dapat disimpan karena bertabrakan' . $dayHint . '. '
            . implode(' ', $lines)
            . ' Sesuaikan jamnya, ubah pola pengulangan, nonaktifkan salah satu jadwal, atau minta admin menandai aktivitas yang memang boleh berjalan paralel.';
    }

    /**
     * @param array<string, mixed> $plan
     */
    private static function recurrenceLabel(array $plan): string
    {
        $recurrence = ScheduleRules::normalizeRecurrence($plan['recurrence_rule'] ?? null);
        $dayNames   = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

        return match ($recurrence['type']) {
            'WEEKDAYS'  => 'Senin-Jumat',
            'WEEKLY'    => (string) ($dayNames[(int) ($recurrence['weekday'] ?? 1)] ?? 'Senin'),
            'CUSTOM_DAYS' => implode(', ', array_map(
                static fn (int $day): string => (string) ($dayNames[$day] ?? 'Senin'),
                $recurrence['days'] ?? [],
            )),
            default     => 'setiap hari',
        };
    }

    /**
     * Validate a compatibility policy value supplied by an administrator.
     */
    public static function assertPolicy(?string $policy): string
    {
        $policy = strtoupper(trim((string) $policy));

        if (! in_array($policy, self::COMPATIBILITY_POLICIES, true)) {
            throw new ValidationException('Kebijakan kompatibilitas jadwal tidak valid.');
        }

        return $policy;
    }

    /**
     * Throw when evaluation reports a hard conflict.
     *
     * @param array<string, mixed> $candidate
     * @param array<string, mixed> $evaluation
     *
     * @return list<array<string, mixed>>
     */
    public static function assertNoHardConflict(array $candidate, array $evaluation): array
    {
        $hard = array_values(array_filter(
            $evaluation['conflicts'] ?? [],
            static fn (array $conflict): bool => $conflict['classification'] === self::HARD_CONFLICT,
        ));

        if ($hard !== []) {
            throw new ScheduleConflictException(
                self::hardConflictMessage($candidate, $hard),
                $hard,
            );
        }

        return $evaluation['conflicts'] ?? [];
    }
}
