<?php $pageTitle = t('admin_panel'); ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<?php $activeTab = $_GET['tab'] ?? 'overview'; ?>

<section class="container py-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-accent"></i><?= t('admin_panel') ?></h2>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="bi bi-people"></i>
                <h3 class="fw-bold"><?= $totalUsers ?></h3>
                <small><?= t('total_users') ?></small>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="bi bi-building"></i>
                <h3 class="fw-bold"><?= $totalAssets ?></h3>
                <small><?= t('total_assets') ?></small>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="bi bi-calendar-check"></i>
                <h3 class="fw-bold"><?= $totalBookings ?></h3>
                <small><?= t('total_bookings') ?></small>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="bi bi-cash-stack"></i>
                <h3 class="fw-bold">$<?= number_format($totalRevenue) ?></h3>
                <small><?= t('total_revenue') ?></small>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card" style="border: 1px solid #F39C12;">
                <i class="bi bi-hourglass-split" style="color:#F39C12;"></i>
                <h3 class="fw-bold"><?= $pendingAssets ?></h3>
                <small><?= t('pending_approval') ?></small>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card" style="border: 1px solid #FF6B6B;">
                <i class="bi bi-flag" style="color:#FF6B6B;"></i>
                <h3 class="fw-bold"><?= $pendingReports ?></h3>
                <small><?= t('pending_reports') ?></small>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs-custom mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=admin&tab=overview">
                <i class="bi bi-graph-up me-1"></i>Overview
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=admin&tab=users">
                <i class="bi bi-people me-1"></i><?= t('all_users') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'assets' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=admin&tab=assets">
                <i class="bi bi-building me-1"></i><?= t('all_assets') ?>
                <?php if ($pendingAssets > 0): ?>
                    <span class="tab-badge"><?= $pendingAssets ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'bookings' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=admin&tab=bookings">
                <i class="bi bi-calendar3 me-1"></i><?= t('all_bookings') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'reports' ? 'active' : '' ?>"
               href="<?= baseUrl() ?>index.php?page=admin&tab=reports">
                <i class="bi bi-flag me-1"></i><?= t('all_reports') ?>
                <?php if ($pendingReports > 0): ?>
                    <span class="tab-badge"><?= $pendingReports ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- Tab content -->
    <?php if ($activeTab === 'overview'): ?>
        <!-- Charts -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <h5 class="fw-semibold mb-3"><?= t('monthly_bookings') ?></h5>
                    <div class="chart-container">
                        <canvas id="bookingsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <h5 class="fw-semibold mb-3"><?= t('monthly_revenue') ?></h5>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') return;

            const chartData = <?= json_encode($chartData) ?>;
            const labels = chartData.map(d => d.month);
            const counts = chartData.map(d => parseInt(d.count));
            const revenue = chartData.map(d => parseFloat(d.revenue));
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
            const textColor = isDark ? '#A0A3B1' : '#636E72';

            new Chart(document.getElementById('bookingsChart'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Bookings',
                        data: counts,
                        backgroundColor: 'rgba(108, 92, 231, 0.6)',
                        borderColor: '#6C5CE7',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                        x: { grid: { display: false }, ticks: { color: textColor } }
                    }
                }
            });

            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: revenue,
                        borderColor: '#00CEC9',
                        backgroundColor: 'rgba(0, 206, 201, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#00CEC9'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                        x: { grid: { display: false }, ticks: { color: textColor } }
                    }
                }
            });
        });
        </script>

    <?php elseif ($activeTab === 'users'): ?>
        <div class="table-responsive glass-card p-3">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= t('id') ?></th>
                        <th><?= t('name') ?></th>
                        <th><?= t('email') ?></th>
                        <th><?= t('role') ?></th>
                        <th>Password (Dev)</th>
                        <th><?= t('status') ?></th>
                        <th><?= t('joined') ?></th>
                        <th><?= t('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['user_id'] ?></td>
                            <td class="fw-semibold"><?= sanitize($u['full_name']) ?></td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><span class="badge bg-accent-subtle text-accent"><?= ucfirst($u['role']) ?></span></td>
                            <td><span class="badge bg-secondary font-monospace"><?= sanitize($u['dev_password'] ?? 'N/A') ?></span></td>
                            <td>
                                <?php if ($u['is_blocked']): ?>
                                    <span class="badge bg-danger"><?= t('block') ?>ed</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= t('active') ?></span>
                                <?php endif; ?>
                                <?php if (!$u['is_verified']): ?>
                                    <span class="badge bg-warning text-dark">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= date('M j, Y', strtotime($u['created_at'])) ?></small></td>
                            <td>
                                <a href="<?= baseUrl() ?>index.php?page=admin&action=block&id=<?= $u['user_id'] ?>"
                                   class="btn btn-sm <?= $u['is_blocked'] ? 'btn-outline-success' : 'btn-outline-danger' ?>"
                                   onclick="return confirm('<?= $u['is_blocked'] ? t('confirm_unblock') : t('confirm_block') ?>')">
                                    <i class="bi <?= $u['is_blocked'] ? 'bi-unlock' : 'bi-lock' ?> me-1"></i>
                                    <?= $u['is_blocked'] ? t('unblock') : t('block') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($activeTab === 'assets'): ?>
        <div class="table-responsive glass-card p-3">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= t('id') ?></th>
                        <th><?= t('title') ?></th>
                        <th><?= t('owner') ?></th>
                        <th><?= t('category') ?></th>
                        <th><?= t('price') ?></th>
                        <th><?= t('status') ?></th>
                        <th><?= t('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allAssets as $a): ?>
                        <tr <?= !$a['is_approved'] ? 'class="table-warning"' : '' ?>>
                            <td><?= $a['asset_id'] ?></td>
                            <td class="fw-semibold"><?= sanitize($a['title']) ?></td>
                            <td><?= sanitize($a['owner_name']) ?></td>
                            <td><?= formatCategory($a['category']) ?></td>
                            <td><?= getAssetPrice($a) ?></td>
                            <td>
                                <?php if (!$a['is_approved']): ?>
                                    <span class="badge bg-warning text-dark"><?= t('pending') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= t('active') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$a['is_approved']): ?>
                                    <a href="<?= baseUrl() ?>index.php?page=admin&action=approve&id=<?= $a['asset_id'] ?>"
                                       class="btn btn-sm btn-success me-1"><i class="bi bi-check-lg me-1"></i><?= t('approve') ?></a>
                                    <a href="<?= baseUrl() ?>index.php?page=admin&action=reject&id=<?= $a['asset_id'] ?>"
                                       class="btn btn-sm btn-danger" onclick="return confirm('Reject this listing?')">
                                        <i class="bi bi-x-lg me-1"></i><?= t('reject') ?></a>
                                <?php else: ?>
                                    <a href="<?= baseUrl() ?>index.php?page=asset&action=detail&id=<?= $a['asset_id'] ?>"
                                       class="btn btn-sm btn-outline-accent"><i class="bi bi-eye me-1"></i><?= t('view_details') ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($activeTab === 'bookings'): ?>
        <div class="table-responsive glass-card p-3">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= t('id') ?></th>
                        <th><?= t('asset') ?></th>
                        <th><?= t('renter') ?></th>
                        <th><?= t('owner') ?></th>
                        <th><?= t('from_date') ?></th>
                        <th><?= t('to_date') ?></th>
                        <th><?= t('total') ?></th>
                        <th><?= t('status') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allBookings as $b): ?>
                        <tr>
                            <td><?= $b['booking_id'] ?></td>
                            <td class="fw-semibold"><?= sanitize($b['asset_title']) ?></td>
                            <td><?= sanitize($b['renter_name']) ?></td>
                            <td><?= sanitize($b['owner_name']) ?></td>
                            <td><small><?= date('M j, Y', strtotime($b['start_time'])) ?></small></td>
                            <td><small><?= date('M j, Y', strtotime($b['end_time'])) ?></small></td>
                            <td><strong>$<?= number_format($b['total_price'], 2) ?></strong></td>
                            <td>
                                <?php
                                $sc = ['confirmed'=>'bg-success','pending'=>'bg-warning text-dark','cancelled'=>'bg-danger'];
                                ?><span class="badge <?= $sc[$b['status']] ?? 'bg-secondary' ?>"><?= t($b['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($activeTab === 'reports'): ?>
        <?php if (empty($allReports)): ?>
            <div class="glass-card p-5 text-center">
                <i class="bi bi-flag display-3 text-muted d-block mb-3"></i>
                <p class="text-muted">No reports yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive glass-card p-3">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= t('id') ?></th>
                            <th><?= t('reporter') ?></th>
                            <th><?= t('reported_asset') ?></th>
                            <th><?= t('reason') ?></th>
                            <th><?= t('status') ?></th>
                            <th><?= t('date') ?></th>
                            <th><?= t('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allReports as $r): ?>
                            <tr>
                                <td><?= $r['report_id'] ?></td>
                                <td><?= sanitize($r['reporter_name']) ?></td>
                                <td><?= sanitize($r['asset_title'] ?? $r['reported_user_name'] ?? '-') ?></td>
                                <td><small><?= sanitize(mb_substr($r['reason'], 0, 80)) ?>...</small></td>
                                <td>
                                    <?php
                                    $rsc = ['pending'=>'bg-warning text-dark','reviewed'=>'bg-info','resolved'=>'bg-success'];
                                    ?><span class="badge <?= $rsc[$r['status']] ?? 'bg-secondary' ?>"><?= ucfirst($r['status']) ?></span>
                                </td>
                                <td><small><?= date('M j, Y', strtotime($r['created_at'])) ?></small></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="POST" action="<?= baseUrl() ?>index.php?page=admin&action=resolve-report&id=<?= $r['report_id'] ?>" class="d-inline">
                                            <input type="hidden" name="admin_notes" value="Reviewed and resolved.">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-lg me-1"></i><?= t('resolve') ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small"><?= sanitize($r['admin_notes'] ?? '') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
