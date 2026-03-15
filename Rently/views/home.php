<?php $pageTitle = 'Explore Rentals'; ?>
<?php require __DIR__ . '/partials/header.php'; ?>

<!-- ─── Hero Section ────────────────────────────────────────── -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-800 mb-3">
                    Rent <span class="text-gradient">Anything</span>, Anywhere
                </h1>
                <p class="lead text-light-muted mb-4">
                    Discover apartments, cars, and sport venues from trusted hosts.
                    Book by the hour or by the day — it's that simple.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ─── Search & Filter Bar ─────────────────────────────────── -->
<section class="container" style="margin-top: -3rem; position: relative; z-index: 10;">
    <div class="glass-card p-4 shadow-lg">
        <form id="search-form" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="filter-category" class="form-label fw-semibold">
                    <i class="bi bi-funnel me-1"></i>Category
                </label>
                <select id="filter-category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="apartment">🏠 Apartment</option>
                    <option value="car">🚗 Car</option>
                    <option value="sport_venue">⚽ Sport Venue</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter-location" class="form-label fw-semibold">
                    <i class="bi bi-geo-alt me-1"></i>Location
                </label>
                <input type="text" id="filter-location" class="form-control"
                       placeholder="City, address...">
            </div>
            <div class="col-md-2">
                <label for="filter-start" class="form-label fw-semibold">
                    <i class="bi bi-calendar-event me-1"></i>From
                </label>
                <input type="datetime-local" id="filter-start" class="form-control">
            </div>
            <div class="col-md-2">
                <label for="filter-end" class="form-label fw-semibold">
                    <i class="bi bi-calendar-check me-1"></i>To
                </label>
                <input type="datetime-local" id="filter-end" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-accent w-100">
                    <i class="bi bi-search me-1"></i>Search
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ─── Assets Grid ─────────────────────────────────────────── -->
<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Available Rentals</h2>
        <span class="badge bg-accent-subtle text-accent" id="results-count">
            <?= count($assets) ?> listings
        </span>
    </div>

    <div class="row g-4" id="assets-grid">
        <?php if (empty($assets)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox display-3 text-muted d-block mb-3"></i>
                <p class="text-muted">No assets found. Be the first to list!</p>
            </div>
        <?php else: ?>
            <?php foreach ($assets as $asset): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="asset-card card h-100 border-0 shadow-sm">
                        <div class="asset-card-img-wrapper">
                            <?php if ($asset['image_url']): ?>
                                <img src="<?= baseUrl() . sanitize($asset['image_url']) ?>"
                                     class="card-img-top" alt="<?= sanitize($asset['title']) ?>">
                            <?php else: ?>
                                <div class="card-img-top placeholder-img d-flex align-items-center justify-content-center">
                                    <?php
                                    $icons = ['apartment' => 'bi-building', 'car' => 'bi-car-front', 'sport_venue' => 'bi-trophy'];
                                    $icon = $icons[$asset['category']] ?? 'bi-image';
                                    ?>
                                    <i class="bi <?= $icon ?> display-3 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <span class="category-badge badge">
                                <?= ucfirst(str_replace('_', ' ', $asset['category'])) ?>
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?= sanitize($asset['title']) ?></h5>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt me-1"></i><?= sanitize($asset['address'] ?: 'Location not specified') ?>
                            </p>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-person me-1"></i>Hosted by <?= sanitize($asset['owner_name']) ?>
                            </p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="price-tag">
                                    <?php if ($asset['category'] === 'sport_venue'): ?>
                                        <strong class="text-accent fs-5">$<?= number_format($asset['price_per_hour'], 2) ?></strong>
                                        <small class="text-muted">/hour</small>
                                    <?php else: ?>
                                        <strong class="text-accent fs-5">$<?= number_format($asset['price_per_day'], 2) ?></strong>
                                        <small class="text-muted">/day</small>
                                    <?php endif; ?>
                                </div>
                                <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $asset['asset_id'] ?>"
                                   class="btn btn-sm btn-outline-accent">
                                    View Details <i class="bi bi-arrow-right ms-1"></i>
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
