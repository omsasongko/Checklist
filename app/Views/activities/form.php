<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<?php
$isEdit = $activity !== null;
$repeatType = old('repeat_type', $activity['repeat_type'] ?? 'ONCE_DAILY');
$maxDaily = old('maximum_daily_completions', $activity['maximum_daily_completions'] ?? 1);
$maxWeekly = old('maximum_weekly_completions', $activity['maximum_weekly_completions'] ?? 7);
$cooldown = old('cooldown_seconds', $activity['cooldown_seconds'] ?? 0);
$selectedCategory = (int) old('category_id', $activity['category_id'] ?? 0);
$action = $isEdit ? '/aktivitas/' . (int) $activity['id'] . '/ubah' : '/aktivitas';
?>

<div class="mb-3">
    <a href="<?= $isEdit ? '/aktivitas/' . (int) $activity['id'] : '/aktivitas' ?>" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card lq-card">
            <div class="card-header lq-card-header">
                <div class="lq-eyebrow mb-1"><i class="bi bi-person-plus me-1"></i>Aktivitas Pribadi</div>
                <h1 class="h3 fw-bold mb-1"><?= $isEdit ? 'Ubah Aktivitas Pribadi' : 'Buat Aktivitas Pribadi' ?></h1>
                <p class="text-muted mb-0">Aktivitas pribadi hanya terlihat oleh Anda dan tidak memberikan XP atau RP pada Fase 2.</p>
            </div>
            <div class="card-body p-4">
                <form action="<?= esc($action) ?>" method="post" class="lq-form">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-semibold">Nama Aktivitas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" maxlength="255" required
                                   value="<?= esc(old('name', $activity['name'] ?? '')) ?>" placeholder="Contoh: Membaca 20 halaman buku">
                            <div class="form-text">Gunakan nama yang jelas dan mudah dikenali.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="category_id" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Pilih kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= $selectedCategory === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= esc($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000"
                                      placeholder="Jelaskan secara singkat bagaimana aktivitas ini dilakukan."><?= esc(old('description', $activity['description'] ?? '')) ?></textarea>
                            <div class="form-text">Opsional, maksimal 2000 karakter.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="repeat_type" class="form-label fw-semibold">Jenis Pengulangan <span class="text-danger">*</span></label>
                            <select class="form-select" id="repeat_type" name="repeat_type" required>
                                <option value="ONCE_DAILY" <?= $repeatType === 'ONCE_DAILY' ? 'selected' : '' ?>>Sekali sehari</option>
                                <option value="REPEATABLE" <?= $repeatType === 'REPEATABLE' ? 'selected' : '' ?>>Dapat diulang beberapa kali</option>
                                <option value="WEEKLY" <?= $repeatType === 'WEEKLY' ? 'selected' : '' ?>>Mingguan</option>
                            </select>
                            <div class="form-text">Jenis sesi terjadwal hanya tersedia untuk aktivitas bawaan administrator.</div>
                        </div>

                        <div class="col-md-3">
                            <label for="maximum_daily_completions" class="form-label fw-semibold">Batas Harian</label>
                            <input type="number" class="form-control" id="maximum_daily_completions" name="maximum_daily_completions"
                                   min="1" max="20" value="<?= esc($maxDaily) ?>" required>
                            <div class="form-text">1 sampai 20.</div>
                        </div>

                        <div class="col-md-3">
                            <label for="maximum_weekly_completions" class="form-label fw-semibold">Batas Mingguan</label>
                            <input type="number" class="form-control" id="maximum_weekly_completions" name="maximum_weekly_completions"
                                   min="1" max="100" value="<?= esc($maxWeekly) ?>" required>
                            <div class="form-text">Tidak boleh kurang dari batas harian.</div>
                        </div>

                        <div class="col-md-6" id="cooldown-field">
                            <label for="cooldown_seconds" class="form-label fw-semibold">Jeda Minimal (detik)</label>
                            <input type="number" class="form-control" id="cooldown_seconds" name="cooldown_seconds"
                                   min="0" max="86400" value="<?= esc($cooldown) ?>">
                            <div class="form-text">Isi 0 jika tidak ada jeda. Jeda hanya berlaku untuk aktivitas yang dapat diulang.</div>
                        </div>
                    </div>

                    <div class="lq-info-panel mt-4">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Catatan Fase 2</strong>
                            <p class="mb-0 small">Aktivitas pribadi menggunakan verifikasi pemeriksaan mandiri. XP/RP tetap 0 dan tidak ada perubahan peringkat. Anda dapat menonaktifkan aktivitas ini kapan saja tanpa menghapus riwayat.</p>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                        <a href="<?= $isEdit ? '/aktivitas/' . (int) $activity['id'] : '/aktivitas' ?>" class="btn btn-outline-light">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Buat Aktivitas' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
