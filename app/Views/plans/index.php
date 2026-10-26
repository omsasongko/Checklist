<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<?php
$isEdit = $editPlan !== null;
$selectedPlanId = (int) old('plan_id', $editPlan['id'] ?? 0);
$selectedActivity = (int) old('activity_id', $editPlan['activity_id'] ?? $selectedActivityId);
$recurrenceType = old('recurrence_type', $editPlan['recurrence']['type'] ?? 'DAILY');
$recurrenceWeekday = (int) old('recurrence_weekday', $editPlan['recurrence']['weekday'] ?? 1);
$recurrenceDays = old('recurrence_days', $editPlan['recurrence']['days'] ?? [1, 2, 3, 4, 5]);
$recurrenceDays = is_array($recurrenceDays) ? array_map('intval', $recurrenceDays) : [(int) $recurrenceDays];
$preferredTimezone = old('timezone', $editPlan['timezone'] ?? 'Asia/Jakarta');
$preferredStart = old('preferred_start_time', $editPlan['preferred_start_time'] ?? '');
$preferredEnd = old('preferred_end_time', $editPlan['preferred_end_time'] ?? '');
$preferredStart = $preferredStart ? substr((string) $preferredStart, 0, 5) : '';
$preferredEnd = $preferredEnd ? substr((string) $preferredEnd, 0, 5) : '';
$isEnabled = (int) old('is_enabled', $editPlan['is_enabled'] ?? 1) === 1;
$timezoneOptions = [
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
if (! isset($timezoneOptions[$preferredTimezone])) {
    $timezoneOptions = [$preferredTimezone => $preferredTimezone] + $timezoneOptions;
}
$dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

// CASE C confirmation: set by ActivityPlans::save() when the activity already
// has a schedule and the submission would replace or extend it.
$confirm = $pendingConfirmation ?? null;
$confirmExisting = is_array($confirm['existing'] ?? null) ? $confirm['existing'] : null;
$confirmProposed = is_array($confirm['proposed'] ?? null) ? $confirm['proposed'] : null;
$confirmOperations = is_array($confirm['operations'] ?? null) ? $confirm['operations'] : [];
$canReplace = (bool) ($confirmOperations['replace'] ?? false);
$canAddSession = (bool) ($confirmOperations['add_session'] ?? false);

$formatWindow = static function (?string $start, ?string $end): string {
    return ($start ? substr($start, 0, 5) : '--:--') . ' - ' . ($end ? substr($end, 0, 5) : '--:--');
};
?>

<div class="lq-page-head d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <div class="lq-eyebrow"><i class="bi bi-calendar2-week me-1"></i>Jadwal Saya</div>
        <h1 class="h2 fw-bold mb-1"><?= $isEdit ? 'Ubah Jadwal Aktivitas' : 'Atur Jadwal Aktivitas' ?></h1>
        <p class="text-muted mb-0">Tentukan aktivitas, pola pengulangan, zona waktu, dan jam yang Anda inginkan.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($isEdit): ?>
            <a href="/jadwal" class="btn btn-outline-light"><i class="bi bi-x-lg me-1"></i>Batal Ubah</a>
        <?php endif; ?>
        <a href="/aktivitas" class="btn btn-outline-primary"><i class="bi bi-grid me-1"></i>Katalog Aktivitas</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card lq-card lq-sticky-card">
            <div class="card-header lq-card-header">
                <h2 class="h5 fw-bold mb-0"><i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-calendar-plus' ?> me-2 text-electric-blue"></i><?= $isEdit ? 'Form Ubah Jadwal' : 'Form Jadwal Baru' ?></h2>
            </div>
            <div class="card-body p-4">
                <form action="/jadwal" method="post" class="lq-form js-plan-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= $selectedPlanId ?>">
                    <?php if ($isEdit): ?>
                        <?php // Editing a known plan is an explicit UPDATE. ?>
                        <input type="hidden" name="operation" value="<?= esc(\App\Services\ActivityScheduleService::OPERATION_REPLACE) ?>">
                    <?php else: ?>
                        <?php // A blank form is always an explicit CREATE. It never
                              // overwrites an existing schedule on its own. ?>
                        <input type="hidden" name="operation" value="<?= esc(\App\Services\ActivityScheduleService::OPERATION_CREATE) ?>">
                        <input type="hidden" name="confirm_plan_id" value="">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="activity_id" class="form-label fw-semibold">Aktivitas <span class="text-danger">*</span></label>
                        <?php if ($isEdit): ?>
                            <select class="form-select" id="activity_id" disabled>
                                <?php foreach ($activities as $activity): ?>
                                    <?php if ((int) $activity['id'] === $selectedActivity): ?>
                                        <option selected><?= esc($activity['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="activity_id" value="<?= $selectedActivity ?>">
                            <div class="form-text">Aktivitas pada jadwal tidak dapat diganti saat mengubah jadwal.</div>
                        <?php else: ?>
                            <select class="form-select" id="activity_id" name="activity_id" required>
                                <option value="">Pilih aktivitas</option>
                                <?php foreach ($activities as $activity): ?>
                                    <option value="<?= (int) $activity['id'] ?>" <?= $selectedActivity === (int) $activity['id'] ? 'selected' : '' ?>>
                                        <?= esc($activity['name']) ?> &mdash; <?= esc($activity['category_name'] ?: 'Lainnya') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="recurrence_type" class="form-label fw-semibold">Pola Pengulangan <span class="text-danger">*</span></label>
                        <select class="form-select" id="recurrence_type" name="recurrence_type" required>
                            <option value="DAILY" <?= $recurrenceType === 'DAILY' ? 'selected' : '' ?>>Setiap hari</option>
                            <option value="WEEKDAYS" <?= $recurrenceType === 'WEEKDAYS' ? 'selected' : '' ?>>Senin sampai Jumat</option>
                            <option value="WEEKLY" <?= $recurrenceType === 'WEEKLY' ? 'selected' : '' ?>>Setiap minggu pada hari tertentu</option>
                            <option value="CUSTOM_DAYS" <?= $recurrenceType === 'CUSTOM_DAYS' ? 'selected' : '' ?>>Hari tertentu</option>
                        </select>
                    </div>

                    <div class="mb-3" id="recurrence-weekday-field">
                        <label for="recurrence_weekday" class="form-label fw-semibold">Hari Mingguan</label>
                        <select class="form-select" id="recurrence_weekday" name="recurrence_weekday">
                            <?php foreach ($dayNames as $dayNumber => $dayName): ?>
                                <option value="<?= $dayNumber ?>" <?= $recurrenceWeekday === $dayNumber ? 'selected' : '' ?>><?= esc($dayName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <fieldset class="mb-3" id="recurrence-days-field">
                        <legend class="form-label fw-semibold fs-6">Pilih Hari</legend>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($dayNames as $dayNumber => $dayName): ?>
                                <div class="form-check lq-day-check">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" id="day-<?= $dayNumber ?>"
                                           value="<?= $dayNumber ?>" <?= in_array($dayNumber, $recurrenceDays, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="day-<?= $dayNumber ?>"><?= esc($dayName) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div class="mb-3">
                        <label for="timezone" class="form-label fw-semibold">Zona Waktu <span class="text-danger">*</span></label>
                        <select class="form-select" id="timezone" name="timezone" required>
                            <?php foreach ($timezoneOptions as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= $preferredTimezone === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Tanggal checklist mengikuti zona waktu ini.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="preferred_start_time" class="form-label fw-semibold">Jam Mulai</label>
                            <input type="time" class="form-control" id="preferred_start_time" name="preferred_start_time" value="<?= esc($preferredStart) ?>">
                        </div>
                        <div class="col-6">
                            <label for="preferred_end_time" class="form-label fw-semibold">Jam Selesai</label>
                            <input type="time" class="form-control" id="preferred_end_time" name="preferred_end_time" value="<?= esc($preferredEnd) ?>">
                        </div>
                    </div>
                    <div class="form-text mb-3">Jam bersifat opsional. Untuk aktivitas dengan sesi terjadwal, jam sesi bawaan akan digunakan. Jadwal tidak boleh bertabrakan dengan jadwal aktif lain pada hari yang sama; waktu yang bersambung (misalnya 05:30&ndash;06:00 lalu 06:00&ndash;06:30) tetap diperbolehkan.</div>

                    <div class="form-check form-switch mb-4">
                        <input type="hidden" name="is_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_enabled" name="is_enabled" value="1" <?= $isEnabled ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_enabled">Jadwal aktif</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Simpan Perubahan Jadwal' : 'Simpan Jadwal' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <?php if (($existingConflicts ?? []) !== []): ?>
            <div class="alert alert-warning lq-alert mb-3" role="alert">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Jadwal yang saling bertabrakan</div>
                <p class="small mb-2">Jadwal aktif Anda berikut memiliki waktu yang beririsan. Anda perlu menyesuaikan jam atau menonaktifkan salah satunya. Data jadwal tidak diubah otomatis.</p>
                <ul class="small mb-0">
                    <?php foreach ($existingConflicts as $conflict): ?>
                        <li class="mb-1">
                            <strong><?= esc($conflict['left']['activity_name']) ?></strong>
                            (<?= esc($conflict['left']['window_start']) ?>&ndash;<?= esc($conflict['left']['window_end']) ?>)
                            dan <strong><?= esc($conflict['right']['activity_name']) ?></strong>
                            (<?= esc($conflict['right']['window_start']) ?>&ndash;<?= esc($conflict['right']['window_end']) ?>)
                            &middot; bertabrakan <?= esc($conflict['overlap_start']) ?>&ndash;<?= esc($conflict['overlap_end']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0"><i class="bi bi-list-ul me-2 text-purple"></i>Daftar Jadwal</h2>
            <span class="badge lq-badge lq-badge-muted"><?= count($plans) ?> jadwal</span>
        </div>

        <?php if ($plans === []): ?>
            <div class="card lq-card">
                <div class="lq-empty-state">
                    <i class="bi bi-calendar2-plus"></i>
                    <h3 class="h6 fw-bold mt-3">Belum ada jadwal aktivitas</h3>
                    <p class="text-muted small mb-3">Pilih aktivitas dari katalog dan simpan jadwal pertama Anda.</p>
                    <a href="/aktivitas" class="btn btn-primary"><i class="bi bi-grid me-1"></i>Buka Katalog</a>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($plans as $plan): ?>
                    <article class="card lq-card lq-plan-card <?= (int) $plan['is_enabled'] === 1 ? '' : 'is-disabled' ?>">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                <div class="d-flex gap-3">
                                    <span class="lq-activity-icon"><i class="bi <?= esc($plan['category_icon'] ?: 'bi-activity') ?>"></i></span>
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <h3 class="h6 fw-bold mb-0"><?= esc($plan['activity_name']) ?></h3>
                                            <?php if ((int) $plan['is_enabled'] === 1): ?>
                                                <span class="badge lq-badge lq-badge-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge lq-badge lq-badge-muted">Nonaktif</span>
                                            <?php endif; ?>
                                            <?php if ((int) $plan['activity_active'] !== 1): ?>
                                                <span class="badge lq-badge lq-badge-warning">Aktivitas nonaktif</span>
                                            <?php endif; ?>
                                            <?php if (($plan['conflicts'] ?? []) !== []): ?>
                                                <span class="badge lq-badge lq-badge-warning" title="Waktu jadwal beririsan dengan jadwal lain"><i class="bi bi-exclamation-triangle me-1"></i>Bertabrakan</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted mb-2">
                                            <i class="bi bi-tag me-1"></i><?= esc($plan['category_name'] ?: 'Lainnya') ?>
                                            &middot; <?= esc(lq_repeat_label($plan['repeat_type'])) ?>
                                        </div>
                                        <div class="d-flex flex-wrap gap-3 lq-meta">
                                            <span><i class="bi bi-arrow-repeat me-1"></i><?= esc($plan['recurrence_label']) ?></span>
                                            <span><i class="bi bi-globe2 me-1"></i><?= esc($plan['timezone']) ?></span>
                                            <?php if ($plan['preferred_start_time'] || $plan['preferred_end_time']): ?>
                                                <span><i class="bi bi-clock me-1"></i>
                                                    <?= esc($plan['preferred_start_time'] ? substr($plan['preferred_start_time'], 0, 5) : '--:--') ?>
                                                    -
                                                    <?= esc($plan['preferred_end_time'] ? substr($plan['preferred_end_time'], 0, 5) : '--:--') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <a href="/jadwal?ubah=<?= (int) $plan['id'] ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil me-1"></i>Ubah</a>
                                    <form action="/jadwal/<?= (int) $plan['id'] ?>/status" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="enabled" value="<?= (int) $plan['is_enabled'] === 1 ? '0' : '1' ?>">
                                        <button type="submit" class="btn btn-sm <?= (int) $plan['is_enabled'] === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                            <i class="bi <?= (int) $plan['is_enabled'] === 1 ? 'bi-pause-circle' : 'bi-play-circle' ?> me-1"></i>
                                            <?= (int) $plan['is_enabled'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php if ($plan['repeat_type'] === 'FIXED_SESSIONS'): ?>
                                <div class="small text-muted mt-3 pt-3 border-top border-dark-subtle">
                                    <i class="bi bi-info-circle me-1"></i>Checklist akan mengikuti sesi terjadwal aktivitas ini.
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($confirmExisting !== null && $confirmProposed !== null): ?>
    <div class="modal fade" id="lq-plan-confirm-modal" tabindex="-1" aria-labelledby="lq-plan-confirm-title" aria-hidden="true"
         data-lq-autoshow="1"
         data-existing-plan-id="<?= (int) ($confirmExisting['plan_id'] ?? 0) ?>">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content lq-card">
                <div class="modal-header lq-card-header">
                    <h2 class="modal-title h5 fw-bold" id="lq-plan-confirm-title">
                        <i class="bi bi-calendar2-exclamation me-2 text-warning"></i>Aktivitas Sudah Terjadwal
                    </h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted">
                        Aktivitas ini sudah memiliki jadwal. Apakah Anda ingin mengganti jadwal lama atau menambahkan sesi baru?
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="lq-confirm-panel h-100">
                                <div class="lq-eyebrow mb-2"><i class="bi bi-clock-history me-1"></i>Jadwal Saat Ini</div>
                                <div class="fw-semibold mb-1"><?= esc($confirmExisting['activity_name'] ?? '') ?></div>
                                <ul class="list-unstyled small mb-0 lq-meta">
                                    <li><i class="bi bi-arrow-repeat me-1"></i><?= esc($confirmExisting['recurrence_label'] ?? '') ?></li>
                                    <li><i class="bi bi-clock me-1"></i><?= esc($formatWindow($confirmExisting['preferred_start_time'] ?? null, $confirmExisting['preferred_end_time'] ?? null)) ?></li>
                                    <li><i class="bi bi-globe2 me-1"></i><?= esc($confirmExisting['timezone'] ?? '') ?></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="lq-confirm-panel h-100">
                                <div class="lq-eyebrow mb-2"><i class="bi bi-calendar-plus me-1"></i>Jadwal Baru</div>
                                <div class="fw-semibold mb-1"><?= esc($confirmProposed['activity_name'] ?? '') ?></div>
                                <ul class="list-unstyled small mb-0 lq-meta">
                                    <li><i class="bi bi-arrow-repeat me-1"></i><?= esc($confirmProposed['recurrence_label'] ?? '') ?></li>
                                    <li><i class="bi bi-clock me-1"></i><?= esc($formatWindow($confirmProposed['preferred_start_time'] ?? null, $confirmProposed['preferred_end_time'] ?? null)) ?></li>
                                    <li><i class="bi bi-globe2 me-1"></i><?= esc($confirmProposed['timezone'] ?? '') ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning lq-alert mb-0" role="alert">
                        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Dampak pada jadwal saat ini</div>
                        <ul class="small mb-0">
                            <?php if ($canReplace): ?>
                                <li><strong>Ganti Jadwal</strong> mengubah jam jadwal yang sudah ada. Riwayat penyelesaian aktivitas Anda tetap tersimpan.</li>
                            <?php endif; ?>
                            <?php if ($canAddSession): ?>
                                <li><strong>Tambah Sesi</strong> mempertahankan jadwal lama dan menambahkan sesi baru pada jam tersebut.</li>
                            <?php endif; ?>
                            <li><strong>Batal</strong> menutup dialog ini tanpa mengubah apa pun. Pilihan ini adalah pilihan bawaan.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-light order-sm-1" data-bs-dismiss="modal" id="lq-plan-confirm-cancel">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <?php if ($canAddSession): ?>
                        <button type="button" class="btn btn-outline-primary order-sm-2" data-lq-plan-choice="add">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Sesi
                        </button>
                    <?php endif; ?>
                    <?php if ($canReplace): ?>
                        <button type="button" class="btn btn-primary order-sm-3" data-lq-plan-choice="replace">
                            <i class="bi bi-arrow-repeat me-1"></i>Ganti Jadwal
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
