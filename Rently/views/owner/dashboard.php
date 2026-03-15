<?php $pageTitle = 'Owner Dashboard'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-speedometer2 me-2"></i>My Dashboard</h2>
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addAssetModal">
            <i class="bi bi-plus-lg me-1"></i>Add New Asset
        </button>
    </div>

    <!-- ─── Stats Cards ─── -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="glass-card p-4 text-center">
                <i class="bi bi-box-seam fs-2 text-accent"></i>
                <h3 class="fw-bold mt-2"><?= count($myAssets) ?></h3>
                <small class="text-muted">My Assets</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 text-center">
                <i class="bi bi-calendar-check fs-2 text-accent"></i>
                <h3 class="fw-bold mt-2"><?= count($incomingBookings) ?></h3>
                <small class="text-muted">Bookings</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 text-center">
                <i class="bi bi-currency-dollar fs-2 text-accent"></i>
                <?php
                $revenue = array_sum(array_column(
                    array_filter($incomingBookings, fn($b) => $b['status'] === 'confirmed'),
                    'total_price'
                ));
                ?>
                <h3 class="fw-bold mt-2">$<?= number_format($revenue, 2) ?></h3>
                <small class="text-muted">Revenue</small>
            </div>
        </div>
    </div>

    <!-- ─── My Assets ─── -->
    <div class="glass-card p-4 mb-4">
        <h4 class="fw-semibold mb-3"><i class="bi bi-grid me-2"></i>My Assets</h4>
        <?php if (empty($myAssets)): ?>
            <p class="text-muted text-center py-3">You haven't listed any assets yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myAssets as $a): ?>
                        <tr>
                            <td class="fw-semibold"><?= sanitize($a['title']) ?></td>
                            <td>
                                <span class="badge bg-accent-subtle text-accent">
                                    <?= ucfirst(str_replace('_', ' ', $a['category'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($a['category'] === 'sport_venue'): ?>
                                    $<?= number_format($a['price_per_hour'], 2) ?>/hr
                                <?php else: ?>
                                    $<?= number_format($a['price_per_day'], 2) ?>/day
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $a['status'] === 'active' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= ucfirst($a['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1 edit-asset-btn"
                                        data-asset='<?= json_encode($a) ?>'
                                        data-bs-toggle="modal" data-bs-target="#editAssetModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="<?= baseUrl() ?>index.php?page=dashboard&action=delete&id=<?= $a['asset_id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this asset?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ─── Incoming Bookings ─── -->
    <div class="glass-card p-4">
        <h4 class="fw-semibold mb-3"><i class="bi bi-inbox me-2"></i>Incoming Bookings</h4>
        <?php if (empty($incomingBookings)): ?>
            <p class="text-muted text-center py-3">No bookings yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Renter</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incomingBookings as $b): ?>
                        <tr>
                            <td class="fw-semibold"><?= sanitize($b['asset_title']) ?></td>
                            <td><?= sanitize($b['renter_name']) ?></td>
                            <td><small><?= date('M j, Y H:i', strtotime($b['start_time'])) ?></small></td>
                            <td><small><?= date('M j, Y H:i', strtotime($b['end_time'])) ?></small></td>
                            <td class="fw-bold text-accent">$<?= number_format($b['total_price'], 2) ?></td>
                            <td>
                                <?php
                                $statusClass = match($b['status']) {
                                    'confirmed' => 'bg-success',
                                    'pending'   => 'bg-warning',
                                    'cancelled' => 'bg-danger',
                                    default     => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= ucfirst($b['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── Add Asset Modal ─── -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl() ?>index.php?page=dashboard&action=add"
                  enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add New Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" name="title" required
                                   placeholder="e.g. Cozy Downtown Apartment">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category</label>
                            <select class="form-select" name="category" required id="add-category">
                                <option value="apartment">🏠 Apartment</option>
                                <option value="car">🚗 Car</option>
                                <option value="sport_venue">⚽ Sport Venue</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="3"
                                      placeholder="Describe your asset..."></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" class="form-control" name="address"
                                   placeholder="123 Main St, City">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price per Hour ($)</label>
                            <input type="number" class="form-control" name="price_per_hour"
                                   step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price per Day ($)</label>
                            <input type="number" class="form-control" name="price_per_day"
                                   step="0.01" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-check-lg me-1"></i>Add Asset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Edit Asset Modal ─── -->
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl() ?>index.php?page=dashboard&action=edit"
                  enctype="multipart/form-data" id="edit-asset-form">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="asset_id" id="edit-asset-id">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" name="title" id="edit-title" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category</label>
                            <select class="form-select" name="category" id="edit-category" required>
                                <option value="apartment">🏠 Apartment</option>
                                <option value="car">🚗 Car</option>
                                <option value="sport_venue">⚽ Sport Venue</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="3" id="edit-description"></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" class="form-control" name="address" id="edit-address">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="edit-status">
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price per Hour ($)</label>
                            <input type="number" class="form-control" name="price_per_hour"
                                   id="edit-price-hour" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price per Day ($)</label>
                            <input type="number" class="form-control" name="price_per_day"
                                   id="edit-price-day" step="0.01" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">New Image (optional)</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
