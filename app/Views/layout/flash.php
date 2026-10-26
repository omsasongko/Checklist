<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show lq-alert" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show lq-alert" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show lq-alert" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i><?= esc(session()->getFlashdata('warning')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show lq-alert" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i><?= esc(session()->getFlashdata('info')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show lq-alert" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>Periksa kembali data yang Anda masukkan.
        <ul class="mb-0 mt-2">
            <?php foreach ((array) session()->getFlashdata('errors') as $field => $error): ?>
                <?php $message = is_array($error) ? reset($error) : $error; ?>
                <li><?= esc(is_string($field) ? ucfirst($field) . ': ' : '') ?><?= esc((string) $message) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>
