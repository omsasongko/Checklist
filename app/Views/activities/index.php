<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<div class="lq-page-head d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <div class="lq-eyebrow"><i class="bi bi-grid me-1"></i>Katalog Aktivitas</div>
        <h1 class="h2 fw-bold mb-1">Pilih Aktivitas Harian</h1>
        <p class="text-muted mb-0">Gunakan aktivitas bawaan atau buat kebiasaan pribadi, lalu tambahkan ke jadwal Anda.</p>
    </div>
    <a href="/aktivitas/baru" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Aktivitas Pribadi</a>
</div>

<div class="card lq-card mb-4">
    <div class="card-body">
        <form action="/aktivitas" method="get" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="q" class="form-label small fw-semibold">Cari Aktivitas</label>
                <div class="input-group">
                    <span class="input-group-text bg-charcoal border-secondary text-muted"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="q" name="q" value="<?= esc($filters['q']) ?>" placeholder="Nama, deskripsi, atau kode aktivitas">
                </div>
            </div>
            <div class="col-md-4">
                <label for="kategori" class="form-label small fw-semibold">Kategori</label>
                <select class="form-select" id="kategori" name="kategori">
                    <option value="0">Semua kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= esc($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="/aktivitas" class="btn btn-outline-light" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted small"><i class="bi bi-collection me-1"></i><?= count($activities) ?> aktivitas ditemukan</div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($categories as $category): ?>
            <a href="/aktivitas?kategori=<?= (int) $category['id'] ?>" class="badge lq-category-chip text-decoration-none <?= (int) $filters['category_id'] === (int) $category['id'] ? 'active' : '' ?>">
                <i class="bi <?= esc($category['icon'] ?: 'bi-tag') ?> me-1"></i><?= esc($category['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($activities === []): ?>
    <div class="card lq-card">
        <div class="lq-empty-state">
            <i class="bi bi-search"></i>
            <h2 class="h5 fw-bold mt-3">Aktivitas tidak ditemukan</h2>
            <p class="text-muted small mb-3">Coba kata kunci atau kategori lain, atau buat aktivitas pribadi baru.</p>
            <a href="/aktivitas" class="btn btn-outline-light me-1">Reset Filter</a>
            <a href="/aktivitas/baru" class="btn btn-primary">Buat Aktivitas</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($activities as $activity): ?>
            <?php $isCustom = $activity['activity_type'] === 'CUSTOM'; ?>
            <div class="col-md-6 col-xl-4">
                <article class="card lq-card lq-activity-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <span class="lq-activity-icon"><i class="bi <?= esc($activity['category_icon'] ?: 'bi-activity') ?>"></i></span>
                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                <?php if ($isCustom): ?>
                                    <span class="badge lq-badge lq-badge-purple">Pribadi</span>
                                <?php else: ?>
                                    <span class="badge lq-badge lq-badge-muted">Bawaan</span>
                                <?php endif; ?>
                                <span class="badge lq-badge lq-badge-blue"><?= esc(lq_repeat_label($activity['repeat_type'])) ?></span>
                            </div>
                        </div>

                        <h2 class="h6 fw-bold mb-1"><?= esc($activity['name']) ?></h2>
                        <div class="small text-muted mb-2"><i class="bi bi-tag me-1"></i><?= esc($activity['category_name'] ?: 'Lainnya') ?></div>
                        <p class="text-muted small flex-grow-1"><?= esc(lq_excerpt($activity['description'], 130)) ?></p>

                        <div class="d-flex flex-wrap gap-2 mb-3 lq-meta">
                            <span><i class="bi bi-stars me-1"></i><?= (int) $activity['default_xp'] ?> XP</span>
                            <span><i class="bi bi-gem me-1"></i><?= (int) $activity['default_rp'] ?> RP</span>
                            <span><i class="bi bi-shield-check me-1"></i><?= esc(lq_policy_label($activity['policy_code'])) ?></span>
                            <?php if ((int) $activity['active_session_count'] > 0): ?>
                                <span><i class="bi bi-clock me-1"></i><?= (int) $activity['active_session_count'] ?> sesi</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <a href="/aktivitas/<?= (int) $activity['id'] ?>" class="btn btn-sm btn-outline-light flex-fill">Detail</a>
                            <a href="/jadwal?aktivitas=<?= (int) $activity['id'] ?>" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-calendar-plus me-1"></i>Jadwalkan</a>
                            <?php if ($isCustom): ?>
                                <a href="/aktivitas/<?= (int) $activity['id'] ?>/ubah" class="btn btn-sm btn-outline-secondary" aria-label="Ubah aktivitas"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="lq-info-panel mt-4">
    <i class="bi bi-lightbulb-fill"></i>
    <div>
        <strong>Tips Katalog</strong>
        <p class="mb-0 small">Aktivitas pribadi hanya terlihat oleh Anda. Aktivitas dengan sesi terjadwal seperti merapikan ruang hunian akan menghasilkan lebih dari satu item checklist per hari.</p>
    </div>
</div>

<?= $this->endSection() ?>
