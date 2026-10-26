<?php

namespace App\Controllers;

use App\Exceptions\AppException;
use App\Exceptions\ForbiddenException;
use App\Services\ActivityService;
use Throwable;

class Activities extends BaseController
{
    private ActivityService $activityService;

    public function __construct()
    {
        $this->activityService = new ActivityService();
    }

    public function index()
    {
        $filters = [
            'q'           => (string) ($this->request->getGet('q') ?? ''),
            'category_id' => (int) ($this->request->getGet('kategori') ?? 0),
        ];

        return view('activities/index', [
            'title'      => 'Katalog Aktivitas - LifeQuest',
            'categories' => $this->activityService->categories(),
            'activities' => $this->activityService->activitiesForUser($this->userId(), $filters),
            'filters'    => $filters,
        ]);
    }

    public function show($id = null)
    {
        try {
            $activity = $this->activityService->findVisibleActivity((int) $id, $this->userId());
        } catch (AppException $exception) {
            return redirect()->to('/aktivitas')->with('error', $exception->getMessage());
        }

        return view('activities/show', [
            'title'    => esc($activity['name']) . ' - LifeQuest',
            'activity' => $activity,
            'sessions' => $this->activityService->sessionsForActivity((int) $activity['id']),
        ]);
    }

    public function create()
    {
        return view('activities/form', [
            'title'      => 'Buat Aktivitas Pribadi - LifeQuest',
            'activity'   => null,
            'categories' => $this->activityService->categories(),
        ]);
    }

    public function store()
    {
        try {
            $activityId = $this->activityService->createCustomActivity(
                $this->userId(),
                $this->customInput(),
            );

            return redirect()->to('/aktivitas/' . $activityId)
                ->with('success', 'Aktivitas pribadi berhasil dibuat.');
        } catch (AppException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Custom activity creation failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Aktivitas pribadi gagal dibuat. Silakan coba lagi.');
        }
    }

    public function edit($id = null)
    {
        $activity = $this->activityService->findActivityRecord((int) $id);

        if (! $this->activityService->canModifyActivity($activity, $this->userId())) {
            return redirect()->to('/aktivitas')->with('error', 'Aktivitas ini tidak dapat diubah.');
        }

        return view('activities/form', [
            'title'      => 'Ubah Aktivitas Pribadi - LifeQuest',
            'activity'   => $activity,
            'categories' => $this->activityService->categories(),
        ]);
    }

    public function update($id = null)
    {
        try {
            $this->activityService->updateCustomActivity(
                $this->userId(),
                (int) $id,
                $this->customInput(),
            );

            return redirect()->to('/aktivitas/' . (int) $id)
                ->with('success', 'Aktivitas pribadi berhasil diperbarui.');
        } catch (AppException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Custom activity update failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Aktivitas pribadi gagal diperbarui. Silakan coba lagi.');
        }
    }

    public function deactivate($id = null)
    {
        try {
            $this->activityService->deactivateCustomActivity($this->userId(), (int) $id);

            return redirect()->to('/aktivitas')->with('success', 'Aktivitas pribadi berhasil dinonaktifkan.');
        } catch (AppException $exception) {
            return redirect()->to('/aktivitas')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Custom activity deactivation failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to('/aktivitas')->with('error', 'Aktivitas pribadi gagal dinonaktifkan.');
        }
    }

    private function customInput(): array
    {
        return [
            'name'                       => (string) ($this->request->getPost('name') ?? ''),
            'description'                => (string) ($this->request->getPost('description') ?? ''),
            'category_id'                => (int) ($this->request->getPost('category_id') ?? 0),
            'repeat_type'                => (string) ($this->request->getPost('repeat_type') ?? 'ONCE_DAILY'),
            'maximum_daily_completions'  => (int) ($this->request->getPost('maximum_daily_completions') ?? 1),
            'maximum_weekly_completions' => (int) ($this->request->getPost('maximum_weekly_completions') ?? 7),
            'cooldown_seconds'           => (int) ($this->request->getPost('cooldown_seconds') ?? 0),
        ];
    }
}
