<?php $pageTitle = t('profile'); ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<?php 
$defaultTab = isAdmin() ? 'edit' : 'bookings';
$activeTab = $_GET['tab'] ?? $defaultTab; 
?>

<section class="container py-4">
    <!-- Profile Header -->
    <div class="glass-card p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <?php if ($user['profile_image']): ?>
                    <img src="<?= baseUrl() . sanitize($user['profile_image']) ?>" class="profile-avatar" alt="Avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder mx-auto">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h3 class="fw-bold mb-1"><?= sanitize($user['full_name']) ?></h3>
                <p class="text-muted mb-1">
                    <i class="bi bi-envelope me-1"></i><?= sanitize($user['email']) ?>
                    <?php if ($user['phone_number']): ?>
                        <span class="ms-2"><i class="bi bi-telephone me-1"></i><?= sanitize($user['phone_number']) ?></span>
                    <?php endif; ?>
                </p>
                <p class="text-muted small mb-0">
                    <i class="bi bi-calendar3 me-1"></i><?= t('member_since') ?> <?= date('M Y', strtotime($user['created_at'])) ?>
                    <span class="badge bg-accent-subtle text-accent ms-2"><?= ucfirst($user['role']) ?></span>
                </p>
                <?php if ($user['bio']): ?>
                    <p class="mt-2 text-muted small"><?= sanitize($user['bio']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <?php if (!isAdmin()): ?>
                <div class="d-flex gap-3 justify-content-end">
                    <div class="profile-stat">
                        <div class="value"><?= count($bookings) ?></div>
                        <div class="label"><?= t('my_bookings') ?></div>
                    </div>
                    <div class="profile-stat">
                        <div class="value"><?= count($reviews) ?></div>
                        <div class="label"><?= t('my_reviews') ?></div>
                    </div>
                    <div class="profile-stat">
                        <div class="value"><?= count($wishlist) ?></div>
                        <div class="label"><?= t('wishlist') ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs-custom mb-4">
        <?php if (!isAdmin()): ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'bookings' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=profile&tab=bookings">
                <i class="bi bi-calendar3 me-1"></i><?= t('my_bookings') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'reviews' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=profile&tab=reviews">
                <i class="bi bi-chat-square-text me-1"></i><?= t('my_reviews') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'wishlist' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=profile&tab=wishlist">
                <i class="bi bi-heart me-1"></i><?= t('my_wishlist') ?>
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'edit' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=profile&tab=edit">
                <i class="bi bi-pencil-square me-1"></i><?= t('edit_profile') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'security' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=profile&tab=security">
                <i class="bi bi-shield-lock me-1"></i><?= t('two_fa_security') ?>
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <?php if ($activeTab === 'bookings'): ?>
        <?php if (empty($bookings)): ?>
            <div class="glass-card p-5 text-center">
                <i class="bi bi-calendar-x display-3 text-muted d-block mb-3"></i>
                <p class="text-muted"><?= t('no_bookings_yet') ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive glass-card p-3">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= t('asset') ?></th>
                            <th><?= t('from_date') ?></th>
                            <th><?= t('to_date') ?></th>
                            <th><?= t('total') ?></th>
                            <th><?= t('status') ?></th>
                            <th><?= t('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $b['asset_id'] ?>" class="text-accent fw-semibold">
                                        <?= sanitize($b['asset_title']) ?>
                                    </a>
                                    <br><small class="text-muted"><?= t($b['category']) ?></small>
                                </td>
                                <td><small><?= date('M j, Y g:i A', strtotime($b['start_time'])) ?></small></td>
                                <td><small><?= date('M j, Y g:i A', strtotime($b['end_time'])) ?></small></td>
                                <td><strong>$<?= number_format($b['total_price'], 2) ?></strong></td>
                                <td>
                                    <?php
                                    $statusClasses = ['confirmed' => 'bg-success', 'pending' => 'bg-warning text-dark', 'cancelled' => 'bg-danger'];
                                    ?>
                                    <span class="badge <?= $statusClasses[$b['status']] ?? 'bg-secondary' ?>">
                                        <?= t($b['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($b['status'] !== 'cancelled'): ?>
                                        <button class="btn btn-sm btn-outline-danger cancel-booking-btn"
                                                data-booking-id="<?= $b['booking_id'] ?>">
                                            <i class="bi bi-x-circle me-1"></i><?= t('cancel') ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php elseif ($activeTab === 'reviews'): ?>
        <?php if (empty($reviews)): ?>
            <div class="glass-card p-5 text-center">
                <i class="bi bi-chat-square display-3 text-muted d-block mb-3"></i>
                <p class="text-muted"><?= t('no_reviews_yet') ?></p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($reviews as $r): ?>
                    <div class="col-md-6">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <strong><?= sanitize($r['asset_title']) ?></strong>
                                <span class="text-warning">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-1"><?= sanitize($r['comment']) ?></p>
                            <small class="text-muted"><?= timeAgo($r['created_at']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($activeTab === 'wishlist'): ?>
        <?php if (empty($wishlist)): ?>
            <div class="glass-card p-5 text-center">
                <i class="bi bi-heart display-3 text-muted d-block mb-3"></i>
                <p class="text-muted"><?= t('empty_wishlist') ?></p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($wishlist as $wa): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="asset-card card h-100 border-0 shadow-sm">
                            <div class="asset-card-img-wrapper">
                                <?php if ($wa['image_url']): ?>
                                    <img src="<?= baseUrl() . sanitize($wa['image_url']) ?>" class="card-img-top" loading="lazy" alt="">
                                <?php else: ?>
                                    <div class="card-img-top placeholder-img d-flex align-items-center justify-content-center">
                                        <i class="bi <?= categoryIcon($wa['category']) ?> display-3 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="category-badge badge"><?= t($wa['category']) ?></span>
                            </div>
                            <div class="card-body">
                                <h6 class="fw-bold"><?= sanitize($wa['title']) ?></h6>
                                <p class="text-accent fw-semibold"><?= getAssetPrice($wa) ?></p>
                                <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $wa['asset_id'] ?>"
                                   class="btn btn-sm btn-outline-accent w-100"><?= t('view_details') ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($activeTab === 'edit'): ?>
        <div class="glass-card p-4 col-md-8 mx-auto">
            <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2"></i><?= t('edit_profile') ?></h5>
            <form method="POST" action="<?= baseUrl() ?>index.php?page=profile" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="edit-name" class="form-label fw-semibold"><?= t('full_name') ?></label>
                    <input type="text" class="form-control" id="edit-name" name="full_name"
                           value="<?= sanitize($user['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="edit-phone" class="form-label fw-semibold"><?= t('phone_number') ?></label>
                    <input type="tel" class="form-control" id="edit-phone" name="phone_number"
                           value="<?= sanitize($user['phone_number'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="edit-bio" class="form-label fw-semibold"><?= t('bio') ?></label>
                    <textarea class="form-control" id="edit-bio" name="bio" rows="3"
                              placeholder="<?= t('bio_placeholder') ?>"><?= sanitize($user['bio'] ?? '') ?></textarea>
                </div>
                <div class="mb-4">
                    <label for="edit-avatar" class="form-label fw-semibold"><?= t('profile_image') ?></label>
                    <input type="file" class="form-control" id="edit-avatar" name="profile_image" accept="image/*">
                </div>
                <button type="submit" class="btn btn-accent">
                    <i class="bi bi-check-lg me-1"></i><?= t('save_changes') ?>
                </button>
            </form>
        </div>

    <?php elseif ($activeTab === 'security'): ?>
        <div class="glass-card p-4 col-md-6 mx-auto text-center">
            <div style="font-size: 4rem;">🔐</div>
            <h5 class="fw-bold mt-2"><?= t('two_fa_security') ?></h5>
            <p class="text-muted mb-4">
                <?= $user['two_fa_enabled']
                    ? 'Two-Factor Authentication is currently <strong class="text-success">enabled</strong>. A code will be sent to your email on every login.'
                    : 'Two-Factor Authentication is currently <strong class="text-danger">disabled</strong>. Enable it for extra security.' ?>
            </p>
            <button class="btn <?= $user['two_fa_enabled'] ? 'btn-outline-danger' : 'btn-accent' ?>" id="toggle-2fa-btn">
                <i class="bi bi-shield-lock me-1"></i>
                <?= $user['two_fa_enabled'] ? t('disable_2fa') : t('enable_2fa') ?>
            </button>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
