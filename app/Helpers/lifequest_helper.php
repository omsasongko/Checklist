<?php

if (! function_exists('lq_repeat_label')) {
    function lq_repeat_label(?string $repeatType): string
    {
        return match (strtoupper((string) $repeatType)) {
            'FIXED_SESSIONS' => 'Sesi Terjadwal',
            'REPEATABLE'     => 'Dapat Diulang',
            'WEEKLY'         => 'Mingguan',
            default          => 'Sekali Sehari',
        };
    }
}

if (! function_exists('lq_activity_type_label')) {
    function lq_activity_type_label(?string $activityType): string
    {
        return match (strtoupper((string) $activityType)) {
            'ALTERNATIVE' => 'Aktivitas Alternatif',
            'CUSTOM'      => 'Aktivitas Pribadi',
            default       => 'Aktivitas Bawaan',
        };
    }
}

if (! function_exists('lq_policy_label')) {
    function lq_policy_label(?string $policyCode): string
    {
        return match (strtoupper((string) $policyCode)) {
            'SELF_CHECK'      => 'Pemeriksaan Mandiri',
            'TIMER'           => 'Timer',
            'PHOTO_PROOF'     => 'Bukti Foto',
            'ADMIN_APPROVAL'  => 'Persetujuan Admin',
            default           => 'Tidak Diperlukan',
        };
    }
}

if (! function_exists('lq_verification_label')) {
    function lq_verification_label(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'PENDING'            => 'Menunggu Verifikasi',
            'AUTO_VALIDATED'     => 'Tervalidasi Otomatis',
            'MANUALLY_APPROVED'  => 'Disetujui Admin',
            'REJECTED'           => 'Ditolak',
            'NOT_REQUIRED'       => 'Tidak Diperlukan',
            default              => 'Tidak Diperlukan',
        };
    }
}

if (! function_exists('lq_completion_label')) {
    function lq_completion_label(?string $status): string
    {
        return strtoupper((string) $status) === 'COMPLETED' ? 'Aktivitas Selesai' : 'Belum Selesai';
    }
}

if (! function_exists('lq_date_id')) {
    function lq_date_id(?string $date, bool $withWeekday = false): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        $datePart = substr($date, 0, 10);

        try {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $datePart);

            if ($parsed === false) {
                return $datePart;
            }

            $months = lq_month_names();
            $label  = $parsed->format('d') . ' ' . $months[(int) $parsed->format('n')] . ' ' . $parsed->format('Y');

            return $withWeekday ? lq_day_name((int) $parsed->format('N')) . ', ' . $label : $label;
        } catch (Throwable) {
            return $datePart;
        }
    }
}

if (! function_exists('lq_datetime_id')) {
    function lq_datetime_id(?string $datetime, string $timezone = 'Asia/Jakarta'): string
    {
        if ($datetime === null || $datetime === '') {
            return '-';
        }

        try {
            $parsed = new DateTimeImmutable($datetime, new DateTimeZone('UTC'));
            $months = lq_month_names();

            return $parsed
                ->setTimezone(new DateTimeZone($timezone))
                ->format('d') . ' ' . $months[(int) $parsed->setTimezone(new DateTimeZone($timezone))->format('n')] . ' ' . $parsed->setTimezone(new DateTimeZone($timezone))->format('Y H:i');
        } catch (Throwable) {
            return $datetime;
        }
    }
}

if (! function_exists('lq_day_name')) {
    function lq_day_name(int $day): string
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

if (! function_exists('lq_month_names')) {
    /**
     * @return array<int, string>
     */
    function lq_month_names(): array
    {
        return [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }
}

if (! function_exists('lq_excerpt')) {
    function lq_excerpt(?string $text, int $limit = 120): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > $limit
            ? mb_substr($text, 0, $limit - 1) . '…'
            : $text;
    }
}
