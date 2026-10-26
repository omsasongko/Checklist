<?php

namespace App\Controllers;

use App\Exceptions\AppException;
use App\Exceptions\ScheduleConfirmationException;
use App\Exceptions\ScheduleConflictException;
use App\Exceptions\ValidationException;
use App\Services\ActivityScheduleService;
use App\Services\ActivityService;
use Throwable;

class ActivityPlans extends BaseController
{
    private ActivityScheduleService $scheduleService;
    private ActivityService $activityService;

    public function __construct()
    {
        $this->activityService = new ActivityService();
        $this->scheduleService = new ActivityScheduleService($this->activityService);
    }

    public function index()
    {
        $editPlan = null;
        $editId   = (int) ($this->request->getGet('ubah') ?? 0);

        if ($editId > 0) {
            try {
                $editPlan = $this->scheduleService->findPlan($this->userId(), $editId);
            } catch (AppException) {
                return redirect()->to('/jadwal')->with('error', 'Jadwal yang ingin diubah tidak ditemukan.');
            }
        }

        // A pending confirmation (HTTP 409) is re-rendered from the session so
        // the dialog survives the redirect-back that follows a plain form POST.
        $pendingConfirmation = session()->getFlashdata('plan_confirmation');

        return view('plans/index', [
            'title'             => 'Jadwal Aktivitas - LifeQuest',
            'plans'             => $this->scheduleService->plans($this->userId()),
            'existingConflicts' => $this->scheduleService->existingConflicts($this->userId()),
            'activities'        => $this->activityService->activitiesForUser($this->userId(), [], true),
            'editPlan'          => $editPlan,
            'selectedActivityId' => (int) ($this->request->getGet('aktivitas') ?? ($editPlan['activity_id'] ?? 0)),
            'pendingConfirmation' => is_array($pendingConfirmation) ? $pendingConfirmation : null,
        ]);
    }

    public function save()
    {
        try {
            $result = $this->scheduleService->savePlan($this->userId(), [
                'plan_id'              => (int) ($this->request->getPost('plan_id') ?? 0),
                'activity_id'          => (int) ($this->request->getPost('activity_id') ?? 0),
                'operation'            => (string) ($this->request->getPost('operation') ?? ActivityScheduleService::OPERATION_CREATE),
                'confirm_plan_id'      => (int) ($this->request->getPost('confirm_plan_id') ?? 0),
                'confirm_plan_updated_at' => (string) ($this->request->getPost('confirm_plan_updated_at') ?? ''),
                'recurrence_type'      => (string) ($this->request->getPost('recurrence_type') ?? 'DAILY'),
                'recurrence_days'      => (array) ($this->request->getPost('recurrence_days') ?? []),
                'recurrence_weekday'   => (int) ($this->request->getPost('recurrence_weekday') ?? 1),
                'timezone'             => (string) ($this->request->getPost('timezone') ?? 'Asia/Jakarta'),
                'preferred_start_time' => (string) ($this->request->getPost('preferred_start_time') ?? ''),
                'preferred_end_time'   => (string) ($this->request->getPost('preferred_end_time') ?? ''),
                'is_enabled'           => (int) ($this->request->getPost('is_enabled') ?? 0),
            ]);

            // CASE C: the activity already has a schedule and the submission
            // would change it. The service wrote nothing; show the dialog that
            // asks the user to choose explicitly.
            if (($result['requires_confirmation'] ?? false) === true) {
                return $this->backToForm()->withInput()->with('plan_confirmation', [
                    'confirmation_required' => true,
                    'code'                  => 'SCHEDULE_EXISTS',
                    'message'               => 'Aktivitas ini sudah memiliki jadwal. Apakah Anda ingin mengganti jadwal lama atau menambahkan sesi baru?',
                    'existing'              => $result['existing'] ?? [],
                    'existing_plans'        => $result['existing_plans'] ?? [],
                    'proposed'              => $result['proposed'] ?? [],
                    'operations'            => [
                        'replace'     => in_array(ActivityScheduleService::OPERATION_REPLACE, $result['operations'] ?? [], true),
                        'add_session' => in_array(ActivityScheduleService::OPERATION_ADD_SESSION, $result['operations'] ?? [], true),
                    ],
                    'add_session_disabled_reason' => $result['add_session_disabled_reason'] ?? 'Kebijakan aktivitas ini tidak mengizinkan jadwal tambahan.',
                ]);
            }

            // CASE B: an identical schedule is already stored. Nothing changed.
            if (($result['duplicate'] ?? false) === true) {
                return redirect()->to('/jadwal')->with(
                    'info',
                    'Jadwal aktivitas ini sudah ada dan tidak diubah. '
                    . 'Gunakan tombol "Ubah" pada daftar jadwal bila ingin menyesuaikan jamnya.',
                );
            }

            $hasWarning = ($result['conflicts'] ?? []) !== [];

            $message = match (true) {
                ($result['added_session'] ?? false) === true => 'Sesi jadwal tambahan berhasil disimpan.',
                ($result['replaced'] ?? false) === true      => 'Jadwal aktivitas berhasil diganti.',
                default                                      => 'Jadwal aktivitas berhasil disimpan.',
            };

            $redirect = redirect()->to('/jadwal')->with('success', $message);

            if ($hasWarning) {
                $redirect->with(
                    'warning',
                    'Jadwal tersimpan, tetapi beberapa aktivitas paralel pada jam yang sama perlu Anda tinjau: '
                    . $this->describeSoftConflicts($result['conflicts']),
                );
            }

            return $redirect;
        } catch (ScheduleConfirmationException $exception) {
            // Not an error: the user must choose. Keep the submitted values and
            // carry the structured payload to the confirmation dialog.
            return $this->backToForm()->withInput()->with('plan_confirmation', $exception->toArray());
        } catch (ScheduleConflictException $exception) {
            // Hard conflicts are business validation, not system failures.
            return $this->backToForm()->withInput()->with('error', $exception->getMessage());
        } catch (AppException $exception) {
            return $this->backToForm()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Activity plan save failed: {message}', ['message' => $exception->getMessage()]);

            return $this->backToForm()->withInput()->with('error', 'Jadwal aktivitas gagal disimpan.');
        }
    }

    /**
     * Return the user to the schedule form.
     *
     * `redirect()->back()` depends on a `Referer` header that is not present in
     * every legitimate request (privacy tooling, hardened browsers, non-browser
     * clients), and when it is missing the redirect target degrades badly. The
     * schedule form always lives at /jadwal, so prefer that as an explicit
     * fallback while still honouring a same-origin referer when one exists.
     */
    private function backToForm(): \CodeIgniter\HTTP\RedirectResponse
    {
        $referer = (string) $this->request->getHeaderLine('Referer');
        $host    = (string) $this->request->getUri()->getHost();

        if ($referer !== '' && $host !== '' && str_contains($referer, $host)) {
            return redirect()->to($referer);
        }

        return redirect()->to('/jadwal');
    }

    public function toggle($id = null)
    {
        try {
            $enabled = (int) ($this->request->getPost('enabled') ?? 0) === 1;
            $this->scheduleService->setEnabled($this->userId(), (int) $id, $enabled);

            return redirect()->to('/jadwal')->with('success', 'Status jadwal berhasil diperbarui.');
        } catch (ScheduleConflictException $exception) {
            return redirect()->to('/jadwal')->with('error', $exception->getMessage());
        } catch (AppException $exception) {
            return redirect()->to('/jadwal')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Activity plan toggle failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to('/jadwal')->with('error', 'Status jadwal gagal diperbarui.');
        }
    }

    /**
     * @param list<array<string, mixed>> $conflicts
     */
    private function describeSoftConflicts(array $conflicts): string
    {
        $parts = [];

        foreach ($conflicts as $conflict) {
            $parts[] = sprintf(
                '"%s" dan "%s" (%s-%s)',
                (string) ($conflict['left']['activity_name'] ?? ''),
                (string) ($conflict['right']['activity_name'] ?? ''),
                (string) ($conflict['overlap_start'] ?? ''),
                (string) ($conflict['overlap_end'] ?? ''),
            );
        }

        return implode('; ', array_unique($parts)) . '.';
    }
}
