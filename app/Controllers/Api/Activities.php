<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Exceptions\AppException;
use App\Libraries\ScheduleRules;
use App\Services\ActivityScheduleService;
use App\Services\ActivityService;
use App\Services\ChecklistService;
use Throwable;

class Activities extends BaseController
{
    private ActivityService $activityService;
    private ChecklistService $checklistService;

    public function __construct()
    {
        $this->activityService  = new ActivityService();
        $this->checklistService = new ChecklistService($this->activityService);
    }

    public function index()
    {
        try {
            $filters = [
                'q'           => (string) ($this->request->getGet('q') ?? ''),
                'category_id' => (int) ($this->request->getGet('kategori') ?? 0),
            ];

            $activities = $this->activityService->activitiesForUser($this->userId(), $filters);

            return $this->response->setJSON([
                'success'    => true,
                'data'       => [
                    'categories' => $this->activityService->categories(),
                    'activities' => $activities,
                ],
                'meta'       => [
                    'total'       => count($activities),
                    'xp_awarded'  => 0,
                    'rp_awarded'  => 0,
                    'reward_note' => 'XP dan RP belum diberikan pada Fase 2.',
                ],
            ]);
        } catch (AppException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            log_message('error', 'Activity catalog API failed: {message}', ['message' => $exception->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Katalog aktivitas gagal dimuat.',
            ]);
        }
    }

    public function today()
    {
        try {
            $timezone = (string) ($this->request->getGet('zona') ?? ScheduleRules::DEFAULT_TIMEZONE);
            ScheduleRules::assertTimezone($timezone);
            $date = (string) ($this->request->getGet('tanggal') ?? ScheduleRules::todayInTimezone($timezone));
            $checklist = $this->checklistService->getChecklist($this->userId(), $date, $timezone);

            return $this->response->setJSON([
                'success' => true,
                'data'    => $checklist,
                'meta'    => [
                    'xp_awarded'  => 0,
                    'rp_awarded'  => 0,
                    'reward_note' => 'XP dan RP belum diberikan pada Fase 2.',
                ],
            ]);
        } catch (AppException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            log_message('error', 'Checklist API failed: {message}', ['message' => $exception->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Checklist harian gagal dimuat.',
            ]);
        }
    }

    public function history()
    {
        try {
            $timezone = (string) ($this->request->getGet('zona') ?? ScheduleRules::DEFAULT_TIMEZONE);
            ScheduleRules::assertTimezone($timezone);
            $today = ScheduleRules::todayInTimezone($timezone);
            $from  = (string) ($this->request->getGet('dari') ?? (new \DateTimeImmutable($today))->modify('-29 days')->format('Y-m-d'));
            $to    = (string) ($this->request->getGet('sampai') ?? $today);
            $history = $this->checklistService->getHistory(
                $this->userId(),
                $from,
                $to,
                (string) ($this->request->getGet('status') ?? 'ALL'),
                (int) ($this->request->getGet('halaman') ?? 1),
                20,
                $timezone,
            );

            return $this->response->setJSON([
                'success' => true,
                'data'    => $history,
                'meta'    => [
                    'xp_awarded'  => 0,
                    'rp_awarded'  => 0,
                    'reward_note' => 'XP dan RP belum diberikan pada Fase 2.',
                ],
            ]);
        } catch (AppException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            log_message('error', 'History API failed: {message}', ['message' => $exception->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Riwayat aktivitas gagal dimuat.',
            ]);
        }
    }

    public function complete($activityId = null)
    {
        try {
            $payload = $this->request->is('json')
                ? $this->request->getJSON(true)
                : $this->request->getPost();
        } catch (Throwable) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Format permintaan tidak valid.',
            ]);
        }

        if (! is_array($payload)) {
            $payload = $this->request->getPost();
        }

        $instanceId = (int) ($payload['instance_id'] ?? 0);

        try {
            $result = $this->checklistService->completeInstance(
                $this->userId(),
                $instanceId,
                (int) $activityId,
            );

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'data'    => $result,
                'meta'    => [
                    'xp_awarded'  => 0,
                    'rp_awarded'  => 0,
                    'reward_note' => 'XP dan RP belum diberikan pada Fase 2.',
                ],
            ]);
        } catch (AppException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            log_message('error', 'Activity completion API failed: {message}', ['message' => $exception->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Aktivitas gagal diselesaikan.',
            ]);
        }
    }

    private function errorResponse(AppException $exception)
    {
        return $this->response->setStatusCode($exception->statusCode())->setJSON([
            'success' => false,
            'message' => $exception->getMessage(),
        ]);
    }
}
