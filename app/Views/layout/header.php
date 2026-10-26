<nav class="navbar navbar-expand-lg navbar-dark py-3">
    <div class="container">
        <?php $currentUser = service('auth')->user(); ?>
        <?php $currentPath = service('request')->getUri()->getPath(); ?>
        <a class="navbar-brand" href="<?= $currentUser !== null ? '/beranda' : '/' ?>">
            <i class="bi bi-lightning-charge-fill me-2"></i>LifeQuest
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($currentUser !== null): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPath === '/beranda' ? 'active' : '' ?>" href="/beranda">
                            <i class="bi bi-speedometer2 me-1"></i>Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($currentPath, '/aktivitas') ? 'active' : '' ?>" href="/aktivitas">
                            <i class="bi bi-grid me-1"></i>Katalog Aktivitas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($currentPath, '/jadwal') ? 'active' : '' ?>" href="/jadwal">
                            <i class="bi bi-calendar2-week me-1"></i>Jadwal Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($currentPath, '/checklist') ? 'active' : '' ?>" href="/checklist">
                            <i class="bi bi-check2-square me-1"></i>Checklist Harian
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($currentPath, '/riwayat') ? 'active' : '' ?>" href="/riwayat">
                            <i class="bi bi-clock-history me-1"></i>Riwayat
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPath === '/' ? 'active' : '' ?>" href="/">
                            <i class="bi bi-house-fill me-1"></i>Beranda
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($currentUser !== null): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i><?= esc($currentUser->username ?? 'Pengguna') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">Fase 2 &mdash; Aktivitas Harian</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="/keluar" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-1"></i>Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/masuk">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary px-3 ms-lg-2" href="/daftar">
                            <i class="bi bi-person-plus me-1"></i>Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
