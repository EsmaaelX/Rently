<?php
$pageTitle = sanitize($asset['title']);
$maps = new GoogleMapsAPI();
$embedUrl = $maps->getEmbedUrl((float)$asset['latitude'], (float)$asset['longitude']);
$isHourly = ($asset['category'] === 'sport_venue');
?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="container py-5">
    <div class="row g-4">
        <!-- ─── Left Column: Asset Details ─── -->
        <div class="col-lg-7">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Home</a></li>
                    <li class="breadcrumb-item active"><?= sanitize($asset['title']) ?></li>
                </ol>
            </nav>

            <!-- Image -->
            <div class="asset-detail-img mb-4 rounded-4 overflow-hidden shadow">
                <?php if ($asset['image_url']): ?>
                    <img src="<?= baseUrl() . sanitize($asset['image_url']) ?>"
                         class="w-100" alt="<?= sanitize($asset['title']) ?>">
                <?php else: ?>
                    <div class="placeholder-img-lg d-flex align-items-center justify-content-center">
                        <?php
                        $icons = ['apartment' => 'bi-building', 'car' => 'bi-car-front', 'sport_venue' => 'bi-trophy'];
                        $icon = $icons[$asset['category']] ?? 'bi-image';
                        ?>
                        <i class="bi <?= $icon ?> display-1 text-muted"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-accent-subtle text-accent mb-2">
                            <?= ucfirst(str_replace('_', ' ', $asset['category'])) ?>
                        </span>
                        <h2 class="fw-bold mb-1"><?= sanitize($asset['title']) ?></h2>
                        <p class="text-muted mb-0">
                            <i class="bi bi-geo-alt me-1"></i><?= sanitize($asset['address'] ?: 'Location not specified') ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="fs-3 fw-bold text-accent">
                            $<?= number_format($isHourly ? $asset['price_per_hour'] : $asset['price_per_day'], 2) ?>
                        </div>
                        <small class="text-muted"><?= $isHourly ? 'per hour' : 'per day' ?></small>
                    </div>
                </div>

                <?php if ($asset['description']): ?>
                    <hr>
                    <h5 class="fw-semibold">About this listing</h5>
                    <p class="text-muted"><?= nl2br(sanitize($asset['description'])) ?></p>
                <?php endif; ?>

                <hr>
                <div class="row text-center">
                    <div class="col-4">
                        <i class="bi bi-person-fill text-accent fs-4"></i>
                        <p class="small mb-0 fw-semibold"><?= sanitize($asset['owner_name']) ?></p>
                        <small class="text-muted">Host</small>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-star-fill text-warning fs-4"></i>
                        <p class="small mb-0 fw-semibold"><?= $avgRating ?: 'N/A' ?></p>
                        <small class="text-muted">Rating</small>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-chat-dots-fill text-accent fs-4"></i>
                        <p class="small mb-0 fw-semibold"><?= count($reviews) ?></p>
                        <small class="text-muted">Reviews</small>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-semibold mb-3"><i class="bi bi-map me-2"></i>Location</h5>
                <div class="rounded-3 overflow-hidden">
                    <iframe src="<?= $embedUrl ?>" width="100%" height="300"
                            style="border:0;" loading="lazy" id="asset-map"></iframe>
                </div>
            </div>

            <!-- Reviews -->
            <div class="glass-card p-4" id="reviews-section">
                <h5 class="fw-semibold mb-3"><i class="bi bi-chat-quote me-2"></i>Reviews</h5>

                <?php if (empty($reviews)): ?>
                    <p class="text-muted">No reviews yet. Be the first!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item mb-3 p-3 rounded-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= sanitize($rev['full_name']) ?></strong>
                                <span>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $rev['rating'] ? '-fill text-warning' : '' ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <p class="text-muted mb-0 mt-1 small"><?= sanitize($rev['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Add Review Form -->
                <?php if (isLoggedIn()): ?>
                <hr>
                <form id="review-form" data-asset-id="<?= $asset['asset_id'] ?>">
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Your Rating</label>
                        <div class="star-rating" id="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star fs-4" data-rating="<?= $i ?>" role="button"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating-input" value="0">
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" name="comment" rows="2"
                                  placeholder="Write your review..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-accent">
                        <i class="bi bi-send me-1"></i>Submit Review
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Right Column: Booking Form ─── -->
        <div class="col-lg-5">
            <div class="glass-card p-4 shadow-lg sticky-top" style="top: 6rem;">
                <h4 class="fw-bold mb-3">
                    <i class="bi bi-calendar-check me-2"></i>Book This <?= ucfirst(str_replace('_', ' ', $asset['category'])) ?>
                </h4>

                <form id="booking-form" data-asset-id="<?= $asset['asset_id'] ?>" data-category="<?= $asset['category'] ?>">
                    <div class="mb-3">
                        <label for="book-start" class="form-label fw-semibold">
                            <?= $isHourly ? 'Start Date & Time' : 'Check-in Date' ?>
                        </label>
                        <input type="datetime-local" class="form-control" id="book-start" name="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="book-end" class="form-label fw-semibold">
                            <?= $isHourly ? 'End Date & Time' : 'Check-out Date' ?>
                        </label>
                        <input type="datetime-local" class="form-control" id="book-end" name="end_time" required>
                    </div>

                    <!-- Dynamic Price Preview -->
                    <div class="price-preview glass-card p-3 mb-3 text-center d-none" id="price-preview">
                        <small class="text-muted d-block">Estimated Total</small>
                        <span class="fs-2 fw-bold text-accent" id="preview-price">$0.00</span>
                        <small class="text-muted d-block" id="preview-breakdown"></small>
                    </div>

                    <!-- Availability Status -->
                    <div id="availability-status" class="mb-3 d-none"></div>

                    <button type="button" class="btn btn-outline-accent w-100 mb-2" id="check-availability-btn">
                        <i class="bi bi-calendar-check me-1"></i>Check Availability
                    </button>

                    <button type="submit" class="btn btn-accent w-100 btn-lg" id="book-now-btn" disabled>
                        <i class="bi bi-credit-card me-2"></i>Book & Pay Now
                    </button>

                    <input type="hidden" name="asset_id" value="<?= $asset['asset_id'] ?>">
                    <input type="hidden" id="price-per-hour" value="<?= $asset['price_per_hour'] ?>">
                    <input type="hidden" id="price-per-day" value="<?= $asset['price_per_day'] ?>">
                </form>

                <?php if (!isLoggedIn()): ?>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <a href="<?= baseUrl() ?>index.php?page=login" class="text-accent">Log in</a> to book this asset
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
