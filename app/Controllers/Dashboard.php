<?php

namespace App\Controllers;

use App\Libraries\ScheduleRules;
use App\Services\ChecklistService;
use Throwable;

class Dashboard extends BaseController
{
    public function index()
    {
        $user      = $this->currentUser();
        $today     = ScheduleRules::todayInTimezone(ScheduleRules::DEFAULT_TIMEZONE);
        $checklist = [
            'date'     => $today,
            'timezone' => ScheduleRules::DEFAULT_TIMEZONE,
            'items'    => [],
            'groups'   => [],
            'progress' => ['completed' => 0, 'total' => 0, 'percent' => 0],
        ];

        try {
            $checklist = (new ChecklistService())->getChecklist((int) $user->id, $today);
        } catch (Throwable $exception) {
            log_message('error', 'Dashboard checklist load failed: {message}', ['message' => $exception->getMessage()]);
        }

        return view('dashboard/index', [
            'title'     => 'Beranda - LifeQuest',
            'user'      => $user,
            'today'     => $today,
            'checklist' => $checklist,
        ]);
    }
}
