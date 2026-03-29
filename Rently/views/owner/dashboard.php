<?php $pageTitle = t('my_dashboard'); ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="container py-4">
    <!-- Leaflet Map CSS for Location Selection -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    .leaflet-container { font-family: inherit; }
    /* Fix leaflet z-index issues in modals */
    .leaflet-pane { z-index: 400; }
    .leaflet-top, .leaflet-bottom { z-index: 401; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-speedometer2 me-2 text-accent"></i><?= t('my_dashboard') ?></h2>
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addAssetModal">
            <i class="bi bi-plus-lg me-1"></i><?= t('add_new_asset') ?>
        </button>
    </div>

    <!-- My Assets -->
    <h4 class="fw-bold mb-3"><?= t('my_assets') ?></h4>
    <?php if (empty($myAssets)): ?>
        <div class="glass-card p-5 text-center mb-4">
            <i class="bi bi-box-seam display-3 text-muted d-block mb-3"></i>
            <p class="text-muted"><?= t('no_assets_yet') ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive glass-card p-3 mb-4">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= t('title') ?></th>
                        <th><?= t('category') ?></th>
                        <th><?= t('price') ?></th>
                        <th><?= t('status') ?></th>
                        <th><?= t('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myAssets as $a): ?>
                        <tr>
                            <td>
                                <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $a['asset_id'] ?>" class="text-accent fw-semibold">
                                    <?= sanitize($a['title']) ?>
                                </a>
                            </td>
                            <td><?= formatCategory($a['category']) ?></td>
                            <td><?= getAssetPrice($a) ?></td>
                            <td>
                                <?php if (!$a['is_approved']): ?>
                                    <span class="badge bg-warning text-dark"><?= t('pending') ?></span>
                                <?php elseif ($a['status'] === 'active'): ?>
                                    <span class="badge bg-success"><?= t('active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= t($a['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-accent me-1" data-bs-toggle="modal"
                                        data-bs-target="#editAssetModal-<?= $a['asset_id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="<?= baseUrl() ?>index.php?page=dashboard&action=delete&id=<?= $a['asset_id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('<?= t('confirm_delete') ?>')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Incoming Bookings -->
    <h4 class="fw-bold mb-3"><?= t('incoming_bookings') ?></h4>
    <?php if (empty($incomingBookings)): ?>
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
                        <th><?= t('renter') ?></th>
                        <th><?= t('from_date') ?></th>
                        <th><?= t('to_date') ?></th>
                        <th><?= t('total') ?></th>
                        <th><?= t('status') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incomingBookings as $b): ?>
                        <tr>
                            <td class="fw-semibold"><?= sanitize($b['asset_title']) ?></td>
                            <td><?= sanitize($b['renter_name']) ?></td>
                            <td><small><?= date('M j, Y g:i A', strtotime($b['start_time'])) ?></small></td>
                            <td><small><?= date('M j, Y g:i A', strtotime($b['end_time'])) ?></small></td>
                            <td><strong>$<?= number_format($b['total_price'], 2) ?></strong></td>
                            <td>
                                <?php
                                $sc = ['confirmed' => 'bg-success', 'pending' => 'bg-warning text-dark', 'cancelled' => 'bg-danger'];
                                ?>
                                <span class="badge <?= $sc[$b['status']] ?? 'bg-secondary' ?>"><?= t($b['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ─── Add Asset Modal ─────────────────────────────────────── -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl() ?>index.php?page=dashboard&action=add" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i><?= t('add_new_asset') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="add-title" class="form-label fw-semibold"><?= t('title') ?></label>
                            <input type="text" class="form-control" id="add-title" name="title" required>
                        </div>
                        <div class="col-md-4">
                            <label for="add-category" class="form-label fw-semibold"><?= t('category') ?></label>
                            <select class="form-select" id="add-category" name="category" required>
                                <option value="apartment">🏠 <?= t('apartment') ?></option>
                                <option value="car">🚗 <?= t('car') ?></option>
                                <option value="sport_venue">⚽ <?= t('sport_venue') ?></option>
                                <option value="equipment">🔧 <?= t('equipment') ?></option>
                                <option value="studio">🎨 <?= t('studio') ?></option>
                                <option value="parking">🅿️ <?= t('parking') ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="add-desc" class="form-label fw-semibold"><?= t('description') ?></label>
                            <textarea class="form-control" id="add-desc" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-geo-alt me-1"></i><?= t('location') ?> (Map Search)</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="add-map-search" placeholder="Enter full address to search..." autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="add-map-search-btn"><i class="bi bi-search"></i> Search</button>
                            </div>
                            <div id="add-map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid var(--border-color); z-index: 1;"></div>
                            <!-- Hidden inputs to submit coordinates -->
                            <input type="hidden" name="latitude" id="add-lat">
                            <input type="hidden" name="longitude" id="add-lng">
                            <input type="hidden" name="address" id="add-address">
                            <input type="hidden" name="city" id="add-city">
                        </div>
                        <div class="col-md-6">
                            <label for="add-price-hour" class="form-label fw-semibold"><?= t('price_per_hour') ?></label>
                            <input type="number" class="form-control" id="add-price-hour" name="price_per_hour" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label for="add-price-day" class="form-label fw-semibold"><?= t('price_per_day') ?></label>
                            <input type="number" class="form-control" id="add-price-day" name="price_per_day" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label for="add-image" class="form-label fw-semibold"><?= t('main_image') ?></label>
                            <input type="file" class="form-control" id="add-image" name="image" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label for="add-gallery" class="form-label fw-semibold"><?= t('gallery_images') ?></label>
                            <input type="file" class="form-control" id="add-gallery" name="gallery[]" accept="image/*" multiple>
                        </div>
                    </div>

                    <!-- Dynamic category-specific fields (JS populated) -->
                    <div id="add-extra-fields" class="row g-3 mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('cancel') ?></button>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i><?= t('add_asset') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Edit Asset Modals ────────────────────────────────────── -->
<?php foreach ($myAssets as $a): ?>
<div class="modal fade" id="editAssetModal-<?= $a['asset_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl() ?>index.php?page=dashboard&action=edit" enctype="multipart/form-data">
                <input type="hidden" name="asset_id" value="<?= $a['asset_id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i><?= t('edit') ?> <?= sanitize($a['title']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold"><?= t('title') ?></label>
                            <input type="text" class="form-control" name="title" value="<?= sanitize($a['title']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= t('category') ?></label>
                            <select class="form-select edit-category-select" data-id="<?= $a['asset_id'] ?>" data-extra='<?= htmlspecialchars($a['extra_fields'] ?? "{}", ENT_QUOTES, "UTF-8") ?>' name="category" required>
                                <?php foreach (['apartment','car','sport_venue','equipment','studio','parking'] as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $a['category'] === $cat ? 'selected' : '' ?>><?= t($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= t('description') ?></label>
                            <textarea class="form-control" name="description" rows="3"><?= sanitize($a['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-geo-alt me-1"></i><?= t('location') ?> (Map Search)</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="edit-map-search-<?= $a['asset_id'] ?>" placeholder="Enter full address to search..." value="<?= sanitize($a['address'] ?? '') ?><?= !empty($a['city']) ? ', ' . sanitize($a['city']) : '' ?>" autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="edit-map-search-btn-<?= $a['asset_id'] ?>"><i class="bi bi-search"></i> Search</button>
                            </div>
                            <div id="edit-map-<?= $a['asset_id'] ?>" class="edit-map-container" data-id="<?= $a['asset_id'] ?>" data-lat="<?= $a['latitude'] ?>" data-lng="<?= $a['longitude'] ?>" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid var(--border-color); z-index: 1;"></div>
                            
                            <!-- Hidden inputs to submit coordinates -->
                            <input type="hidden" name="latitude" id="edit-lat-<?= $a['asset_id'] ?>" value="<?= $a['latitude'] ?>">
                            <input type="hidden" name="longitude" id="edit-lng-<?= $a['asset_id'] ?>" value="<?= $a['longitude'] ?>">
                            <input type="hidden" name="address" id="edit-address-<?= $a['asset_id'] ?>" value="<?= sanitize($a['address'] ?? '') ?>">
                            <input type="hidden" name="city" id="edit-city-<?= $a['asset_id'] ?>" value="<?= sanitize($a['city'] ?? '') ?>">
                        </div>
                        
                        <!-- Dynamic category-specific fields container -->
                        <div id="edit-extra-fields-<?= $a['asset_id'] ?>" class="row g-3 mt-2"></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= t('price_per_hour') ?></label>
                            <input type="number" class="form-control" name="price_per_hour" step="0.01" value="<?= $a['price_per_hour'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= t('price_per_day') ?></label>
                            <input type="number" class="form-control" name="price_per_day" step="0.01" value="<?= $a['price_per_day'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= t('main_image') ?></label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= t('gallery_images') ?></label>
                            <input type="file" class="form-control" name="gallery[]" accept="image/*" multiple>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('cancel') ?></button>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i><?= t('save_changes') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Leaflet Library -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
