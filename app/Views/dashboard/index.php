<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h2 fw-bold">Selamat Datang, <?= esc($user->username ?? 'Pengguna') ?>!</h1>
        <p class="text-muted">Ini adalah halaman dashboard Anda. Berikut adalah ringkasan aktivitas dan peringkat Anda.</p>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card bg-surface border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Lifetime Level</p>
                        <h3 class="fw-bold text-electric-blue mb-0">1</h3>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-gold text-dark fw-bold">LVL 1</span>
                    </div>
                </div>
                <div class="xp-bar mt-3">
                    <div class="xp-bar-fill" style="width: 0%;"></div>
                </div>
                <div class="text-center mt-2">
                    <span class="text-muted small">0 / 100 XP</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card bg-surface border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Total XP</p>
                        <h3 class="fw-bold text-electric-blue mb-0">0</h3>
                    </div>
                    <div class="text-end">
                        <i class="bi bi-star-fill text-gold fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card bg-surface border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">RP Musim Ini</p>
                        <h3 class="fw-bold text-electric-blue mb-0">0</h3>
                    </div>
                    <div class="text-end">
                        <span class="rank-badge">Warrior</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card bg-surface border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Streak Saat Ini</p>
                        <h3 class="fw-bold text-electric-blue mb-0">0</h3>
                    </div>
                    <div class="text-end">
                        <span class="badge text-gold fw-bold">0 hari</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-surface border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title fw-bold mb-0"><i class="bi bi-list-task me-2 text-electric-blue"></i>Checklist Harian</h5>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-check display-4 text-muted"></i>
                    <p class="mt-3 text-muted">Belum ada aktivitas harian yang ditetapkan.</p>
                    <a href="#" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Aktivitas Pertama
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>