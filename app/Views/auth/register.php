<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<div class="auth-card card shadow">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-person-plus display-4 text-electric-blue mb-2"></i>
            <h2 class="card-title fw-bold">Daftar Akun Baru</h2>
            <p class="text-muted">Mulai petualangan LifeQuest Anda</p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <ul class="mb-0 mt-2">
                    <?php $fieldLabels = ['username' => 'Username', 'email' => 'Email', 'password' => 'Kata Sandi', 'password_confirm' => 'Konfirmasi Kata Sandi']; ?>
                    <?php foreach (session()->getFlashdata('errors') as $field => $error): ?>
                        <li><?= esc($fieldLabels[$field] ?? ucfirst((string) $field)) ?>: <?= esc(is_array($error) ? $error[0] : $error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="/daftar" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= set_value('username') ?>" required autofocus
                       placeholder="contoh: user2026">
                <div class="form-text">Hanya huruf, angka, dan titik. Minimal 3 karakter.</div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= set_value('email') ?>" required
                       placeholder="nama@email.com">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Kata Sandi</label>
                <input type="password" class="form-control" id="password" name="password" required
                       placeholder="Minimal 8 karakter">
            </div>

            <div class="mb-4">
                <label for="password_confirm" class="form-label fw-semibold">Konfirmasi Kata Sandi</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="mb-2 text-muted small">Sudah punya akun?</p>
            <a href="/masuk" class="btn btn-outline-secondary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>