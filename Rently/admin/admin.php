<?php
// admin/admin.php - Admin Dashboard
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdmin()) { die("Access Denied. Admins only."); }

$error = '';
$message = '';

$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$custom_params = "";
if ($date_filter === 'custom') {
    $custom_params = "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date);
}

// Handle Admin Dashboard POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            if ($action === 'approve_listing') {
                $id = (int)$_POST['listing_id'];
                $pdo->prepare("UPDATE listings SET status = 'approved' WHERE id = ?")->execute([$id]);
                
                $stmtOwner = $pdo->prepare("SELECT user_id, title FROM listings WHERE id = ?");
                $stmtOwner->execute([$id]);
                $lst = $stmtOwner->fetch();
                if ($lst) {
                    $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$lst['user_id'], "Your listing \"{$lst['title']}\" has been approved by the Admin and is now live!", "listings/view_listing.php?id=$id"]);
                }
                
                $message = __('Listing approved.');
            }
            
            elseif ($action === 'delete_listing') {
                $id = (int)$_POST['listing_id'];
                
                $stmt = $pdo->prepare("SELECT user_id, title, image FROM listings WHERE id = ?");
                $stmt->execute([$id]);
                $listing = $stmt->fetch();
                
                $checkHist = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE listing_id = ?");
                $checkHist->execute([$id]);
                $hasHistory = ($checkHist->fetchColumn() > 0);
                
                if ($hasHistory) {
                    $pdo->prepare("UPDATE listings SET status = 'rejected', rejection_reason = 'Deleted by Admin' WHERE id = ?")->execute([$id]);
                    if ($listing) {
                        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$listing['user_id'], "Your listing \"{$listing['title']}\" was deactivated by the Admin.", "user/profile.php"]);
                    }
                    $message = __('Listing deactivated (history preserved).');
                } else {
                    if($listing && file_exists('../' . $listing['image'])) { unlink('../' . $listing['image']); }
                    $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$id]);
                    if ($listing) {
                        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$listing['user_id'], "Your listing \"{$listing['title']}\" was deleted by the Admin.", "#"]);
                    }
                    $message = __('Listing deleted.');
                }
            }
            
            elseif ($action === 'approve_booking') {
                $bid = (int)$_POST['booking_id'];
                $pdo->prepare("UPDATE bookings SET status = 'pending_owner' WHERE id = ?")->execute([$bid]);
                
                $bk = $pdo->prepare("SELECT b.user_id as renter_id, l.user_id as owner_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
                $bk->execute([$bid]);
                $bData = $bk->fetch();
                if ($bData) {
                    $msg = "Admin approved the booking request for your listing \"{$bData['title']}\". It is now awaiting your final approval.";
                    $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$bData['owner_id'], $msg, 'user/profile.php']);
                }
                $message = __('Booking approved to pending owner.');
            }
            
            elseif ($action === 'reject_booking') {
                $bid = (int)$_POST['booking_id'];
                $stmt = $pdo->prepare("SELECT listing_id FROM bookings WHERE id = ?");
                $stmt->execute([$bid]);
                $listing_id = $stmt->fetchColumn();

                $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bid]);
                
                $bk = $pdo->prepare("SELECT b.user_id as renter_id, l.user_id as owner_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
                $bk->execute([$bid]);
                $bData = $bk->fetch();
                if ($bData) {
                    $msg = "Your booking request for \"{$bData['title']}\" was rejected or cancelled by the Admin.";
                    $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$bData['renter_id'], $msg, 'user/profile.php']);
                }

                if ($listing_id) {
                    promoteWaitlistedBookings($pdo, $listing_id);
                }
                $message = __('Booking rejected.');
            }
            
            elseif ($action === 'block_user') {
                $uid = (int)$_POST['user_id'];
                if ($uid !== 1) { 
                    $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?")->execute([$uid]); 
                    $message = __('User blocked.');
                }
            }
            
            elseif ($action === 'unblock_user') {
                $uid = (int)$_POST['user_id'];
                $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?")->execute([$uid]);
                $message = __('User unblocked.');
            }
            
            elseif ($action === 'resolve_report') {
                $rid = (int)$_POST['report_id'];
                $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$rid]);
                $message = __('Report marked as resolved.');
            }
            
            elseif ($action === 'dismiss_report') {
                $rid = (int)$_POST['report_id'];
                $pdo->prepare("UPDATE reports SET status = 'rejected' WHERE id = ?")->execute([$rid]);
                $message = __('Report dismissed.');
            }
        }
    }
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'analytics';

$date_cond_raw = "";
if ($date_filter === 'day') {
    $date_cond_raw = "created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
} elseif ($date_filter === 'week') {
    $date_cond_raw = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $date_cond_raw = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($date_filter === 'custom') {
    if (!empty($start_date) && !empty($end_date)) {
        $date_cond_raw = "created_at >= " . $pdo->quote($start_date . " 00:00:00") . " AND created_at <= " . $pdo->quote($end_date . " 23:59:59");
    }
}

// Build prefixed conditions to prevent ambiguous column errors in JOINs
$date_cond_listings = str_replace('created_at', 'listings.created_at', $date_cond_raw);
$date_cond_l = str_replace('created_at', 'l.created_at', $date_cond_raw);
$date_cond_users = str_replace('created_at', 'users.created_at', $date_cond_raw);
$date_cond_b = str_replace('created_at', 'b.created_at', $date_cond_raw);
$date_cond_bookings = str_replace('created_at', 'bookings.created_at', $date_cond_raw);
$date_cond_t = str_replace('created_at', 't.created_at', $date_cond_raw);
$date_cond_tickets = str_replace('created_at', 'tickets.created_at', $date_cond_raw);
$date_cond_r = str_replace('created_at', 'r.created_at', $date_cond_raw);
$date_cond_reports = str_replace('created_at', 'reports.created_at', $date_cond_raw);

$where_users = $date_cond_users ? "WHERE $date_cond_users" : "";
$where_listings = $date_cond_l ? "WHERE $date_cond_l" : "";
$where_bookings = $date_cond_b ? "WHERE $date_cond_b" : "";
$where_reports = $date_cond_r ? "WHERE $date_cond_r" : "";

$all_listings = $pdo->query("SELECT l.*, u.name as owner_name, u.email as owner_email FROM listings l JOIN users u ON l.user_id = u.id $where_listings ORDER BY l.status ASC, l.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_users = $pdo->query("SELECT * FROM users $where_users ORDER BY role ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_bookings = $pdo->query("
    SELECT b.*, l.title as listing_title, l.image as listing_image, l.price, l.price_type,
           u.name as renter_name, u.email as renter_email,
           o.name as owner_name
    FROM bookings b 
    JOIN listings l ON b.listing_id = l.id 
    JOIN users u ON b.user_id = u.id
    JOIN users o ON l.user_id = o.id
    $where_bookings
    ORDER BY b.status ASC, b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$tickets_query = "SELECT t.*, u.name as user_name FROM tickets t JOIN users u ON t.user_id = u.id";
if ($date_cond_t) { $tickets_query .= " WHERE $date_cond_t"; }
$tickets_query .= " ORDER BY t.created_at DESC";
$tickets = $pdo->query($tickets_query)->fetchAll(PDO::FETCH_ASSOC);

$all_reports = $pdo->query("
    SELECT r.*, l.title as listing_title, l.image as listing_image, u.name as reporter_name, u.email as reporter_email 
    FROM reports r 
    JOIN listings l ON r.listing_id = l.id 
    JOIN users u ON r.user_id = u.id 
    $where_reports
    ORDER BY r.status ASC, r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($all_users);
$total_listings = count($all_listings);
$total_bookings = count($all_bookings);
$total_reports = count($all_reports);

$cond_pending_listings = "WHERE status='pending'";
if ($date_cond_listings) { $cond_pending_listings .= " AND $date_cond_listings"; }
$pending_listings = $pdo->query("SELECT COUNT(*) FROM listings $cond_pending_listings")->fetchColumn();

$cond_pending_bookings = "WHERE status='pending_admin'";
if ($date_cond_bookings) { $cond_pending_bookings .= " AND $date_cond_bookings"; }
$pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings $cond_pending_bookings")->fetchColumn();

$cond_pending_reports = "WHERE status='pending'";
if ($date_cond_reports) { $cond_pending_reports .= " AND $date_cond_reports"; }
$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports $cond_pending_reports")->fetchColumn();

$cond_revenue = "WHERE status = 'approved'";
if ($date_cond_bookings) { $cond_revenue .= " AND $date_cond_bookings"; }
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings $cond_revenue")->fetchColumn();

$cond_open_tickets = "WHERE status='open'";
if ($date_cond_tickets) { $cond_open_tickets .= " AND $date_cond_tickets"; }
$open_tickets = $pdo->query("SELECT COUNT(*) FROM tickets $cond_open_tickets")->fetchColumn();

$top_earning_listings = $pdo->query("SELECT l.id, l.title, l.image, COUNT(b.id) as booking_count, SUM(b.total_price) as total_earned FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.status = 'approved' GROUP BY l.id ORDER BY total_earned DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$active_renters = $pdo->query("SELECT u.id, u.name, u.profile_picture, COUNT(b.id) as booking_count, SUM(b.total_price) as total_spent FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.status = 'approved' GROUP BY u.id ORDER BY booking_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$category_distribution = $pdo->query("SELECT category, COUNT(*) as count FROM listings GROUP BY category ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
$chart_labels_cat = array_column($category_distribution, 'category');
$chart_data_cat = array_column($category_distribution, 'count');

$booking_status = $pdo->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$chart_labels_status = [];
$chart_data_status = [];
foreach ($booking_status as $bs) {
    $chart_labels_status[] = ucfirst(str_replace('_', ' ', $bs['status']));
    $chart_data_status[] = (int)$bs['count'];
}

require_once '../includes/header.php';
?>

<div class="container" style="margin-bottom: 60px; margin-top:20px;">
    
    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    
    <h1 style="margin-bottom: 10px;"><?= __('Admin Dashboard') ?></h1>
    <p style="color:#64748b; margin-bottom:30px;"><?= __('Manage systems') ?></p>

    <!-- Date Filter -->
    <div style="background:var(--card-bg); padding:15px 20px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:30px;">
        <form method="GET" action="" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin:0; width:100%;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:20px;">📅</span>
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text-color);"><?= __('Filter by Date') ?></h3>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button type="submit" name="date_filter" value="all" class="btn <?= $date_filter === 'all' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'all' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('All Time') ?></button>
                    <button type="submit" name="date_filter" value="day" class="btn <?= $date_filter === 'day' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'day' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Today') ?></button>
                    <button type="submit" name="date_filter" value="week" class="btn <?= $date_filter === 'week' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'week' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Last 7 Days') ?></button>
                    <button type="submit" name="date_filter" value="month" class="btn <?= $date_filter === 'month' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'month' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Last 30 Days') ?></button>
                    <button type="submit" name="date_filter" value="custom" class="btn <?= $date_filter === 'custom' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'custom' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Custom') ?></button>
                </div>
                
                <?php if ($date_filter === 'custom'): ?>
                <input type="hidden" name="date_filter" value="custom">
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control" style="font-size:12px; padding:4px 8px; width:130px; height:32px; margin:0;">
                    <span style="color:#64748b; font-size:12px;">→</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control" style="font-size:12px; padding:4px 8px; width:130px; height:32px; margin:0;">
                    <button type="submit" class="btn btn-primary" style="font-size:12px; padding:4px 12px; height:32px; display:flex; align-items:center; justify-content:center;"><?= __('Apply') ?></button>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Quick Stats -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:20px; margin-bottom:40px;">
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color);"><?= $total_users ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">👤 <?= __('Users') ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color);"><?= $total_listings ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">🏠 <?= __('Listings') ?> <?php if($pending_listings > 0): ?><span class="badge" style="background:#f59e0b;"><?= $pending_listings ?></span><?php endif; ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color);"><?= $total_bookings ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">📅 <?= __('Bookings') ?> <?php if($pending_bookings > 0): ?><span class="badge" style="background:#f59e0b;"><?= $pending_bookings ?></span><?php endif; ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:1.8rem; font-weight:800; color:var(--success-color);">₪<?= number_format($total_revenue, 2) ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">💰 <?= __('Revenue') ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color);"><?= $total_reports ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">⚠️ <?= __('Reports') ?> <?php if($pending_reports > 0): ?><span class="badge" style="background:var(--error-color); color:white;"><?= $pending_reports ?></span><?php endif; ?></p>
        </div>
    </div>

    <!-- Tabs Links -->
    <div style="display:flex; gap:10px; margin-bottom:25px; flex-wrap:wrap;">
        <a href="admin.php?tab=analytics&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'analytics' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'analytics' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">📊 <?= __('Analytics') ?></a>
        <a href="admin.php?tab=listings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'listings' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'listings' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">🏠 <?= __('Listings') ?></a>
        <a href="admin.php?tab=bookings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'bookings' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'bookings' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">📅 <?= __('Bookings') ?></a>
        <a href="admin.php?tab=users&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'users' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'users' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">👤 <?= __('Users') ?></a>
        <a href="admin.php?tab=reports&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'reports' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'reports' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">⚠️ <?= __('Reports') ?></a>
        <a href="admin.php?tab=tickets&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'tickets' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'tickets' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">🎫 <?= __('Support Tickets') ?></a>
    </div>

    <!-- Contents -->
    <?php if($tab === 'analytics'): ?>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
            <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color);">
                <h3 style="margin-bottom:15px;">📊 <?= __('Bookings Distribution') ?></h3>
                <div style="height:250px;"><canvas id="bookingStatusChart"></canvas></div>
            </div>
            <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color);">
                <h3 style="margin-bottom:15px;">🏷️ <?= __('Category Share') ?></h3>
                <div style="height:250px;"><canvas id="categoryDistChart"></canvas></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color);">
                <h3 style="margin-bottom:15px;">🏆 <?= __('Top Listings Earnings') ?></h3>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach($top_earning_listings as $tl): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
                            <span><?= htmlspecialchars($tl['title']) ?></span>
                            <strong>₪<?= number_format($tl['total_earned'], 2) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color);">
                <h3 style="margin-bottom:15px;">👤 <?= __('Most Active Renters') ?></h3>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach($active_renters as $ar): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
                            <span><?= htmlspecialchars($ar['name']) ?></span>
                            <strong>₪<?= number_format($ar['total_spent'], 2) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php elseif($tab === 'listings'): ?>
        <?php if (count($all_listings) > 0): ?>
            <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden;">
                <thead>
                    <tr style="text-align:left; border-bottom: 2px solid var(--border-color); background:var(--bg-color);">
                        <th style="padding:15px;">Listing</th>
                        <th style="padding:15px;">Owner</th>
                        <th style="padding:15px;">Status</th>
                        <th style="padding:15px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_listings as $l): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding:12px;">
                                <a href="<?= BASE_URL ?>listings/view_listing.php?id=<?= $l['id'] ?>" style="color:inherit; text-decoration:none;"><strong><?= htmlspecialchars($l['title']) ?></strong></a>
                            </td>
                            <td style="padding:12px;"><?= htmlspecialchars($l['owner_name']) ?></td>
                            <td style="padding:12px;">
                                <span class="badge" style="<?= $l['status'] === 'approved' ? 'background:var(--success-color);' : 'background:#f59e0b; color:black;' ?>"><?= ucfirst($l['status']) ?></span>
                            </td>
                            <td style="padding:12px;">
                                <?php if($l['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="approve_listing">
                                        <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="btn btn-primary" style="padding:4px 10px; font-size:11px;">✅ <?= __('Approve') ?></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete or Deactivate listing?');">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="delete_listing">
                                    <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:11px;">🗑️ <?= __('Delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); text-align:center; color:#718096;">
                📭 <?= __('No listings found matching these criteria.') ?>
            </div>
        <?php endif; ?>

    <?php elseif($tab === 'bookings'): ?>
        <?php if (count($all_bookings) > 0): ?>
            <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden;">
                <thead>
                    <tr style="text-align:left; border-bottom: 2px solid var(--border-color); background:var(--bg-color);">
                        <th style="padding:15px;">Listing</th>
                        <th style="padding:15px;">Renter</th>
                        <th style="padding:15px;">Price</th>
                        <th style="padding:15px;">Status</th>
                        <th style="padding:15px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_bookings as $b): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding:12px;"><?= htmlspecialchars($b['listing_title']) ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($b['renter_name']) ?></td>
                            <td style="padding:12px;">₪<?= htmlspecialchars($b['total_price']) ?></td>
                            <td style="padding:12px;">
                                <span class="badge"><?= ucfirst(str_replace('_', ' ', $b['status'])) ?></span>
                            </td>
                            <td style="padding:12px;">
                                <?php if($b['status'] === 'pending_admin'): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="approve_booking">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn btn-primary" style="padding:4px 10px; font-size:11px;">✅ <?= __('Approve') ?></button>
                                    </form>
                                    <form method="POST" style="display:inline-block; margin-left:5px;" onsubmit="return confirm('Reject booking?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="reject_booking">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:11px;">❌ <?= __('Reject') ?></button>
                                    </form>
                                <?php elseif($b['status'] !== 'rejected'): ?>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Cancel booking?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="reject_booking">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:11px;"><?= __('Cancel') ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); text-align:center; color:#718096;">
                📭 <?= __('No bookings found matching these criteria.') ?>
            </div>
        <?php endif; ?>

    <?php elseif($tab === 'users'): ?>
        <?php if (count($all_users) > 0): ?>
            <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden;">
                <thead>
                    <tr style="text-align:left; border-bottom: 2px solid var(--border-color); background:var(--bg-color);">
                        <th style="padding:15px;">User</th>
                        <th style="padding:15px;">Email</th>
                        <th style="padding:15px;">Status</th>
                        <th style="padding:15px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_users as $u): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding:12px;">
                                <a href="<?= BASE_URL ?>user/user.php?id=<?= $u['id'] ?>" style="color:inherit; text-decoration:none; display:flex; align-items:center; gap:8px;">
                                    <img src="<?= htmlspecialchars(BASE_URL . (!empty($u['profile_picture']) ? $u['profile_picture'] : 'assets/img/default_avatar.png')) ?>" style="width:24px; height:24px; border-radius:50%; object-fit:cover;">
                                    <?= htmlspecialchars($u['name']) ?>
                                </a>
                            </td>
                            <td style="padding:12px;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="padding:12px;">
                                <span class="badge" style="<?= $u['is_blocked'] ? 'background:var(--error-color); color:white;' : 'background:var(--success-color); color:white;' ?>"><?= $u['is_blocked'] ? 'Blocked' : 'Active' ?></span>
                            </td>
                            <td style="padding:12px;">
                                <?php if($u['role'] !== 'admin'): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <?php if($u['is_blocked']): ?>
                                            <input type="hidden" name="action" value="unblock_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-primary" style="padding:4px 10px; font-size:11px;">🔓 <?= __('Unblock') ?></button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="block_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:11px;" onclick="return confirm('Block this user?');">🔒 <?= __('Block') ?></button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); text-align:center; color:#718096;">
                📭 <?= __('No users found matching these criteria.') ?>
            </div>
        <?php endif; ?>

    <?php elseif($tab === 'reports'): ?>
        <?php if (count($all_reports) > 0): ?>
            <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden;">
                <thead>
                    <tr style="text-align:left; border-bottom: 2px solid var(--border-color); background:var(--bg-color);">
                        <th style="padding:15px;">Listing</th>
                        <th style="padding:15px;">Reporter</th>
                        <th style="padding:15px;">Reason</th>
                        <th style="padding:15px;">Status</th>
                        <th style="padding:15px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_reports as $rep): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding:12px;">
                                <a href="<?= BASE_URL ?>listings/view_listing.php?id=<?= $rep['listing_id'] ?>" style="color:inherit; text-decoration:none;"><strong><?= htmlspecialchars($rep['listing_title']) ?></strong></a>
                            </td>
                            <td style="padding:12px;"><?= htmlspecialchars($rep['reporter_name']) ?></td>
                            <td style="padding:12px; font-style:italic;"><?= htmlspecialchars($rep['reason']) ?></td>
                            <td style="padding:12px;">
                                <span class="badge"><?= ucfirst($rep['status']) ?></span>
                            </td>
                            <td style="padding:12px;">
                                <?php if($rep['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="resolve_report">
                                        <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                                        <button type="submit" class="btn btn-primary" style="padding:4px 10px; font-size:11px;">✅ <?= __('Resolve') ?></button>
                                    </form>
                                    <form method="POST" style="display:inline-block; margin-left:5px;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="dismiss_report">
                                        <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:11px;">❌ <?= __('Dismiss') ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); text-align:center; color:#718096;">
                📭 <?= __('No reports found matching these criteria.') ?>
            </div>
        <?php endif; ?>

    <?php elseif($tab === 'tickets'): ?>
        <?php if (count($tickets) > 0): ?>
            <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden;">
                <thead>
                    <tr style="text-align:left; border-bottom: 2px solid var(--border-color); background:var(--bg-color);">
                        <th style="padding:15px;">ID</th>
                        <th style="padding:15px;">User</th>
                        <th style="padding:15px;">Subject</th>
                        <th style="padding:15px;">Status</th>
                        <th style="padding:15px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tickets as $t): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding:12px;">#<?= $t['id'] ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($t['user_name']) ?></td>
                            <td style="padding:12px; font-weight:600;"><?= htmlspecialchars($t['subject']) ?></td>
                            <td style="padding:12px;">
                                <span class="badge"><?= ucfirst($t['status']) ?></span>
                            </td>
                            <td style="padding:12px;">
                                <a href="<?= BASE_URL ?>support/view_ticket.php?id=<?= $t['id'] ?>" class="btn btn-primary" style="padding:4px 10px; font-size:11px;">👁️ <?= __('View') ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); text-align:center; color:#718096;">
                📭 <?= __('No support tickets found matching these criteria.') ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const statusCtx = document.getElementById('bookingStatusChart');
    if (!statusCtx) return;

    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($chart_labels_status) ?>,
            datasets: [{
                data: <?= json_encode($chart_data_status) ?>,
                backgroundColor: ['#3182ce', '#e53e3e', '#718096', '#f59e0b', '#805ad5']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    const catCtx = document.getElementById('categoryDistChart');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chart_labels_cat) ?>,
            datasets: [{
                data: <?= json_encode($chart_data_cat) ?>,
                backgroundColor: ['#3182ce', '#38a169', '#dd6b20', '#e53e3e', '#805ad5']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
