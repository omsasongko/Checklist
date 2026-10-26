<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<?php
$timezoneChoices = $timezoneOptions;
if (! isset($timezoneChoices[$timezone])) {
    $timezoneChoices = [$timezone => $timezone] + $timezoneChoices;
}

$historyUrl = static function (int $page) use ($history, $timezone): string {
    return '/riwayat?' . http_build_query([
        'dari'    => $history['from'],
        'sampai'  => $history['to'],
        'status'  => $history['status'],
        'zona'    => $timezone,
        'halaman' => $page,
    ]);
};
?>

<div class="lq-page-head d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <div class="lq-eyebrow"><i class="bi bi-clock-history me-1"></i>Riwayat Aktivitas</div>
        <h1 class="h2 fw-bold mb-1">Catatan Penyelesaian</h1>
        <p class="text-muted mb-0">Tinjau aktivitas yang belum selesai dan yang sudah diselesaikan.</p>
    </div>
    <a href="/checklist?tanggal=<?= urlencode($history['to']) ?>&zona=<?= urlencode($timezone) ?>" class="btn btn-primary">
        <i class="bi bi-check2-square me-1"></i>Buka Checklist
    </a>
</div>

<div class="card lq-card mb-4">
    <div class="card-body">
        <form action="/riwayat" method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="dari" class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" class="form-control" id="dari" name="dari" value="<?= esc($history['from']) ?>">
            </div>
            <div class="col-md-3">
                <label for="sampai" class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" class="form-control" id="sampai" name="sampai" value="<?= esc($history['to']) ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="ALL" <?= $history['status'] === 'ALL' ? 'selected' : '' ?>>Semua</option>
                    <option value="COMPLETED" <?= $history['status'] === 'COMPLETED' ? 'selected' : '' ?>>Selesai</option>
                    <option value="PENDING" <?= $history['status'] === 'PENDING' ? 'selected' : '' ?>>Belum Selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="zona" class="form-label small fw-semibold">Zona Waktu</label>
                <select class="form-select" id="zona" name="zona">
                    <?php foreach ($timezoneChoices as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= $value === $timezone ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card lq-card">
            <div class="card-body py-3">
                <div class="text-muted small">Total Aktivitas</div>
                <div class="h4 fw-bold mb-0"><?= (int) $history['summary']['completed'] + (int) $history['summary']['pending'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card lq-card">
            <div class="card-body py-3">
                <div class="text-muted small">Aktivitas Selesai</div>
                <div class="h4 fw-bold text-success mb-0"><?= (int) $history['summary']['completed'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card lq-card">
            <div class="card-body py-3">
                <div class="text-muted small">Belum Selesai</div>
                <div class="h4 fw-bold text-warning mb-0"><?= (int) $history['summary']['pending'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card lq-card">
    <div class="card-header lq-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h5 fw-bold mb-0"><i class="bi bi-table me-2 text-electric-blue"></i>Daftar Riwayat</h2>
        <span class="small text-muted"><?= (int) $history['total'] ?> hasil</span>
    </div>
    <div class="card-body p-0">
        <?php if ($history['items'] === []): ?>
            <div class="lq-empty-state">
                <i class="bi bi-inbox"></i>
                <h3 class="h6 fw-bold mt-3">Belum ada riwayat pada rentang ini</h3>
                <p class="text-muted small mb-0">Coba ubah rentang tanggal atau filter status.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark lq-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Aktivitas</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Status</th>
                            <th scope="col">Verifikasi</th>
                            <th scope="col">Diselesaikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history['items'] as $row): ?>
                            <tr>
                                <td class="text-nowrap"><?= esc(lq_date_id($row['activity_date'])) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc($row['activity_name']) ?></div>
                                    <div class="small text-muted">
                                        <?= esc(lq_repeat_label($row['repeat_type'])) ?>
                                        <?php if ($row['session_name']): ?>
                                            &middot; <?= esc($row['session_name']) ?>
                                        <?php endif; ?>
                                        <?php if ($row['occurrence_number'] !== null): ?>
                                            &middot; Sesi <?= (int) $row['occurrence_number'] ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="badge lq-badge lq-badge-muted"><?= esc($row['category_name'] ?: 'Lainnya') ?></span></td>
                                <td>
                                    <?php if ($row['completion_status'] === 'COMPLETED'): ?>
                                        <span class="badge lq-badge lq-badge-success"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                                    <?php else: ?>
                                        <span class="badge lq-badge lq-badge-warning"><i class="bi bi-hourglass-split me-1"></i>Belum</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['completion_status'] === 'COMPLETED'): ?>
                                        <span class="small <?= $row['verification_status'] === 'PENDING' ? 'text-warning' : 'text-muted' ?>"><?= esc(lq_verification_label($row['verification_status'])) ?></span>
                                    <?php else: ?>
                                        <span class="small text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted text-nowrap">
                                    <?php if ($row['completed_at_local']): ?>
                                        <?= esc(lq_date_id($row['completed_at_local'])) ?><br>
                                        <?= esc(substr($row['completed_at_local'], 11, 5)) ?> <?= esc($timezone) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($history['pages'] > 1): ?>
        <div class="card-footer lq-card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-muted">Halaman <?= (int) $history['page'] ?> dari <?= (int) $history['pages'] ?></span>
            <nav aria-label="Navigasi riwayat">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $history['page'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc($historyUrl(max(1, $history['page'] - 1))) ?>" aria-label="Sebelumnya">&laquo;</a>
                    </li>
                    <?php for ($pageNumber = max(1, $history['page'] - 2); $pageNumber <= min($history['pages'], $history['page'] + 2); $pageNumber++): ?>
                        <li class="page-item <?= $pageNumber === $history['page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc($historyUrl($pageNumber)) ?>"><?= $pageNumber ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $history['page'] >= $history['pages'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc($historyUrl(min($history['pages'], $history['page'] + 1))) ?>" aria-label="Berikutnya">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<div class="lq-info-panel mt-4">
    <i class="bi bi-info-circle-fill"></i>
    <div>
        <strong>Catatan Riwayat</strong>
        <p class="mb-0 small">Riwayat ini menampilkan instance checklist harian. Penyelesaian dengan verifikasi timer, foto, atau admin akan tetap berstatus menunggu sampai modul verifikasi tersedia pada fase berikutnya.</p>
    </div>
</div>

<?= $this->endSection() ?>
