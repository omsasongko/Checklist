<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<?php
$progress = $checklist['progress'];
$previewItems = array_slice($checklist['items'], 0, 5);
?>

<div class="lq-page-head d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <div class="lq-eyebrow"><i class="bi bi-stars me-1"></i>Fase 2 &mdash; Aktivitas Harian</div>
        <h1 class="h2 fw-bold mb-1">Selamat Datang, <?= esc($user->username ?? 'Pengguna') ?>!</h1>
        <p class="text-muted mb-0"><?= esc(lq_date_id($today, true)) ?> &middot; Zona waktu Asia/Jakarta</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/checklist" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i>Buka Checklist</a>
        <a href="/jadwal" class="btn btn-outline-light"><i class="bi bi-calendar2-week me-1"></i>Atur Jadwal</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card lq-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Progres Hari Ini</p>
                        <h2 class="fw-bold text-electric-blue mb-0"><span id="lq-progress-label"><?= (int) $progress['percent'] ?></span>%</h2>
                    </div>
                    <i class="bi bi-activity fs-3 text-electric-blue"></i>
                </div>
                <div class="lq-progress mt-3" role="progressbar" aria-label="Progres checklist hari ini" aria-valuenow="<?= (int) $progress['percent'] ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="lq-progress-fill" id="lq-progress-fill" style="width: <?= (int) $progress['percent'] ?>%"></div>
                </div>
                <div class="small text-muted mt-2" id="lq-progress-caption"><?= (int) $progress['completed'] ?> dari <?= (int) $progress['total'] ?> aktivitas selesai</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card lq-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Aktivitas Selesai</p>
                        <h2 class="fw-bold text-purple mb-0"><?= (int) $progress['completed'] ?><span class="fs-6 text-muted"> / <?= (int) $progress['total'] ?></span></h2>
                    </div>
                    <i class="bi bi-check2-circle fs-3 text-purple"></i>
                </div>
                <p class="small text-muted mb-0 mt-3">Catatan pribadi tanpa peringkat kompetitif.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card lq-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">XP &amp; RP Fase 2</p>
                        <h2 class="fw-bold text-gold mb-0">0 <span class="fs-6 text-muted">XP</span> &middot; 0 <span class="fs-6 text-muted">RP</span></h2>
                    </div>
                    <i class="bi bi-stars fs-3 text-gold"></i>
                </div>
                <p class="small text-muted mb-0 mt-3">Pemberian XP/RP belum aktif pada Fase 2.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card lq-card">
            <div class="card-header lq-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h5 fw-bold mb-0"><i class="bi bi-list-check me-2 text-electric-blue"></i>Checklist Harian</h2>
                    <div class="small text-muted mt-1"><?= esc(lq_date_id($checklist['date'], true)) ?></div>
                </div>
                <a href="/checklist" class="btn btn-sm btn-outline-primary">Lihat semua</a>
            </div>
            <div class="card-body p-0">
                <?php if ($previewItems === []): ?>
                    <div class="lq-empty-state">
                        <i class="bi bi-clipboard-plus"></i>
                        <h3 class="h6 fw-bold mt-3">Belum ada aktivitas untuk hari ini</h3>
                        <p class="text-muted small mb-3">Pilih aktivitas dari katalog lalu tambahkan ke jadwal Anda.</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="/aktivitas" class="btn btn-sm btn-primary"><i class="bi bi-grid me-1"></i>Buka Katalog</a>
                            <a href="/jadwal" class="btn btn-sm btn-outline-light"><i class="bi bi-calendar-plus me-1"></i>Buat Jadwal</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="lq-check-list">
                        <?php foreach ($previewItems as $item): ?>
                            <?= $this->setData([
                                'item' => $item,
                                'compact' => true,
                                'listDate' => $checklist['date'],
                                'listTimezone' => $checklist['timezone'],
                            ])->include('checklist/_item') ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($checklist['items']) > count($previewItems)): ?>
                        <div class="p-3 text-center border-top border-dark-subtle">
                            <a href="/checklist" class="small text-electric-blue text-decoration-none">+ <?= count($checklist['items']) - count($previewItems) ?> aktivitas lainnya</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card lq-card mb-4">
            <div class="card-header lq-card-header">
                <h2 class="h5 fw-bold mb-0"><i class="bi bi-lightning-charge me-2 text-gold"></i>Aksi Cepat</h2>
            </div>
            <div class="list-group list-group-flush lq-action-list">
                <a href="/aktivitas" class="list-group-item list-group-item-action">
                    <span class="lq-action-icon"><i class="bi bi-grid"></i></span>
                    <span><strong>Katalog Aktivitas</strong><small class="d-block text-muted">Jelajahi aktivitas bawaan dan pribadi</small></span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
                <a href="/aktivitas/baru" class="list-group-item list-group-item-action">
                    <span class="lq-action-icon"><i class="bi bi-plus-lg"></i></span>
                    <span><strong>Buat Aktivitas Pribadi</strong><small class="d-block text-muted">Tambahkan kebiasaan Anda sendiri</small></span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
                <a href="/jadwal" class="list-group-item list-group-item-action">
                    <span class="lq-action-icon"><i class="bi bi-calendar2-week"></i></span>
                    <span><strong>Jadwal Saya</strong><small class="d-block text-muted">Atur pengulangan dan zona waktu</small></span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
                <a href="/riwayat" class="list-group-item list-group-item-action">
                    <span class="lq-action-icon"><i class="bi bi-clock-history"></i></span>
                    <span><strong>Riwayat Aktivitas</strong><small class="d-block text-muted">Tinjau catatan penyelesaian</small></span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </div>
        </div>

        <div class="lq-info-panel">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                <strong>Catatan Fase 2</strong>
                <p class="mb-0 small">Penyelesaian aktivitas hanya dicatat untuk pelacakan pribadi. XP, RP, peringkat, dan musim kompetisi belum dihitung pada fase ini.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
