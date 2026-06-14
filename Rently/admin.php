<?php
// admin.php - Admin Dashboard with Listings, Bookings & User Management
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isAdmin()) { die("Access Denied. Admins only."); }

// Get date filter (day, week, month, year, all)
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$custom_params = "";
if ($date_filter === 'custom') {
    $custom_params = "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date);
}

// Handle Approve Listing
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $pdo->prepare("UPDATE listings SET status = 'approved' WHERE id = ?")->execute([$id]);
    redirect('admin.php?tab=listings&date_filter=' . $date_filter . $custom_params);
}

// Handle Delete Listing
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM listings WHERE id = ?");
    $stmt->execute([$id]);
    $listing = $stmt->fetch();
    if($listing && file_exists($listing['image'])) { unlink($listing['image']); }
    $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$id]);
    redirect('admin.php?tab=listings&date_filter=' . $date_filter . $custom_params);
}

// Handle Approve Booking (Admin approves first)
if (isset($_GET['approve_booking'])) {
    $bid = (int)$_GET['approve_booking'];
    $pdo->prepare("UPDATE bookings SET status = 'pending_owner' WHERE id = ?")->execute([$bid]);
    
    // Notify Owner
    $bk = $pdo->prepare("SELECT b.user_id as renter_id, l.user_id as owner_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
    $bk->execute([$bid]);
    $bData = $bk->fetch();
    if ($bData) {
        $msg = "Admin approved the booking request for your listing \"{$bData['title']}\". It is now awaiting your final approval.";
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$bData['owner_id'], $msg, 'profile.php']);
    }
    redirect('admin.php?tab=bookings&date_filter=' . $date_filter . $custom_params);
}

// Handle Reject Booking / Cancel Booking
if (isset($_GET['reject_booking'])) {
    $bid = (int)$_GET['reject_booking'];
    
    $stmt = $pdo->prepare("SELECT listing_id FROM bookings WHERE id = ?");
    $stmt->execute([$bid]);
    $listing_id = $stmt->fetchColumn();

    $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bid]);
    
    // Notify Renter
    $bk = $pdo->prepare("SELECT b.user_id as renter_id, l.user_id as owner_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
    $bk->execute([$bid]);
    $bData = $bk->fetch();
    if ($bData) {
        $msg = "Your booking request for \"{$bData['title']}\" was rejected or cancelled by the Admin.";
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$bData['renter_id'], $msg, 'profile.php']);
    }

    if ($listing_id) {
        promoteWaitlistedBookings($pdo, $listing_id);
    }

    redirect('admin.php?tab=bookings&date_filter=' . $date_filter . $custom_params);
}

// Handle Block/Unblock User
if (isset($_GET['block_user'])) {
    $uid = (int)$_GET['block_user'];
    if ($uid !== 1) { $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?")->execute([$uid]); }
    redirect('admin.php?tab=users&date_filter=' . $date_filter . $custom_params);
}
if (isset($_GET['unblock_user'])) {
    $uid = (int)$_GET['unblock_user'];
    $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?")->execute([$uid]);
    redirect('admin.php?tab=users&date_filter=' . $date_filter . $custom_params);
}

// Current tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'analytics';

// Build date condition for queries
$date_cond = "";
if ($date_filter === 'day') {
    $date_cond = "created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
} elseif ($date_filter === 'week') {
    $date_cond = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $date_cond = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($date_filter === 'year') {
    $date_cond = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
} elseif ($date_filter === 'custom') {
    if (!empty($start_date) && !empty($end_date)) {
        $date_cond = "created_at >= " . $pdo->quote($start_date . " 00:00:00") . " AND created_at <= " . $pdo->quote($end_date . " 23:59:59");
    } elseif (!empty($start_date)) {
        $date_cond = "created_at >= " . $pdo->quote($start_date . " 00:00:00");
    } elseif (!empty($end_date)) {
        $date_cond = "created_at <= " . $pdo->quote($end_date . " 23:59:59");
    }
}

$where_users = $date_cond ? "WHERE $date_cond" : "";
$where_listings = $date_cond ? "WHERE l.$date_cond" : "";
$where_bookings = $date_cond ? "WHERE b.$date_cond" : "";

// Fetch all data
$all_listings = $pdo->query("SELECT l.*, u.name as owner_name, u.email as owner_email FROM listings l JOIN users u ON l.user_id = u.id $where_listings ORDER BY l.status ASC, l.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_users = $pdo->query("SELECT * FROM users $where_users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
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

// Fetch all support tickets
$tickets_query = "SELECT t.*, u.name as user_name FROM tickets t JOIN users u ON t.user_id = u.id";
if ($date_cond) {
    $tickets_query .= " WHERE t.$date_cond";
}
$tickets_query .= " ORDER BY t.created_at DESC";
$tickets = $pdo->query($tickets_query)->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_users = count($all_users);
$total_listings = count($all_listings);
$total_bookings = count($all_bookings);

$cond_pending_listings = "WHERE status='pending'";
if ($date_cond) {
    $cond_pending_listings .= " AND $date_cond";
}
$pending_listings = $pdo->query("SELECT COUNT(*) FROM listings $cond_pending_listings")->fetchColumn();

$cond_pending_bookings = "WHERE status='pending_admin'";
if ($date_cond) {
    $cond_pending_bookings .= " AND $date_cond";
}
$pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings $cond_pending_bookings")->fetchColumn();

$cond_revenue = "WHERE status = 'approved'";
if ($date_cond) {
    $cond_revenue .= " AND $date_cond";
}
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings $cond_revenue")->fetchColumn();

$cond_open_tickets = "WHERE status='open'";
if ($date_cond) {
    $cond_open_tickets .= " AND $date_cond";
}
$open_tickets = $pdo->query("SELECT COUNT(*) FROM tickets $cond_open_tickets")->fetchColumn();

$cond_reviews = $date_cond ? "WHERE $date_cond" : "";
$total_reviews = $pdo->query("SELECT COUNT(*) FROM reviews $cond_reviews")->fetchColumn();

$cond_favorites = $date_cond ? "WHERE $date_cond" : "";
$total_favorites = $pdo->query("SELECT COUNT(*) FROM favorites $cond_favorites")->fetchColumn();

// Analytics Queries
$cond_top_earning = "WHERE b.status = 'approved'";
if ($date_cond) {
    $cond_top_earning .= " AND b." . $date_cond;
}
$top_earning_listings = $pdo->query("SELECT l.id, l.title, l.image, COUNT(b.id) as booking_count, SUM(b.total_price) as total_earned FROM bookings b JOIN listings l ON b.listing_id = l.id $cond_top_earning GROUP BY l.id ORDER BY total_earned DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$cond_popular = "WHERE b.status = 'approved'";
if ($date_cond) {
    $cond_popular .= " AND b." . $date_cond;
}
$popular_listings = $pdo->query("SELECT l.id, l.title, l.image, COUNT(b.id) as booking_count FROM bookings b JOIN listings l ON b.listing_id = l.id $cond_popular GROUP BY l.id ORDER BY booking_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$cond_top_rated = "";
if ($date_cond) {
    $cond_top_rated = "WHERE r." . $date_cond;
}
$top_rated_owners = $pdo->query("SELECT u.id, u.name, u.profile_picture, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count FROM reviews r JOIN listings l ON r.listing_id = l.id JOIN users u ON l.user_id = u.id $cond_top_rated GROUP BY u.id HAVING review_count >= 1 ORDER BY avg_rating DESC, review_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$cond_active_renters = "WHERE b.status = 'approved'";
if ($date_cond) {
    $cond_active_renters .= " AND b." . $date_cond;
}
$active_renters = $pdo->query("SELECT u.id, u.name, u.profile_picture, COUNT(b.id) as booking_count, SUM(b.total_price) as total_spent FROM bookings b JOIN users u ON b.user_id = u.id $cond_active_renters GROUP BY u.id ORDER BY booking_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px;">
    <h1 style="margin-bottom: 10px;"><?= __('Admin Dashboard') ?></h1>
    <p style="color:#64748b; margin-bottom:30px;"><?= __('Manage listings') ?></p>

    <!-- Date Filter Box -->
    <div style="background:var(--card-bg); padding:15px 20px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:30px;">
        <form method="GET" action="admin.php" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin:0; width:100%;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:20px;">📅</span>
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text-color);"><?= __('Filter by Date') ?></h3>
                    <p style="margin:0; font-size:12px; color:#64748b;"><?= __('Show stats for selected period') ?></p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <!-- Quick Filters -->
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button type="submit" name="date_filter" value="all" class="btn <?= $date_filter === 'all' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'all' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('All Time') ?></button>
                    <button type="submit" name="date_filter" value="day" class="btn <?= $date_filter === 'day' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'day' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Today') ?></button>
                    <button type="submit" name="date_filter" value="week" class="btn <?= $date_filter === 'week' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'week' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Last 7 Days') ?></button>
                    <button type="submit" name="date_filter" value="month" class="btn <?= $date_filter === 'month' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'month' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Last 30 Days') ?></button>
                    <button type="submit" name="date_filter" value="year" class="btn <?= $date_filter === 'year' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'year' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Last Year') ?></button>
                    <button type="submit" name="date_filter" value="custom" class="btn <?= $date_filter === 'custom' ? 'btn-primary' : '' ?>" style="font-size:13px; padding:6px 14px; <?= $date_filter !== 'custom' ? 'background:none; color:var(--text-color); border:1px solid var(--border-color);' : '' ?>"><?= __('Custom') ?></button>
                </div>

                <!-- Custom Range Picker Inputs -->
                <?php if ($date_filter === 'custom'): ?>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control" style="font-size:12px; padding:4px 8px; width:130px; height:32px; margin:0;" placeholder="<?= __('Start Date') ?>">
                    <span style="color:#64748b; font-size:12px;">→</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control" style="font-size:12px; padding:4px 8px; width:130px; height:32px; margin:0;" placeholder="<?= __('End Date') ?>">
                    <button type="submit" class="btn btn-primary" style="font-size:12px; padding:4px 12px; height:32px; display:flex; align-items:center; justify-content:center;"><?= __('Apply') ?></button>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:20px; margin-bottom:40px;">
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light); display:flex; flex-direction:column; justify-content:center;">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color); white-space:nowrap; line-height:1.2;"><?= $total_users ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">👤 <?= __('Users') ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light); display:flex; flex-direction:column; justify-content:center;">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color); white-space:nowrap; line-height:1.2;"><?= $total_listings ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">🏠 <?= __('Listings') ?> <?php if($pending_listings > 0): ?><span class="badge" style="font-size:10px; padding:2px 8px; margin:0;"><?= $pending_listings ?> <?= __('Pending') ?></span><?php endif; ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light); display:flex; flex-direction:column; justify-content:center;">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color); white-space:nowrap; line-height:1.2;"><?= $total_bookings ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">📅 <?= __('Bookings') ?> <?php if($pending_bookings > 0): ?><span class="badge" style="font-size:10px; padding:2px 8px; margin:0;"><?= $pending_bookings ?> <?= __('Pending') ?></span><?php endif; ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light); display:flex; flex-direction:column; justify-content:center;">
            <div style="font-size:1.8rem; font-weight:800; color:var(--success-color); white-space:nowrap; line-height:1.2;">₪<?= number_format($total_revenue, 2) ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">💰 <?= __('Revenue') ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light); display:flex; flex-direction:column; justify-content:center;">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color); white-space:nowrap; line-height:1.2;"><?= $open_tickets ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">🎫 <?= __('Open Tickets') ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light); display:flex; flex-direction:column; justify-content:center;">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color); white-space:nowrap; line-height:1.2;"><?= $total_reviews ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">⭐ <?= __('Reviews') ?></p>
        </div>
        <div style="background:var(--card-bg); padding:20px 15px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light); display:flex; flex-direction:column; justify-content:center;">
            <div style="font-size:1.8rem; font-weight:800; color:var(--primary-color); white-space:nowrap; line-height:1.2;"><?= $total_favorites ?></div>
            <p style="color:#64748b; font-size:14px; margin-top:8px;">❤️ <?= __('Favorites') ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div style="display:flex; gap:10px; margin-bottom:25px; flex-wrap:wrap;">
        <a href="admin.php?tab=analytics&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'analytics' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'analytics' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">📊 <?= __('Analytics') ?></a>
        <a href="admin.php?tab=listings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'listings' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'listings' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">🏠 <?= __('Listings') ?> <?php if($pending_listings > 0): ?>(<?= $pending_listings ?>)<?php endif; ?></a>
        <a href="admin.php?tab=bookings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'bookings' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'bookings' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">📅 <?= __('Bookings') ?> <?php if($pending_bookings > 0): ?>(<?= $pending_bookings ?>)<?php endif; ?></a>
        <a href="admin.php?tab=users&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'users' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'users' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">👤 <?= __('Users') ?></a>
        <a href="admin.php?tab=tickets&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn <?= $tab === 'tickets' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'tickets' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">🎫 <?= __('Support Tickets') ?></a>
    </div>

    <?php if($tab === 'analytics'): ?>
    <!-- Analytics Dashboard -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
        <!-- Top Earning Listings -->
        <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light);">
            <h3 style="margin-bottom:15px; color:var(--primary-color);">💰 <?= __('Top Earning Listings') ?></h3>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <?php foreach($top_earning_listings as $l): ?>
                <div style="display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <a href="view_listing.php?id=<?= $l['id'] ?>"><img src="<?= htmlspecialchars($l['image']) ?>" style="width:50px; height:50px; border-radius:8px; object-fit:cover;"></a>
                    <div style="flex:1;">
                        <a href="view_listing.php?id=<?= $l['id'] ?>" style="text-decoration:none; color:inherit;"><strong><?= htmlspecialchars($l['title']) ?></strong></a>
                        <div style="color:#64748b; font-size:12px;"><?= $l['booking_count'] ?> <?= __('bookings') ?></div>
                    </div>
                    <strong style="color:var(--success-color);">₪<?= number_format($l['total_earned']) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Most Popular Listings -->
        <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light);">
            <h3 style="margin-bottom:15px; color:var(--primary-color);">🔥 <?= __('Most Popular Listings') ?></h3>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <?php foreach($popular_listings as $l): ?>
                <div style="display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <a href="view_listing.php?id=<?= $l['id'] ?>"><img src="<?= htmlspecialchars($l['image']) ?>" style="width:50px; height:50px; border-radius:8px; object-fit:cover;"></a>
                    <div style="flex:1;">
                        <a href="view_listing.php?id=<?= $l['id'] ?>" style="text-decoration:none; color:inherit;"><strong><?= htmlspecialchars($l['title']) ?></strong></a>
                    </div>
                    <strong style="background:var(--primary-color); color:white; padding:4px 10px; border-radius:20px; font-size:12px;"><?= $l['booking_count'] ?> <?= __('Bookings') ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Rated Owners -->
        <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light);">
            <h3 style="margin-bottom:15px; color:var(--primary-color);">⭐ <?= __('Top Rated Owners') ?></h3>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <?php foreach($top_rated_owners as $u): ?>
                <div style="display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <a href="user.php?id=<?= $u['id'] ?>"><img src="<?= htmlspecialchars($u['profile_picture'] ?? 'assets/img/default_avatar.png') ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;"></a>
                    <div style="flex:1;">
                        <a href="user.php?id=<?= $u['id'] ?>" style="text-decoration:none; color:inherit;"><strong><?= htmlspecialchars($u['name']) ?></strong></a>
                        <div style="color:#64748b; font-size:12px;"><?= $u['review_count'] ?> <?= __('reviews') ?></div>
                    </div>
                    <strong style="color:#eab308;"><?= number_format($u['avg_rating'], 1) ?> ★</strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Most Active Renters -->
        <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light);">
            <h3 style="margin-bottom:15px; color:var(--primary-color);">🚀 <?= __('Most Active Renters') ?></h3>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <?php foreach($active_renters as $u): ?>
                <div style="display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <a href="user.php?id=<?= $u['id'] ?>"><img src="<?= htmlspecialchars($u['profile_picture'] ?? 'assets/img/default_avatar.png') ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;"></a>
                    <div style="flex:1;">
                        <a href="user.php?id=<?= $u['id'] ?>" style="text-decoration:none; color:inherit;"><strong><?= htmlspecialchars($u['name']) ?></strong></a>
                        <div style="color:#64748b; font-size:12px;"><?= $u['booking_count'] ?> <?= __('bookings') ?></div>
                    </div>
                    <strong style="color:var(--success-color);">₪<?= number_format($u['total_spent']) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php elseif($tab === 'listings'): ?>
    <!-- Listings Table -->
    <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-light);">
        <thead>
            <tr style="text-align:left; border-bottom: 2px solid var(--border-color);">
                <th style="padding:15px;"><?= __('Image') ?></th>
                <th style="padding:15px;"><?= __('Listing Details') ?></th>
                <th style="padding:15px;"><?= __('Owner') ?></th>
                <th style="padding:15px;"><?= __('Status') ?></th>
                <th style="padding:15px;"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($all_listings as $l): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding:12px;"><a href="view_listing.php?id=<?= $l['id'] ?>"><img src="<?= htmlspecialchars($l['image']) ?>" width="80" style="border-radius:10px; object-fit:cover; height:60px;" loading="lazy"></a></td>
                    <td style="padding:12px;">
                        <a href="view_listing.php?id=<?= $l['id'] ?>" style="color:inherit; text-decoration:none;"><strong><?= htmlspecialchars($l['title']) ?></strong></a><br>
                        <span style="font-size:12px;color:#64748b;">₪<?= htmlspecialchars($l['price']) ?>/<?= $l['price_type'] ?? 'day' ?> · <?= htmlspecialchars($l['category']) ?></span>
                    </td>
                    <td style="padding:12px;">
                        <?= htmlspecialchars($l['owner_name']) ?><br>
                        <span style="font-size:12px;color:#64748b;"><?= htmlspecialchars($l['owner_email']) ?></span>
                    </td>
                    <td style="padding:12px;">
                        <?php if($l['status'] == 'approved'): ?>
                            <span style="background:var(--success-color); color:white; padding:4px 12px; border-radius:20px; font-size:12px;"><?= __('Approved') ?></span>
                        <?php else: ?>
                            <span style="background:#f59e0b; color:black; padding:4px 12px; border-radius:20px; font-size:12px;"><?= __('Pending') ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;">
                        <?php if($l['status'] == 'pending'): ?>
                            <a href="admin.php?approve=<?= $l['id'] ?>&tab=listings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn btn-primary" style="padding:6px 14px; font-size:12px;">✅ <?= __('Approve') ?></a>
                        <?php endif; ?>
                        <a href="admin.php?delete=<?= $l['id'] ?>&tab=listings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn btn-danger" style="padding:6px 14px; font-size:12px;" onclick="return confirm('Delete this listing?');">🗑️ <?= __('Delete') ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php elseif($tab === 'bookings'): ?>
    <!-- Bookings Table -->
    <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-light);">
        <thead>
            <tr style="text-align:left; border-bottom: 2px solid var(--border-color);">
                <th style="padding:15px;"><?= __('Listing') ?></th>
                <th style="padding:15px;"><?= __('Renter') ?></th>
                <th style="padding:15px;"><?= __('Dates') ?></th>
                <th style="padding:15px;"><?= __('Amount') ?></th>
                <th style="padding:15px;"><?= __('Status') ?></th>
                <th style="padding:15px;"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($all_bookings as $b): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding:12px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <a href="view_listing.php?id=<?= $b['listing_id'] ?>"><img src="<?= htmlspecialchars($b['listing_image']) ?>" width="50" style="border-radius:8px; height:40px; object-fit:cover;" loading="lazy"></a>
                            <div>
                                <a href="view_listing.php?id=<?= $b['listing_id'] ?>" style="color:inherit; text-decoration:none;"><strong style="font-size:13px;"><?= htmlspecialchars($b['listing_title']) ?></strong></a><br>
                                <span style="font-size:11px; color:#64748b;"><?= __('Owner') ?>: <?= htmlspecialchars($b['owner_name']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px;">
                        <?= htmlspecialchars($b['renter_name']) ?><br>
                        <span style="font-size:12px;color:#64748b;"><?= htmlspecialchars($b['renter_email']) ?></span>
                    </td>
                    <td style="padding:12px; font-size:13px;">
                        <?= htmlspecialchars($b['start_date']) ?><br>→ <?= htmlspecialchars($b['end_date']) ?>
                    </td>
                    <td style="padding:12px;">
                        <strong style="color:var(--primary-color);">₪<?= number_format($b['total_price'], 2) ?></strong>
                    </td>
                    <td style="padding:12px;">
                        <?php if($b['status'] == 'approved'): ?>
                            <span style="background:var(--success-color); color:white; padding:4px 12px; border-radius:20px; font-size:12px;"><?= __('Approved') ?></span>
                        <?php elseif($b['status'] == 'rejected'): ?>
                            <span style="background:var(--error-color); color:white; padding:4px 12px; border-radius:20px; font-size:12px;"><?= __('Rejected') ?></span>
                        <?php elseif($b['status'] == 'pending_owner'): ?>
                            <span style="background:#64748b; color:white; padding:4px 12px; border-radius:20px; font-size:12px;"><?= __('Pending Owner') ?></span>
                        <?php else: ?>
                            <span style="background:#f59e0b; color:black; padding:4px 12px; border-radius:20px; font-size:12px;"><?= __('Pending Admin') ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;">
                        <?php if($b['status'] == 'pending_admin'): ?>
                            <a href="admin.php?approve_booking=<?= $b['id'] ?>&tab=bookings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn btn-primary" style="padding:5px 12px; font-size:12px;">✅</a>
                            <a href="admin.php?reject_booking=<?= $b['id'] ?>&tab=bookings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn btn-danger" style="padding:5px 12px; font-size:12px;" onclick="return confirm('Reject this booking?');">❌</a>
                        <?php elseif($b['status'] !== 'rejected'): ?>
                            <a href="admin.php?reject_booking=<?= $b['id'] ?>&tab=bookings&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn btn-danger" style="padding:5px 12px; font-size:12px;" onclick="return confirm('Cancel this approved/pending booking?');"><?= __('Cancel') ?></a>
                        <?php else: ?>
                            <span style="color:#64748b; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(count($all_bookings) === 0): ?>
                <tr><td colspan="6" style="padding:30px; text-align:center; color:#64748b;"><?= __('No bookings yet.') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <?php elseif($tab === 'users'): ?>
    <!-- Users Table -->
    <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-light);">
        <thead>
            <tr style="text-align:left; border-bottom: 2px solid var(--border-color);">
                <th style="padding:15px;">#</th>
                <th style="padding:15px;"><?= __('Name') ?></th>
                <th style="padding:15px;"><?= __('Email') ?></th>
                <th style="padding:15px;"><?= __('Role') ?></th>
                <th style="padding:15px;"><?= __('Status') ?></th>
                <th style="padding:15px;"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($all_users as $u): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding:12px;"><?= $u['id'] ?></td>
                    <td style="padding:12px;">
                        <a href="user.php?id=<?= $u['id'] ?>" style="color:inherit; text-decoration:none; display:flex; align-items:center; gap:10px;">
                            <img src="<?= htmlspecialchars($u['profile_picture'] ?? 'assets/img/default_avatar.png') ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                            <strong><?= htmlspecialchars($u['name']) ?></strong>
                        </a>
                    </td>
                    <td style="padding:12px;"><?= htmlspecialchars($u['email']) ?></td>
                    <td style="padding:12px;">
                        <span class="badge" style="<?= $u['role'] === 'admin' ? 'background:var(--primary-color);' : 'background:#64748b;' ?>"><?= ucfirst($u['role']) ?></span>
                    </td>
                    <td style="padding:12px;">
                        <?php if($u['is_blocked']): ?>
                            <span style="background:var(--error-color); color:white; padding:4px 12px; border-radius:20px; font-size:12px;">Blocked</span>
                        <?php else: ?>
                            <span style="background:var(--success-color); color:white; padding:4px 12px; border-radius:20px; font-size:12px;">Active</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;">
                        <?php if($u['role'] !== 'admin'): ?>
                            <?php if($u['is_blocked']): ?>
                                <a href="admin.php?unblock_user=<?= $u['id'] ?>&tab=users&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn btn-primary" style="padding:5px 14px; font-size:12px;"><?= __('Unblock') ?></a>
                            <?php else: ?>
                                <a href="admin.php?block_user=<?= $u['id'] ?>&tab=users&date_filter=<?= $date_filter ?><?= $custom_params ?>" class="btn btn-danger" style="padding:5px 14px; font-size:12px;" onclick="return confirm('Block this user?');"><?= __('Block') ?></a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#64748b; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php elseif($tab === 'tickets'): ?>
    <!-- Tickets Table -->
    <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse: collapse; background:var(--card-bg); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-light);">
        <thead>
            <tr style="text-align:left; border-bottom: 2px solid var(--border-color);">
                <th style="padding:15px;">ID</th>
                <th style="padding:15px;"><?= __('User') ?></th>
                <th style="padding:15px;"><?= __('Subject') ?></th>
                <th style="padding:15px;"><?= __('Status') ?></th>
                <th style="padding:15px;"><?= __('Date') ?></th>
                <th style="padding:15px;"><?= __('Action') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($tickets as $t): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding:12px;">#<?= $t['id'] ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($t['user_name']) ?></td>
                    <td style="padding:12px; font-weight:600;"><?= htmlspecialchars($t['subject']) ?></td>
                    <td style="padding:12px;">
                        <?php if($t['status'] === 'open'): ?>
                            <span style="background:#ecc94b; color:black; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Open') ?></span>
                        <?php else: ?>
                            <span style="background:#64748b; color:white; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Closed') ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px; color:#64748b;"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                    <td style="padding:12px;">
                        <a href="view_ticket.php?id=<?= $t['id'] ?>" class="btn btn-primary" style="padding:6px 12px; font-size:12px;"><?= __('View') ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(count($tickets) === 0): ?>
                <tr><td colspan="6" style="padding:30px; text-align:center; color:#64748b;"><?= __('No support tickets found.') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
