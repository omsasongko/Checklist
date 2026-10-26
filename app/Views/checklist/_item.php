<?php
$compact = $compact ?? false;
$listDate = $listDate ?? ($item['activity_date'] ?? '');
$listTimezone = $listTimezone ?? 'Asia/Jakarta';
$statusClass = $item['is_completed'] ? 'is-complete' : '';
?>
<div class="lq-check-item <?= $statusClass ?>" data-instance-id="<?= (int) $item['id'] ?>">
    <div class="lq-check-mark" aria-hidden="true">
        <?php if ($item['is_completed']): ?>
            <i class="bi bi-check-lg"></i>
        <?php else: ?>
            <i class="bi <?= esc($item['category_icon'] ?: 'bi-circle') ?>"></i>
        <?php endif; ?>
    </div>

    <div class="flex-grow-1 min-w-0">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h6 class="mb-0 fw-semibold"><?= esc($item['activity_name']) ?></h6>
            <?php if ($item['session_name']): ?>
                <span class="badge lq-badge lq-badge-purple"><?= esc($item['session_name']) ?></span>
            <?php elseif ($item['repeat_type'] === 'REPEATABLE' && $item['occurrence_number'] !== null): ?>
                <span class="badge lq-badge lq-badge-blue">Sesi <?= (int) $item['occurrence_number'] ?></span>
            <?php endif; ?>
            <span class="badge lq-badge lq-badge-muted"><?= esc(lq_repeat_label($item['repeat_type'])) ?></span>
            <?php if ($item['is_completed']): ?>
                <span class="badge lq-badge lq-badge-success"><i class="bi bi-check-circle-fill me-1"></i><?= esc(lq_completion_label($item['completion_status'])) ?></span>
                <?php if ($item['verification_status'] === 'PENDING'): ?>
                    <span class="badge lq-badge lq-badge-warning"><?= esc(lq_verification_label($item['verification_status'])) ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (! $compact && $item['activity_description']): ?>
            <p class="text-muted small mb-2"><?= esc($item['activity_description']) ?></p>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center gap-2 lq-meta">
            <?php if ($item['schedule_label']): ?>
                <span><i class="bi bi-clock me-1"></i><?= esc($item['schedule_label']) ?></span>
            <?php endif; ?>
            <span><i class="bi bi-tag me-1"></i><?= esc($item['category_name']) ?></span>
            <?php if (! $compact): ?>
                <span><i class="bi bi-shield-check me-1"></i><?= esc(lq_policy_label($item['policy_code'])) ?></span>
                <span class="lq-reference"><i class="bi bi-stars me-1"></i>Referensi <?= (int) $item['xp_reference'] ?> XP / <?= (int) $item['rp_reference'] ?> RP</span>
            <?php endif; ?>
        </div>

        <?php if ($item['is_completed'] && $item['completed_at_local']): ?>
            <div class="small text-muted mt-2">
                <i class="bi bi-check2 me-1"></i>Diselesaikan <?= esc(lq_date_id($item['completed_at_local'])) ?> pukul <?= esc(substr($item['completed_at_local'], 11, 5)) ?>
            </div>
        <?php endif; ?>

        <?php if (! $item['is_completed'] && ! $item['completion_allowed'] && $item['completion_reason']): ?>
            <div class="small text-warning mt-2"><i class="bi bi-info-circle me-1"></i><?= esc($item['completion_reason']) ?></div>
        <?php endif; ?>
    </div>

    <div class="lq-check-action">
        <?php if ($item['is_completed']): ?>
            <span class="lq-complete-check" title="Selesai"><i class="bi bi-check-lg"></i></span>
        <?php elseif ($item['completion_allowed']): ?>
            <form action="/checklist/<?= (int) $item['id'] ?>/selesai" method="post" class="js-complete-form">
                <?= csrf_field() ?>
                <input type="hidden" name="activity_id" value="<?= (int) $item['activity_id'] ?>">
                <input type="hidden" name="tanggal" value="<?= esc($listDate) ?>">
                <input type="hidden" name="zona" value="<?= esc($listTimezone) ?>">
                <button type="submit" class="btn btn-sm btn-primary js-complete-btn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                    <i class="bi bi-check2 me-1"></i><span class="js-btn-label">Selesaikan</span>
                </button>
            </form>
        <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                <i class="bi bi-lock me-1"></i>Selesaikan
            </button>
        <?php endif; ?>
    </div>
</div>
