<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<?php
$progress = $checklist['progress'];
$isViewingToday = $checklist['date'] === $checklist['today'];
$timezoneChoices = $timezoneOptions;
if (! isset($timezoneChoices[$timezone])) {
    $timezoneChoices = [$timezone => $timezone] + $timezoneChoices;
}
?>

<div class="lq-page-head d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <div class="lq-eyebrow"><i class="bi bi-check2-square me-1"></i>Checklist Harian</div>
        <h1 class="h2 fw-bold mb-1"><?= esc(lq_date_id($checklist['date'], true)) ?></h1>
        <p class="text-muted mb-0">Zona waktu <?= esc($timezone) ?><?= $isViewingToday ? ' &middot; Hari ini' : ' &middot; Tanggal lain' ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/checklist?tanggal=<?= urlencode($previousDate) ?>&zona=<?= urlencode($timezone) ?>" class="btn btn-outline-light" aria-label="Tanggal sebelumnya">
            <i class="bi bi-chevron-left"></i>
        </a>
        <a href="/checklist?tanggal=<?= urlencode($checklist['today']) ?>&zona=<?= urlencode($timezone) ?>" class="btn btn-outline-primary">Hari Ini</a>
        <a href="/checklist?tanggal=<?= urlencode($nextDate) ?>&zona=<?= urlencode($timezone) ?>" class="btn btn-outline-light" aria-label="Tanggal berikutnya">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<div class="card lq-card mb-4">
    <div class="card-body">
        <form action="/checklist" method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="tanggal" class="form-label small fw-semibold">Tanggal</label>
                <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= esc($checklist['date']) ?>">
            </div>
            <div class="col-md-5">
                <label for="zona" class="form-label small fw-semibold">Zona Waktu</label>
                <select class="form-select" id="zona" name="zona">
                    <?php foreach ($timezoneChoices as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= $value === $timezone ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="card lq-card lq-progress-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div>
                <span class="text-muted small">Progres Checklist</span>
                <div class="fw-bold"><span id="lq-progress-label"><?= (int) $progress['percent'] ?></span>% selesai</div>
            </div>
            <div class="text-end">
                <span class="text-muted small">Aktivitas Selesai</span>
                <div class="fw-bold"><span id="lq-progress-completed"><?= (int) $progress['completed'] ?></span> / <span id="lq-progress-total"><?= (int) $progress['total'] ?></span></div>
            </div>
        </div>
        <div class="lq-progress" role="progressbar" aria-label="Progres checklist" aria-valuenow="<?= (int) $progress['percent'] ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="lq-progress-fill" id="lq-progress-fill" style="width: <?= (int) $progress['percent'] ?>%"></div>
        </div>
        <div class="small text-muted mt-2" id="lq-progress-caption"><?= (int) $progress['completed'] ?> dari <?= (int) $progress['total'] ?> aktivitas selesai</div>
    </div>
</div>

<?php if (! $isViewingToday): ?>
    <div class="alert alert-info lq-alert" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>Anda melihat tanggal lain. Penyelesaian aktivitas hanya diizinkan pada tanggal aktivitas yang sesuai dengan zona waktu jadwal.
    </div>
<?php endif; ?>

<?php if ($checklist['groups'] === []): ?>
    <div class="card lq-card">
        <div class="lq-empty-state">
            <i class="bi bi-calendar-x"></i>
            <h2 class="h5 fw-bold mt-3">Tidak ada aktivitas untuk tanggal ini</h2>
            <p class="text-muted small mb-3">Tambahkan aktivitas ke jadwal Anda agar checklist harian terbentuk otomatis.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/aktivitas" class="btn btn-primary"><i class="bi bi-grid me-1"></i>Buka Katalog Aktivitas</a>
                <a href="/jadwal" class="btn btn-outline-light"><i class="bi bi-calendar-plus me-1"></i>Atur Jadwal</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($checklist['groups'] as $group): ?>
        <section class="mb-4" aria-labelledby="group-<?= esc(md5($group['name'])) ?>">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="lq-group-icon"><i class="bi <?= esc($group['icon'] ?: 'bi-collection') ?>"></i></span>
                <h2 class="h5 fw-bold mb-0" id="group-<?= esc(md5($group['name'])) ?>"><?= esc($group['name']) ?></h2>
                <span class="badge lq-badge lq-badge-muted"><?= count($group['items']) ?></span>
            </div>
            <div class="card lq-card">
                <div class="lq-check-list">
                    <?php foreach ($group['items'] as $item): ?>
                        <?= $this->setData([
                            'item' => $item,
                            'compact' => false,
                            'listDate' => $checklist['date'],
                            'listTimezone' => $checklist['timezone'],
                        ])->include('checklist/_item') ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<div class="lq-info-panel mt-4">
    <i class="bi bi-stars"></i>
    <div>
        <strong>Referensi XP dan RP</strong>
        <p class="mb-0 small">Nilai XP/RP yang tampil adalah konfigurasi referensi. Pada Fase 2, penyelesaian aktivitas tidak memberikan XP/RP dan tidak memengaruhi peringkat musim.</p>
    </div>
</div>

<?= $this->endSection() ?>
