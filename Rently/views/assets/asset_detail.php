<?php $pageTitle = sanitize($asset['title']); ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="container py-4">
    <a href="<?= baseUrl() ?>" class="btn btn-sm btn-outline-accent mb-3">
        <i class="bi bi-arrow-left me-1"></i><?= t('back_to_home') ?>
    </a>

    <div class="row g-4">
        <!-- Left: Images + Details -->
        <div class="col-lg-8">
            <!-- Image Gallery -->
            <div class="glass-card overflow-hidden mb-4">
                <?php
                $allImages = [];
                if ($asset['image_url']) $allImages[] = $asset['image_url'];
                foreach ($galleryImages as $gi) $allImages[] = $gi['image_url'];
                ?>
                <?php if (!empty($allImages)): ?>
                    <div class="gallery-main" id="gallery-main">
                        <img src="<?= strpos($allImages[0], 'http') === 0 ? sanitize($allImages[0]) : baseUrl() . sanitize($allImages[0]) ?>" alt="<?= sanitize($asset['title']) ?>" id="gallery-active-img">
                        <?php if (count($allImages) > 1): ?>
                            <button class="gallery-nav prev" onclick="galleryNav(-1)"><i class="bi bi-chevron-left"></i></button>
                            <button class="gallery-nav next" onclick="galleryNav(1)"><i class="bi bi-chevron-right"></i></button>
                        <?php endif; ?>
                    </div>
                    <?php if (count($allImages) > 1): ?>
                        <div class="gallery-thumbs px-3" id="gallery-thumbs">
                            <?php foreach ($allImages as $i => $img): ?>
                                <div class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                                     onclick="setGalleryImage(<?= $i ?>)">
                                    <img src="<?= strpos($img, 'http') === 0 ? sanitize($img) : baseUrl() . sanitize($img) ?>" alt="Gallery <?= $i + 1 ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="placeholder-img-lg d-flex align-items-center justify-content-center">
                        <i class="bi <?= categoryIcon($asset['category']) ?> display-1 text-muted"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- About -->
            <div class="glass-card p-4 mb-4">
                <h1 class="fw-bold mb-2"><?= sanitize($asset['title']) ?></h1>
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <span class="badge bg-accent-subtle text-accent"><?= formatCategory($asset['category']) ?></span>
                    <span class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= sanitize($asset['address'] ?? '') ?></span>
                    <?php if ($avgRating > 0): ?>
                        <span class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                            <strong class="text-body ms-1"><?= $avgRating ?></strong>
                            <small class="text-muted">(<?= count($reviews) ?>)</small>
                        </span>
                    <?php endif; ?>
                </div>

                <h5 class="fw-semibold"><?= t('about_listing') ?></h5>
                <p class="text-muted"><?= nl2br(sanitize($asset['description'] ?? '')) ?></p>

                <?php
                $extra = json_decode($asset['extra_fields'] ?? '{}', true);
                if (!empty($extra)):
                ?>
                <div class="row g-2 mt-3">
                    <?php foreach ($extra as $key => $val): ?>
                        <?php if (is_array($val)): ?>
                            <div class="col-12">
                                <span class="fw-semibold small text-muted text-capitalize"><?= str_replace('_', ' ', $key) ?>: </span>
                                <?php foreach ($val as $v): ?>
                                    <span class="badge bg-accent-subtle text-accent me-1"><?= sanitize($v) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (is_bool($val)): ?>
                            <div class="col-auto">
                                <span class="badge <?= $val ? 'bg-success' : 'bg-secondary' ?>"><?= str_replace('_', ' ', ucfirst($key)) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="col-auto">
                                <div class="stat-card p-2 text-center">
                                    <div class="fw-bold text-accent"><?= sanitize((string)$val) ?></div>
                                    <small class="text-muted text-capitalize"><?= str_replace('_', ' ', $key) ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Host Info -->
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-semibold mb-3"><?= t('hosted_by') ?> <?= sanitize($asset['owner_name']) ?></h5>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($asset['owner_avatar']): ?>
                        <img src="<?= baseUrl() . sanitize($asset['owner_avatar']) ?>" class="review-avatar" alt="Host">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder" style="width:50px;height:50px;font-size:1.2rem;border-width:2px;">
                            <?= strtoupper(substr($asset['owner_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="fw-semibold mb-0"><?= sanitize($asset['owner_name']) ?></p>
                        <?php if ($asset['owner_bio']): ?>
                            <p class="text-muted small mb-0"><?= sanitize(mb_substr($asset['owner_bio'], 0, 100)) ?>...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reviews -->
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-semibold mb-3">
                    <i class="bi bi-chat-square-text me-2"></i><?= t('reviews') ?>
                    <span class="badge bg-accent-subtle text-accent ms-2"><?= count($reviews) ?></span>
                </h5>

                <?php if (empty($reviews)): ?>
                    <p class="text-muted text-center py-3"><?= t('no_reviews_yet') ?></p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item rounded p-3 mb-2">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <?php if ($review['profile_image']): ?>
                                    <img src="<?= baseUrl() . sanitize($review['profile_image']) ?>" class="review-avatar" alt="">
                                <?php else: ?>
                                    <div class="profile-avatar-placeholder" style="width:35px;height:35px;font-size:0.9rem;border-width:2px;">
                                        <?= strtoupper(substr($review['full_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?= sanitize($review['full_name']) ?></strong>
                                    <span class="text-warning ms-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>" style="font-size:0.8rem;"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                                <small class="text-muted ms-auto"><?= timeAgo($review['created_at']) ?></small>
                            </div>
                            <p class="mb-0 text-muted small"><?= sanitize($review['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Add Review Form -->
                <?php if (isLoggedIn()): ?>
                    <div class="mt-4 pt-3" style="border-top: 1px solid var(--border-color);">
                        <h6 class="fw-semibold"><?= t('write_review') ?></h6>
                        <div class="star-rating mb-3" id="star-rating" data-rating="0">
                            <i class="bi bi-star fs-4" data-value="1"></i>
                            <i class="bi bi-star fs-4" data-value="2"></i>
                            <i class="bi bi-star fs-4" data-value="3"></i>
                            <i class="bi bi-star fs-4" data-value="4"></i>
                            <i class="bi bi-star fs-4" data-value="5"></i>
                        </div>
                        <textarea class="form-control mb-2" id="review-comment" rows="3"
                                  placeholder="<?= t('write_review') ?>"></textarea>
                        <button class="btn btn-accent btn-sm" id="submit-review-btn"
                                data-asset-id="<?= $asset['asset_id'] ?>">
                            <i class="bi bi-send me-1"></i><?= t('submit_review') ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Booking Card -->
        <div class="col-lg-4">
            <div class="glass-card p-4 mb-3 sticky-top" style="top: 90px;">
                <!-- Price -->
                <div class="text-center mb-3">
                    <h2 class="fw-800 text-accent mb-0"><?= getAssetPrice($asset) ?></h2>
                </div>

                <!-- Wishlist -->
                <div class="d-flex gap-2 mb-3">
                    <?php if (isLoggedIn()): ?>
                        <?php if (!isAdmin()): ?>
                        <button class="btn <?= $inWishlist ? 'btn-danger' : 'btn-outline-accent' ?> flex-fill" id="detail-wishlist-btn"
                                data-asset-id="<?= $asset['asset_id'] ?>">
                            <i class="bi bi-heart<?= $inWishlist ? '-fill' : '' ?> me-1"></i><?= t('wishlist') ?>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" title="<?= t('report_listing') ?>">
                            <i class="bi bi-flag"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Booking Form -->
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-1"></i><?= t('admin_cannot_book') ?>
                        </div>
                    <?php elseif ($_SESSION['user_id'] == $asset['owner_id']): ?>
                        <div class="alert alert-secondary text-center">
                            <i class="bi bi-person-check me-1"></i><?= t('cannot_book_own_asset') ?>
                        </div>
                    <?php else: ?>
                    <div class="booking-form">
                        <div class="mb-3">
                            <label for="book-start" class="form-label fw-semibold small">
                                <?= isHourlyCategory($asset['category']) ? t('start_datetime') : t('checkin_date') ?>
                            </label>
                            <input type="<?= isHourlyCategory($asset['category']) ? 'datetime-local' : 'date' ?>"
                                   class="form-control" id="book-start">
                        </div>
                        <div class="mb-3">
                            <label for="book-end" class="form-label fw-semibold small">
                                <?= isHourlyCategory($asset['category']) ? t('end_datetime') : t('checkout_date') ?>
                            </label>
                            <input type="<?= isHourlyCategory($asset['category']) ? 'datetime-local' : 'date' ?>"
                                   class="form-control" id="book-end">
                        </div>

                        <div class="price-preview p-3 mb-3 d-none" id="price-preview">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold"><?= t('estimated_total') ?></span>
                                <span class="fw-bold text-accent fs-5" id="estimated-total">$0.00</span>
                            </div>
                        </div>

                        <button class="btn btn-outline-accent w-100 mb-2" id="check-availability-btn"
                                data-asset-id="<?= $asset['asset_id'] ?>"
                                data-price-hour="<?= $asset['price_per_hour'] ?>"
                                data-price-day="<?= $asset['price_per_day'] ?>"
                                data-category="<?= $asset['category'] ?>">
                            <i class="bi bi-calendar-check me-1"></i><?= t('check_availability') ?>
                        </button>

                        <button class="btn btn-accent w-100 d-none" id="book-now-btn" data-asset-id="<?= $asset['asset_id'] ?>">
                            <i class="bi bi-credit-card me-1"></i><?= t('book_pay_now') ?>
                        </button>

                        <div id="availability-msg" class="mt-2 text-center small"></div>
                    </div>
                    <?php endif; ?>

                    <!-- Cancellation Policy -->
                    <div class="cancellation-policy p-3 mt-3">
                        <h6 class="fw-semibold small mb-2"><i class="bi bi-shield-check me-1"></i><?= t('cancellation_policy') ?></h6>
                        <div class="policy-item"><i class="bi bi-check-circle-fill"></i><span><?= t('free_cancel_48h') ?></span></div>
                        <div class="policy-item"><i class="bi bi-exclamation-circle-fill"></i><span><?= t('half_refund_24h') ?></span></div>
                        <div class="policy-item"><i class="bi bi-x-circle-fill"></i><span><?= t('no_refund_24h') ?></span></div>
                    </div>
                <?php else: ?>
                    <a href="<?= baseUrl() ?>index.php?page=login" class="btn btn-accent w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i><?= t('login_to_book') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Similar Listings -->
    <?php if (!empty($similarAssets)): ?>
    <div class="mt-5">
        <h4 class="fw-bold mb-3"><i class="bi bi-collection me-2"></i><?= t('similar_listings') ?></h4>
        <div class="row g-4">
            <?php foreach ($similarAssets as $sa): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="asset-card card h-100 border-0 shadow-sm">
                        <div class="asset-card-img-wrapper">
                            <?php if ($sa['image_url']): ?>
                                <img src="<?= strpos($sa['image_url'], 'http') === 0 ? sanitize($sa['image_url']) : baseUrl() . sanitize($sa['image_url']) ?>" class="card-img-top" alt="<?= sanitize($sa['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="card-img-top placeholder-img d-flex align-items-center justify-content-center">
                                    <i class="bi <?= categoryIcon($sa['category']) ?> display-3 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <span class="category-badge badge"><?= t($sa['category']) ?></span>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold"><?= sanitize($sa['title']) ?></h6>
                            <p class="text-accent fw-semibold"><?= getAssetPrice($sa) ?></p>
                            <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $sa['asset_id'] ?>"
                               class="btn btn-sm btn-outline-accent w-100"><?= t('view_details') ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<!-- Report Modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-flag me-2"></i><?= t('report_listing') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="report-reason" rows="4" placeholder="<?= t('reason') ?>..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('cancel') ?></button>
                <button type="button" class="btn btn-danger" id="submit-report-btn" data-asset-id="<?= $asset['asset_id'] ?>">
                    <i class="bi bi-flag me-1"></i><?= t('submit') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Gallery images data
const galleryImages = <?= json_encode(array_map(fn($img) => baseUrl() . $img, $allImages)) ?>;
let currentGalleryIndex = 0;

function setGalleryImage(index) {
    currentGalleryIndex = index;
    document.getElementById('gallery-active-img').src = galleryImages[index];
    document.querySelectorAll('.gallery-thumb').forEach((t, i) => {
        t.classList.toggle('active', i === index);
    });
}

function galleryNav(dir) {
    let newIndex = currentGalleryIndex + dir;
    if (newIndex < 0) newIndex = galleryImages.length - 1;
    if (newIndex >= galleryImages.length) newIndex = 0;
    setGalleryImage(newIndex);
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
