<?php $pageTitle = t('explore'); ?>
<?php require __DIR__ . '/partials/header.php'; ?>

<!-- ─── Hero Section ────────────────────────────────────────── -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-800 mb-3">
                    <?= t('hero_title_1') ?> <span class="text-gradient"><?= t('hero_title_2') ?></span><?= t('hero_title_3') ?>
                </h1>
                <p class="lead text-light-muted mb-4"><?= t('hero_subtitle') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ─── Smart Search Bar ─────────────────────────────────── -->
<section class="container search-bar-wrapper">
    <div class="glass-card p-4 shadow-lg">
        <form id="search-form" class="row g-3 align-items-end">
            <!-- Keyword Search with Autocomplete -->
            <div class="col-lg-4 col-md-6 position-relative">
                <label for="filter-keyword" class="form-label fw-semibold">
                    <i class="bi bi-search me-1"></i><?= t('keyword') ?>
                </label>
                <input type="text" id="filter-keyword" class="form-control"
                       placeholder="<?= t('search_placeholder') ?>" autocomplete="off">
                <div class="autocomplete-dropdown" id="autocomplete-dropdown"></div>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="filter-category" class="form-label fw-semibold">
                    <i class="bi bi-funnel me-1"></i><?= t('category') ?>
                </label>
                <select id="filter-category" class="form-select">
                    <option value=""><?= t('all_categories') ?></option>
                    <option value="apartment">🏠 <?= t('apartment') ?></option>
                    <option value="car">🚗 <?= t('car') ?></option>
                    <option value="sport_venue">⚽ <?= t('sport_venue') ?></option>
                    <option value="equipment">🔧 <?= t('equipment') ?></option>
                    <option value="studio">🎨 <?= t('studio') ?></option>
                    <option value="parking">🅿️ <?= t('parking') ?></option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label for="filter-location" class="form-label fw-semibold">
                    <i class="bi bi-geo-alt me-1"></i><?= t('location') ?>
                </label>
                <input type="text" id="filter-location" class="form-control"
                       placeholder="<?= t('city_address') ?>">
            </div>
            <div class="col-lg-2 col-md-4">
                <label for="filter-sort" class="form-label fw-semibold">
                    <i class="bi bi-sort-down me-1"></i><?= t('sort_by') ?>
                </label>
                <select id="filter-sort" class="form-select">
                    <option value="newest"><?= t('newest') ?></option>
                    <option value="price_low"><?= t('price_low_high') ?></option>
                    <option value="price_high"><?= t('price_high_low') ?></option>
                    <option value="rating"><?= t('top_rated') ?></option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <button type="submit" class="btn btn-accent w-100">
                    <i class="bi bi-search me-1"></i><?= t('search') ?>
                </button>
            </div>
        </form>

        <!-- Dynamic Filters (category-specific) -->
        <div id="dynamic-filters" class="dynamic-filters collapsed mt-3 pt-3" style="border-top: 1px solid var(--border-color);">
            <!-- Populated by JS based on category -->
        </div>

        <!-- Advanced: Price Range + Dates -->
        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <label class="form-label fw-semibold small"><i class="bi bi-currency-dollar me-1"></i><?= t('min_price') ?></label>
                <input type="number" id="filter-min-price" class="form-control form-control-sm" min="0" placeholder="0">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small"><i class="bi bi-currency-dollar me-1"></i><?= t('max_price') ?></label>
                <input type="number" id="filter-max-price" class="form-control form-control-sm" min="0" placeholder="1000">
            </div>
            <div class="col-md-3">
                <label for="filter-start" class="form-label fw-semibold small">
                    <i class="bi bi-calendar-event me-1"></i><?= t('from_date') ?>
                </label>
                <input type="datetime-local" id="filter-start" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label for="filter-end" class="form-label fw-semibold small">
                    <i class="bi bi-calendar-check me-1"></i><?= t('to_date') ?>
                </label>
                <input type="datetime-local" id="filter-end" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</section>

<!-- ─── Recommended for You ───────────────────────────────── -->
<?php if (isLoggedIn()): ?>
<section class="container py-4">
    <div class="section-header">
        <h4 class="fw-bold"><i class="bi bi-stars me-2 text-accent"></i><?= t('recommended_for_you') ?></h4>
    </div>
    <div class="row g-4" id="recommendations-grid">
        <div class="col-12 text-center py-3">
            <div class="spinner-border text-accent spinner-border-sm" role="status"></div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ─── Assets Grid ─────────────────────────────────────────── -->
<section class="container py-4">
    <div class="section-header">
        <h2 class="fw-bold"><?= t('available_rentals') ?></h2>
        <span class="badge bg-accent-subtle text-accent" id="results-count">
            <?= count($assets) ?> <?= t('listings') ?>
        </span>
    </div>

    <!-- Category Pills -->
    <div class="category-pills" id="category-pills">
        <button class="category-pill active" data-category=""><?= t('all_categories') ?></button>
        <button class="category-pill" data-category="apartment">🏠 <?= t('apartment') ?></button>
        <button class="category-pill" data-category="car">🚗 <?= t('car') ?></button>
        <button class="category-pill" data-category="sport_venue">⚽ <?= t('sport_venue') ?></button>
        <button class="category-pill" data-category="equipment">🔧 <?= t('equipment') ?></button>
        <button class="category-pill" data-category="studio">🎨 <?= t('studio') ?></button>
        <button class="category-pill" data-category="parking">🅿️ <?= t('parking') ?></button>
    </div>

    <div class="row g-4" id="assets-grid">
        <?php if (empty($assets)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox display-3 text-muted d-block mb-3"></i>
                <p class="text-muted"><?= t('no_results') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($assets as $asset): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="asset-card card h-100 border-0 shadow-sm">
                        <div class="asset-card-img-wrapper">
                            <?php if ($asset['image_url']): ?>
                                <img src="<?= strpos($asset['image_url'], 'http') === 0 ? sanitize($asset['image_url']) : baseUrl() . sanitize($asset['image_url']) ?>"
                                     class="card-img-top lazy-img" alt="<?= sanitize($asset['title']) ?>"
                                     loading="lazy" onload="this.classList.add('loaded')">
                            <?php else: ?>
                                <div class="card-img-top placeholder-img d-flex align-items-center justify-content-center">
                                    <i class="bi <?= categoryIcon($asset['category']) ?> display-3 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <span class="category-badge badge"><?= t($asset['category']) ?></span>
                            <?php if (isLoggedIn() && !isAdmin()): ?>
                                <button class="wishlist-btn <?= !empty($asset['in_wishlist']) ? 'active' : '' ?>" data-asset-id="<?= $asset['asset_id'] ?>" title="<?= t('wishlist') ?>">
                                    <i class="bi bi-heart<?= !empty($asset['in_wishlist']) ? '-fill' : '' ?>"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?= sanitize($asset['title']) ?></h5>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-geo-alt me-1"></i><?= sanitize($asset['address'] ?: $asset['city'] ?? t('location')) ?>
                            </p>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-person me-1"></i><?= t('hosted_by') ?> <?= sanitize($asset['owner_name']) ?>
                            </p>
                            <?php if (isset($asset['avg_rating']) && $asset['avg_rating'] > 0): ?>
                                <div class="mb-2">
                                    <span class="stars-mini">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= round($asset['avg_rating']) ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <small class="text-muted ms-1"><?= $asset['avg_rating'] ?></small>
                                    <?php if (isset($asset['review_count'])): ?>
                                        <small class="text-muted">(<?= $asset['review_count'] ?>)</small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="price-tag">
                                    <?php if (isHourlyCategory($asset['category'])): ?>
                                        <strong class="text-accent fs-5">$<?= number_format($asset['price_per_hour'], 2) ?></strong>
                                        <small class="text-muted"><?= t('per_hour') ?></small>
                                    <?php else: ?>
                                        <strong class="text-accent fs-5">$<?= number_format($asset['price_per_day'], 2) ?></strong>
                                        <small class="text-muted"><?= t('per_day') ?></small>
                                    <?php endif; ?>
                                </div>
                                <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $asset['asset_id'] ?>"
                                   class="btn btn-sm btn-outline-accent">
                                    <?= t('view_details') ?> <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
