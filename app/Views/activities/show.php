<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<?php
$currentUserId = (int) (service('auth')->user()->id ?? 0);
$canModify = $activity['activity_type'] === 'CUSTOM' && (int) ($activity['created_by_user_id'] ?? 0) === $currentUserId;
$isCustom = $activity['activity_type'] === 'CUSTOM';
?>

<div class="mb-3">
    <a href="/aktivitas" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i>Kembali ke Katalog</a>
</div>

<div class="card lq-card lq-detail-hero mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
            <div class="d-flex gap-3">
                <span class="lq-activity-icon lq-activity-icon-lg"><i class="bi <?= esc($activity['category_icon'] ?: 'bi-activity') ?>"></i></span>
                <div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <span class="badge lq-badge <?= $isCustom ? 'lq-badge-purple' : 'lq-badge-muted' ?>"><?= esc(lq_activity_type_label($activity['activity_type'])) ?></span>
                        <span class="badge lq-badge lq-badge-blue"><?= esc(lq_repeat_label($activity['repeat_type'])) ?></span>
                        <span class="badge lq-badge lq-badge-muted"><?= esc($activity['category_name'] ?: 'Lainnya') ?></span>
                    </div>
                    <h1 class="h2 fw-bold mb-2"><?= esc($activity['name']) ?></h1>
                    <p class="text-muted mb-0"><?= esc($activity['description'] ?: 'Belum ada deskripsi aktivitas.') ?></p>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-start gap-2">
                <a href="/jadwal?aktivitas=<?= (int) $activity['id'] ?>" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Jadwalkan</a>
                <?php if ($canModify): ?>
                    <a href="/aktivitas/<?= (int) $activity['id'] ?>/ubah" class="btn btn-outline-light"><i class="bi bi-pencil me-1"></i>Ubah</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card lq-card mb-4">
            <div class="card-header lq-card-header">
                <h2 class="h5 fw-bold mb-0"><i class="bi bi-info-circle me-2 text-electric-blue"></i>Detail Aktivitas</h2>
            </div>
            <div class="card-body">
                <dl class="row lq-detail-list mb-0">
                    <dt class="col-sm-5">Kode Aktivitas</dt>
                    <dd class="col-sm-7"><code><?= esc($activity['activity_code']) ?></code></dd>

                    <dt class="col-sm-5">Grup Reward</dt>
                    <dd class="col-sm-7"><?= esc($activity['reward_group_name'] ?: 'Tidak ada') ?></dd>

                    <dt class="col-sm-5">Kebijakan Verifikasi</dt>
                    <dd class="col-sm-7"><?= esc(lq_policy_label($activity['policy_code'])) ?></dd>

                    <dt class="col-sm-5">Referensi XP</dt>
                    <dd class="col-sm-7"><span class="text-gold fw-semibold"><?= (int) $activity['default_xp'] ?> XP</span></dd>

                    <dt class="col-sm-5">Referensi RP</dt>
                    <dd class="col-sm-7"><span class="text-purple fw-semibold"><?= (int) $activity['default_rp'] ?> RP</span></dd>

                    <dt class="col-sm-5">Batas Harian</dt>
                    <dd class="col-sm-7"><?= (int) $activity['maximum_daily_completions'] ?> penyelesaian</dd>

                    <dt class="col-sm-5">Batas Mingguan</dt>
                    <dd class="col-sm-7"><?= (int) $activity['maximum_weekly_completions'] ?> penyelesaian</dd>

                    <dt class="col-sm-5">Jeda Minimal</dt>
                    <dd class="col-sm-7"><?= (int) $activity['cooldown_seconds'] > 0 ? (int) $activity['cooldown_seconds'] . ' detik' : 'Tidak ada' ?></dd>

                    <dt class="col-sm-5">Durasi Minimum</dt>
                    <dd class="col-sm-7"><?= $activity['minimum_duration_seconds'] !== null ? (int) $activity['minimum_duration_seconds'] . ' detik' : 'Tidak ada' ?></dd>
                </dl>
            </div>
        </div>

        <div class="lq-info-panel">
            <i class="bi bi-stars"></i>
            <div>
                <strong>XP dan RP belum diberikan</strong>
                <p class="mb-0 small">Nilai di atas adalah referensi konfigurasi. Fase 2 hanya mencatat penyelesaian aktivitas untuk pelacakan pribadi; mesin reward belum dijalankan.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card lq-card mb-4">
            <div class="card-header lq-card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold mb-0"><i class="bi bi-clock me-2 text-purple"></i>Sesi Terjadwal</h2>
                <span class="badge lq-badge lq-badge-muted"><?= count($sessions) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if ($sessions === []): ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-calendar2-minus fs-4 d-block mb-2"></i>
                        Aktivitas ini tidak memiliki sesi terjadwal. Checklist mengikuti pola pengulangan jadwal Anda.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush lq-session-list">
                        <?php foreach ($sessions as $session): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold"><?= esc($session['session_name']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-clock me-1"></i><?= esc(substr($session['scheduled_start'], 0, 5)) ?> - <?= esc(substr($session['scheduled_end'], 0, 5)) ?></div>
                                </div>
                                <div class="text-end small">
                                    <span class="text-gold"><?= (int) $session['xp_value'] ?> XP</span><br>
                                    <span class="text-purple"><?= (int) $session['rp_value'] ?> RP</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canModify): ?>
            <div class="card lq-card lq-danger-zone">
                <div class="card-body">
                    <h2 class="h6 fw-bold"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Nonaktifkan Aktivitas</h2>
                    <p class="small text-muted">Aktivitas pribadi akan disembunyikan dari katalog dan jadwal terkait dinonaktifkan. Riwayat penyelesaian tidak dihapus.</p>
                    <form action="/aktivitas/<?= (int) $activity['id'] ?>/nonaktifkan" method="post" data-confirm="Nonaktifkan aktivitas pribadi ini? Riwayat penyelesaian tetap disimpan.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle me-1"></i>Nonaktifkan</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
