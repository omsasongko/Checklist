<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<div class="auth-card card shadow">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-key display-4 text-electric-blue mb-2"></i>
            <h2 class="card-title fw-bold">Lupa Kata Sandi</h2>
            <p class="text-muted">Pemulihan kata sandi belum tersedia</p>
        </div>

        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Fitur ini belum tersedia.</strong>
            Sistem pengiriman email belum dikonfigurasi, sehingga LifeQuest belum dapat
            mengirim tautan reset kata sandi.
        </div>

        <p class="text-muted small mb-4">
            Jika Anda tidak dapat mengakses akun, silakan hubungi administrator LifeQuest.
            Fitur pemulihan kata sandi akan diaktifkan setelah sistem pengiriman email siap.
        </p>

        <button type="button" class="btn btn-secondary w-100 py-2 fw-semibold" disabled>
            <i class="bi bi-envelope me-2"></i>Kirim Tautan Reset (Belum Tersedia)
        </button>

        <div class="text-center mt-4">
            <a href="/masuk" class="text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke halaman masuk
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
