<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-6 mb-4 mb-md-0">
                <h1 class="display-4 fw-bold mb-3">
                    LifeQuest <span class="text-gold">RPG</span>
                </h1>
                <p class="lead text-muted mb-4">
                    Ubah aktivitas harian Anda menjadi pencapaian yang berarti, terukur, dan berimbal.
                    Bangun kebiasaan positif, tingkatkan produktivitas, dan bertumbuh melalui permainan harian.
                </p>
                <div class="d-grid d-md-flex justify-content-md-start gap-3">
                    <a href="/daftar" class="btn btn-primary btn-lg px-5 py-3">
                        <i class="bi bi-person-plus me-2"></i>Mulai Gratis
                    </a>
                    <a href="/masuk" class="btn btn-secondary btn-lg px-5 py-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-charcoal">
                    <div class="card-body p-4">
                        <h5 class="card-title text-electric-blue fw-bold"><i class="bi bi-bootstrap-fill me-2"></i>Fitur Utama</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-electric-blue me-2"></i>
                                <span>Checklist Aktivitas Harian</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-electric-blue me-2"></i>
                                <span>Experience Points (XP) &amp; Rank Points (RP)</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-electric-blue me-2"></i>
                                <span>Tingkat Lifetime &amp; Peringkat Kompetitif</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-electric-blue me-2"></i>
                                <span>Pencapaian &amp; Badge</span>
                            </li>
                            <li class="mb-0 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-electric-blue me-2"></i>
                                <span>Musim Kompetitif 60 Hari &amp; Peringkat</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-charcoal">
    <div class="container">
        <h2 class="text-center text-gold fw-bold mb-5">Bagaimana LifeQuest Bekerja</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 bg-surface border-0">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-list-task display-4 text-purple"></i>
                        </div>
                        <h5 class="fw-bold">1. Pilih Aktivitas</h5>
                        <p class="text-muted small">
                            Pilih dari katalog aktivitas harian atau buat aktivitas pribadi Anda sendiri.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 bg-surface border-0">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-clock display-4 text-purple"></i>
                        </div>
                        <h5 class="fw-bold">2. Selesaikan Aktivitas</h5>
                        <p class="text-muted small">
                            Lakukan aktivitas sesuai jadwal dan tandai sebagai selesai.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 bg-surface border-0">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-trophy display-4 text-gold"></i>
                        </div>
                        <h5 class="fw-bold">3. Dapatkan Imbalan</h5>
                        <p class="text-muted small">
                            Kumpulkan XP dan RP, naik tingkat, dan naik peringkat kompetitif.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>