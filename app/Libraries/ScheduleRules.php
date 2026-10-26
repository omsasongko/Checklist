<?php

namespace App\Libraries;

use App\Exceptions\ValidationException;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Pure, testable scheduling rules used by the activity and checklist
 * services. User-facing messages are Indonesian; identifiers are English.
 */
final class ScheduleRules
{
    public const REPEAT_TYPES = [
        'ONCE_DAILY',
        'FIXED_SESSIONS',
        'REPEATABLE',
        'WEEKLY',
    ];

    public const CUSTOM_REPEAT_TYPES = [
        'ONCE_DAILY',
        'REPEATABLE',
        'WEEKLY',
    ];

    public const RECURRENCE_TYPES = [
        'DAILY',
        'WEEKDAYS',
        'WEEKLY',
        'CUSTOM_DAYS',
    ];

    public const ACTIVITY_TYPES = [
        'STANDARD',
        'ALTERNATIVE',
        'CUSTOM',
    ];

    public const DEFAULT_TIMEZONE = 'Asia/Jakarta';
    public const COMPETITION_TIMEZONE = 'Asia/Jakarta';

    /**
     * @return array{type: string, weekday?: int, days?: list<int>}
     */
    public static function normalizeRecurrence(array|string|null $rule): array
    {
        if (is_string($rule)) {
            $decoded = json_decode($rule, true);
            $rule    = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($rule)) {
            return ['type' => 'DAILY'];
        }

        $type = strtoupper((string) ($rule['type'] ?? 'DAILY'));

        if (! in_array($type, self::RECURRENCE_TYPES, true)) {
            $type = 'DAILY';
        }

        if ($type === 'WEEKLY') {
            $weekday = (int) ($rule['weekday'] ?? 1);

            return [
                'type'    => 'WEEKLY',
                'weekday' => $weekday >= 1 && $weekday <= 7 ? $weekday : 1,
            ];
        }

        if ($type === 'CUSTOM_DAYS') {
            $days = array_values(array_unique(array_filter(
                array_map('intval', (array) ($rule['days'] ?? [])),
                static fn (int $day): bool => $day >= 1 && $day <= 7,
            )));
            sort($days);

            return [
                'type' => 'CUSTOM_DAYS',
                'days' => $days === [] ? [1] : $days,
            ];
        }

        return ['type' => $type];
    }

    /**
     * Build and validate a recurrence rule from user input.
     *
     * @param list<int> $days
     *
     * @return array{type: string, weekday?: int, days?: list<int>}
     */
    public static function buildRecurrence(string $type, array $days = [], ?int $weekday = null): array
    {
        $type = strtoupper(trim($type));

        if (! in_array($type, self::RECURRENCE_TYPES, true)) {
            throw new ValidationException('Pola pengulangan tidak valid.');
        }

        if ($type === 'WEEKLY') {
            $weekday = $weekday === null ? 1 : $weekday;

            if ($weekday < 1 || $weekday > 7) {
                throw new ValidationException('Hari mingguan tidak valid.');
            }

            return ['type' => 'WEEKLY', 'weekday' => $weekday];
        }

        if ($type === 'CUSTOM_DAYS') {
            $days = array_values(array_unique(array_filter(
                array_map('intval', $days),
                static fn (int $day): bool => $day >= 1 && $day <= 7,
            )));
            sort($days);

            if ($days === []) {
                throw new ValidationException('Pilih minimal satu hari aktivitas.');
            }

            return ['type' => 'CUSTOM_DAYS', 'days' => $days];
        }

        return ['type' => $type];
    }

    public static function isScheduledOn(array $recurrence, string $date): bool
    {
        $date = self::assertDate($date);
        $rule = self::normalizeRecurrence($recurrence);

        if ($rule['type'] === 'DAILY') {
            return true;
        }

        $weekday = (int) (new DateTimeImmutable($date))->format('N');

        return match ($rule['type']) {
            'WEEKDAYS'   => $weekday <= 5,
            'WEEKLY'     => $weekday === (int) ($rule['weekday'] ?? 1),
            'CUSTOM_DAYS' => in_array($weekday, $rule['days'] ?? [], true),
            default      => true,
        };
    }

    public static function occurrenceIdentity(
        int $userId,
        int $activityId,
        string $date,
        string $repeatType,
        ?string $sessionCode = null,
        ?int $occurrenceNumber = null,
    ): string {
        $date       = self::assertDate($date);
        $repeatType = strtoupper($repeatType);

        $suffix = match ($repeatType) {
            'FIXED_SESSIONS' => 'SESSION_' . self::safeKey($sessionCode ?? 'DEFAULT'),
            'REPEATABLE'     => 'OCCURRENCE_' . max(1, (int) $occurrenceNumber),
            'WEEKLY'         => 'WEEK_' . self::isoWeekKey($date),
            default          => 'ONCE',
        };

        return sprintf(
            'user:%d:activity:%d:date:%s:%s:%s',
            $userId,
            $activityId,
            $date,
            $repeatType,
            $suffix,
        );
    }

    public static function isoWeekKey(string $date): string
    {
        return (new DateTimeImmutable(self::assertDate($date)))->format('o-\WW');
    }

    /**
     * @return array{start: string, end: string}
     */
    public static function weekBounds(string $date): array
    {
        $date      = self::assertDate($date);
        $day       = new DateTimeImmutable($date);
        $weekday   = (int) $day->format('N');
        $start     = $day->modify('-' . ($weekday - 1) . ' days');

        return [
            'start' => $start->format('Y-m-d'),
            'end'   => $start->modify('+6 days')->format('Y-m-d'),
        ];
    }

    public static function todayInTimezone(string $timezone, ?DateTimeImmutable $now = null): string
    {
        $zone = self::assertTimezone($timezone);
        $now  ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $now->setTimezone($zone)->format('Y-m-d');
    }

    public static function competitionDate(string $activityDate, string $timezone): string
    {
        $activityDate = self::assertDate($activityDate);
        $zone         = self::assertTimezone($timezone);
        $localNoon    = new DateTimeImmutable($activityDate . ' 12:00:00', $zone);

        return $localNoon
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTimezone(new DateTimeZone(self::COMPETITION_TIMEZONE))
            ->format('Y-m-d');
    }

    public static function localDateTimeToUtc(
        string $date,
        ?string $time,
        string $timezone,
        bool $nextDay = false,
    ): ?string {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $date = self::assertDate($date);
        $time = self::normalizeTime($time);
        $zone = self::assertTimezone($timezone);

        try {
            $local = new DateTimeImmutable($date . ' ' . $time, $zone);

            if ($nextDay) {
                $local = $local->modify('+1 day');
            }

            return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            throw new ValidationException('Waktu aktivitas tidak valid.');
        }
    }

    public static function cooldownRemainingSeconds(
        ?string $lastCompletedAtUtc,
        int $cooldownSeconds,
        ?DateTimeImmutable $now = null,
    ): int {
        if ($lastCompletedAtUtc === null || $cooldownSeconds <= 0) {
            return 0;
        }

        try {
            $last     = new DateTimeImmutable($lastCompletedAtUtc, new DateTimeZone('UTC'));
            $now    ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $available = $last->modify('+' . $cooldownSeconds . ' seconds');
            $remaining = $available->getTimestamp() - $now->getTimestamp();

            return max(0, $remaining);
        } catch (Throwable) {
            return 0;
        }
    }

    public static function normalizeTime(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $time = trim($time);

        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time)) {
            throw new ValidationException('Format waktu harus HH:MM.');
        }

        return substr($time, 0, 5);
    }

    public static function timeToMinutes(?string $time): ?int
    {
        $time = self::normalizeTime($time);

        if ($time === null) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }

    public static function assertDate(string $date): string
    {
        $date = trim($date);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new ValidationException('Tanggal tidak valid.');
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new ValidationException('Tanggal tidak valid.');
        }

        return $date;
    }

    public static function assertTimezone(string $timezone): DateTimeZone
    {
        try {
            return new DateTimeZone($timezone);
        } catch (Throwable) {
            throw new ValidationException('Zona waktu tidak valid.');
        }
    }

    private static function safeKey(string $value): string
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $value) ?? '');

        return $value === '' ? 'DEFAULT' : $value;
    }
}
