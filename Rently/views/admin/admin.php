<?php $pageTitle = 'Admin Panel'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="container py-5">
    <h2 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2"></i>Admin Panel</h2>

    <!-- ─── Stats ─── -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="bi bi-people-fill fs-2 text-accent"></i>
                <h3 class="fw-bold mt-2"><?= $totalUsers ?></h3>
                <small class="text-muted">Total Users</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="bi bi-box-seam-fill fs-2 text-accent"></i>
                <h3 class="fw-bold mt-2"><?= $totalAssets ?></h3>
                <small class="text-muted">Total Assets</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="bi bi-calendar-check-fill fs-2 text-accent"></i>
                <h3 class="fw-bold mt-2"><?= $totalBookings ?></h3>
                <small class="text-muted">Total Bookings</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="bi bi-currency-dollar fs-2 text-accent"></i>
                <h3 class="fw-bold mt-2">$<?= number_format($totalRevenue, 2) ?></h3>
                <small class="text-muted">Revenue</small>
            </div>
        </div>
    </div>

    <!-- ─── Users Table ─── -->
    <div class="glass-card p-4 mb-4">
        <h4 class="fw-semibold mb-3"><i class="bi bi-people me-2"></i>All Users</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td class="fw-semibold"><?= sanitize($u['full_name']) ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td>
                            <span class="badge bg-accent-subtle text-accent">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td><small><?= date('M j, Y', strtotime($u['created_at'])) ?></small></td>
                        <td>
                            <?php if ($u['is_blocked']): ?>
                                <span class="badge bg-danger">Blocked</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= baseUrl() ?>index.php?page=admin&action=block&id=<?= $u['user_id'] ?>"
                               class="btn btn-sm <?= $u['is_blocked'] ? 'btn-outline-success' : 'btn-outline-danger' ?>"
                               onclick="return confirm('<?= $u['is_blocked'] ? 'Unblock' : 'Block' ?> this user?')">
                                <i class="bi bi-<?= $u['is_blocked'] ? 'unlock' : 'lock' ?>"></i>
                                <?= $u['is_blocked'] ? 'Unblock' : 'Block' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ─── All Assets ─── -->
    <div class="glass-card p-4">
        <h4 class="fw-semibold mb-3"><i class="bi bi-grid me-2"></i>All Assets</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Owner</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allAssets as $a): ?>
                    <tr>
                        <td><?= $a['asset_id'] ?></td>
                        <td class="fw-semibold">
                            <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $a['asset_id'] ?>">
                                <?= sanitize($a['title']) ?>
                            </a>
                        </td>
                        <td><?= sanitize($a['owner_name']) ?></td>
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
