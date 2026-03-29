<?php $pageTitle = t('notifications'); ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-bell me-2 text-accent"></i><?= t('notifications') ?></h2>
        <?php if (!empty($notifications)): ?>
            <button class="btn btn-sm btn-outline-accent" id="mark-all-read-btn">
                <i class="bi bi-check-all me-1"></i><?= t('mark_all_read') ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="glass-card overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="p-5 text-center">
                <i class="bi bi-bell-slash display-3 text-muted d-block mb-3"></i>
                <p class="text-muted"><?= t('no_notifications') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notification-item <?= $n['is_read'] ? '' : 'unread' ?>"
                     data-notification-id="<?= $n['notification_id'] ?>">
                    <div class="notification-icon">
                        <?php
                        $typeIcons = [
                            'booking' => 'bi-calendar-check', 'review' => 'bi-chat-square-text',
                            'system' => 'bi-info-circle', 'report' => 'bi-flag', 'approval' => 'bi-check-circle'
                        ];
                        ?>
                        <i class="bi <?= $typeIcons[$n['type']] ?? 'bi-bell' ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <strong><?= sanitize($n['title']) ?></strong>
                            <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
                        </div>
                        <p class="text-muted small mb-0"><?= sanitize($n['message']) ?></p>
                        <?php if ($n['link']): ?>
                            <a href="<?= baseUrl() . sanitize($n['link']) ?>" class="small text-accent">
                                <?= t('view_details') ?> <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <button class="btn btn-sm btn-outline-accent mark-read-btn"
                                data-id="<?= $n['notification_id'] ?>" title="Mark as read">
                            <i class="bi bi-check"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
