<?php

namespace App\Controllers;

use App\Exceptions\AppException;
use App\Libraries\ScheduleRules;
use App\Services\ChecklistService;
use DateTimeImmutable;
use Throwable;

class Checklist extends BaseController
{
    private ChecklistService $checklistService;

    public function __construct()
    {
        $this->checklistService = new ChecklistService();
    }

    public function index()
    {
        $timezone = $this->requestedTimezone();
        $date     = $this->requestedDate($timezone);

        $checklist = $this->checklistService->getChecklist($this->userId(), $date, $timezone);
        $selected  = new DateTimeImmutable($date);

        return view('checklist/index', [
            'title'           => 'Checklist Harian - LifeQuest',
            'checklist'       => $checklist,
            'timezone'        => $timezone,
            'timezoneOptions' => $this->timezoneOptions(),
            'previousDate'    => $selected->modify('-1 day')->format('Y-m-d'),
            'nextDate'        => $selected->modify('+1 day')->format('Y-m-d'),
        ]);
    }

    public function complete($id = null)
    {
        $timezone = $this->requestedTimezone();
        $date     = $this->requestedDate($timezone);

        try {
            $result = $this->checklistService->completeInstance(
                $this->userId(),
                (int) $id,
                (int) ($this->request->getPost('activity_id') ?? 0) ?: null,
            );

            if ($this->isAjax()) {
                return $this->response->setStatusCode(200)->setJSON([
                    'success' => true,
                    'data'    => $result,
                ]);
            }

            return redirect()->to('/checklist?tanggal=' . urlencode($date) . '&zona=' . urlencode($timezone))
                ->with('success', $result['message']);
        } catch (AppException $exception) {
            if ($this->isAjax()) {
                return $this->response->setStatusCode($exception->statusCode())->setJSON([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ]);
            }

            return redirect()->to('/checklist?tanggal=' . urlencode($date) . '&zona=' . urlencode($timezone))
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Activity completion failed: {message}', ['message' => $exception->getMessage()]);

            if ($this->isAjax()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Aktivitas gagal diselesaikan. Silakan coba lagi.',
                ]);
            }

            return redirect()->to('/checklist?tanggal=' . urlencode($date) . '&zona=' . urlencode($timezone))
                ->with('error', 'Aktivitas gagal diselesaikan. Silakan coba lagi.');
        }
    }

    public function history()
    {
        $timezone = $this->requestedTimezone();
        $today    = ScheduleRules::todayInTimezone($timezone);
        $from     = (string) ($this->request->getGet('dari') ?? (new DateTimeImmutable($today))->modify('-29 days')->format('Y-m-d'));
        $to       = (string) ($this->request->getGet('sampai') ?? $today);
        $status   = strtoupper((string) ($this->request->getGet('status') ?? 'ALL'));
        $page     = (int) ($this->request->getGet('halaman') ?? 1);

        try {
            $history = $this->checklistService->getHistory($this->userId(), $from, $to, $status, $page, 20, $timezone);
        } catch (AppException $exception) {
            return redirect()->to('/riwayat')->with('error', $exception->getMessage());
        }

        return view('checklist/history', [
            'title'           => 'Riwayat Aktivitas - LifeQuest',
            'history'         => $history,
            'timezone'        => $timezone,
            'timezoneOptions' => $this->timezoneOptions(),
        ]);
    }

    private function requestedTimezone(): string
    {
        $timezone = trim((string) ($this->request->getGet('zona') ?? $this->request->getPost('zona') ?? ScheduleRules::DEFAULT_TIMEZONE));

        if ($timezone === '') {
            return ScheduleRules::DEFAULT_TIMEZONE;
        }

        try {
            ScheduleRules::assertTimezone($timezone);

            return $timezone;
        } catch (AppException) {
            return ScheduleRules::DEFAULT_TIMEZONE;
        }
    }

    private function requestedDate(string $timezone): string
    {
        $date = trim((string) ($this->request->getGet('tanggal') ?? $this->request->getPost('tanggal') ?? ''));

        if ($date === '') {
            return ScheduleRules::todayInTimezone($timezone);
        }

        try {
            return ScheduleRules::assertDate($date);
        } catch (AppException) {
            return ScheduleRules::todayInTimezone($timezone);
        }
    }

    /**
     * @return array<string, string>
     */
    private function timezoneOptions(): array
    {
        return [
            'Asia/Jakarta'        => 'WIB - Asia/Jakarta',
            'Asia/Makassar'       => 'WITA - Asia/Makassar',
            'Asia/Jayapura'       => 'WIT - Asia/Jayapura',
            'UTC'                 => 'UTC',
            'Asia/Tokyo'          => 'Asia/Tokyo',
            'Australia/Sydney'    => 'Australia/Sydney',
            'Europe/London'       => 'Europe/London',
            'America/Los_Angeles' => 'America/Los_Angeles',
            'Pacific/Kiritimati'  => 'Pacific/Kiritimati',
        ];
    }
}
