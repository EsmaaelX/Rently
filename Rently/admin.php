<?php
// admin.php - Admin Dashboard with Listings, Bookings & User Management
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isAdmin()) { die("Access Denied. Admins only."); }

// Handle Approve Listing
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $pdo->prepare("UPDATE listings SET status = 'approved' WHERE id = ?")->execute([$id]);
    redirect('admin.php?tab=listings');
}

// Handle Delete Listing
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM listings WHERE id = ?");
    $stmt->execute([$id]);
    $listing = $stmt->fetch();
    if($listing && file_exists($listing['image'])) { unlink($listing['image']); }
    $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$id]);
    redirect('admin.php?tab=listings');
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
    redirect('admin.php?tab=bookings');
}

// Handle Reject Booking / Cancel Booking
if (isset($_GET['reject_booking'])) {
    $bid = (int)$_GET['reject_booking'];
    $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bid]);
    
    // Notify Renter
    $bk = $pdo->prepare("SELECT b.user_id as renter_id, l.user_id as owner_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
    $bk->execute([$bid]);
    $bData = $bk->fetch();
    if ($bData) {
        $msg = "Your booking request for \"{$bData['title']}\" was rejected or cancelled by the Admin.";
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$bData['renter_id'], $msg, 'profile.php']);
    }
    redirect('admin.php?tab=bookings');
}

// Handle Block/Unblock User
if (isset($_GET['block_user'])) {
    $uid = (int)$_GET['block_user'];
    if ($uid !== 1) { $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?")->execute([$uid]); }
    redirect('admin.php?tab=users');
}
if (isset($_GET['unblock_user'])) {
    $uid = (int)$_GET['unblock_user'];
    $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?")->execute([$uid]);
    redirect('admin.php?tab=users');
}

// Current tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'listings';

// Fetch all data
$all_listings = $pdo->query("SELECT l.*, u.name as owner_name, u.email as owner_email FROM listings l JOIN users u ON l.user_id = u.id ORDER BY l.status ASC, l.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_bookings = $pdo->query("
    SELECT b.*, l.title as listing_title, l.image as listing_image, l.price, l.price_type,
           u.name as renter_name, u.email as renter_email,
           o.name as owner_name
    FROM bookings b 
    JOIN listings l ON b.listing_id = l.id 
    JOIN users u ON b.user_id = u.id
    JOIN users o ON l.user_id = o.id
    ORDER BY b.status ASC, b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all support tickets
$ticketsStmt = $pdo->query("SELECT t.*, u.name as user_name FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
$tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_users = count($all_users);
$total_listings = count($all_listings);
$total_bookings = count($all_bookings);
$pending_listings = $pdo->query("SELECT COUNT(*) FROM listings WHERE status='pending'")->fetchColumn();
$pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending_admin'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'approved'")->fetchColumn();

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px;">
    <h1 style="margin-bottom: 10px;"><?= __('Admin Dashboard') ?></h1>
    <p style="color:#64748b; margin-bottom:30px;"><?= __('Manage listings') ?></p>

    <!-- Stats Cards -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:20px; margin-bottom:40px;">
        <div style="background:var(--card-bg); padding:25px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:2.5rem; font-weight:800; color:var(--primary-color);"><?= $total_users ?></div>
            <p style="color:#64748b; font-size:14px;">👤 <?= __('Users') ?></p>
        </div>
        <div style="background:var(--card-bg); padding:25px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:2.5rem; font-weight:800; color:var(--primary-color);"><?= $total_listings ?></div>
            <p style="color:#64748b; font-size:14px;">🏠 <?= __('Listings') ?> <?php if($pending_listings > 0): ?><span class="badge" style="font-size:10px; padding:2px 8px;"><?= $pending_listings ?> <?= __('Pending') ?></span><?php endif; ?></p>
        </div>
        <div style="background:var(--card-bg); padding:25px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:2.5rem; font-weight:800; color:var(--primary-color);"><?= $total_bookings ?></div>
            <p style="color:#64748b; font-size:14px;">📅 <?= __('Bookings') ?> <?php if($pending_bookings > 0): ?><span class="badge" style="font-size:10px; padding:2px 8px;"><?= $pending_bookings ?> <?= __('Pending') ?></span><?php endif; ?></p>
        </div>
        <div style="background:var(--card-bg); padding:25px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
            <div style="font-size:2.5rem; font-weight:800; color:var(--success-color);">₪<?= number_format($total_revenue, 2) ?></div>
            <p style="color:#64748b; font-size:14px;">💰 <?= __('Revenue') ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div style="display:flex; gap:10px; margin-bottom:25px; flex-wrap:wrap;">
        <a href="admin.php?tab=listings" class="btn <?= $tab === 'listings' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'listings' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">🏠 <?= __('Listings') ?> <?php if($pending_listings > 0): ?>(<?= $pending_listings ?>)<?php endif; ?></a>
        <a href="admin.php?tab=bookings" class="btn <?= $tab === 'bookings' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'bookings' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">📅 <?= __('Bookings') ?> <?php if($pending_bookings > 0): ?>(<?= $pending_bookings ?>)<?php endif; ?></a>
        <a href="admin.php?tab=users" class="btn <?= $tab === 'users' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'users' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">👤 <?= __('Users') ?></a>
        <a href="admin.php?tab=tickets" class="btn <?= $tab === 'tickets' ? 'btn-primary' : '' ?>" style="<?= $tab !== 'tickets' ? 'background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color);' : '' ?>">🎫 <?= __('Support Tickets') ?></a>
    </div>

    <?php if($tab === 'listings'): ?>
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
                    <td style="padding:12px;"><img src="<?= htmlspecialchars($l['image']) ?>" width="80" style="border-radius:10px; object-fit:cover; height:60px;" loading="lazy"></td>
                    <td style="padding:12px;">
                        <strong><?= htmlspecialchars($l['title']) ?></strong><br>
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
                            <a href="admin.php?approve=<?= $l['id'] ?>&tab=listings" class="btn btn-primary" style="padding:6px 14px; font-size:12px;">✅ <?= __('Approve') ?></a>
                        <?php endif; ?>
                        <a href="admin.php?delete=<?= $l['id'] ?>&tab=listings" class="btn btn-danger" style="padding:6px 14px; font-size:12px;" onclick="return confirm('Delete this listing?');">🗑️ <?= __('Delete') ?></a>
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
                            <img src="<?= htmlspecialchars($b['listing_image']) ?>" width="50" style="border-radius:8px; height:40px; object-fit:cover;" loading="lazy">
                            <div>
                                <strong style="font-size:13px;"><?= htmlspecialchars($b['listing_title']) ?></strong><br>
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
                            <a href="admin.php?approve_booking=<?= $b['id'] ?>&tab=bookings" class="btn btn-primary" style="padding:5px 12px; font-size:12px;">✅</a>
                            <a href="admin.php?reject_booking=<?= $b['id'] ?>&tab=bookings" class="btn btn-danger" style="padding:5px 12px; font-size:12px;" onclick="return confirm('Reject this booking?');">❌</a>
                        <?php elseif($b['status'] !== 'rejected'): ?>
                            <a href="admin.php?reject_booking=<?= $b['id'] ?>&tab=bookings" class="btn btn-danger" style="padding:5px 12px; font-size:12px;" onclick="return confirm('Cancel this approved/pending booking?');"><?= __('Cancel') ?></a>
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
                    <td style="padding:12px;"><strong><?= htmlspecialchars($u['name']) ?></strong></td>
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
                                <a href="admin.php?unblock_user=<?= $u['id'] ?>&tab=users" class="btn btn-primary" style="padding:5px 14px; font-size:12px;"><?= __('Unblock') ?></a>
                            <?php else: ?>
                                <a href="admin.php?block_user=<?= $u['id'] ?>&tab=users" class="btn btn-danger" style="padding:5px 14px; font-size:12px;" onclick="return confirm('Block this user?');"><?= __('Block') ?></a>
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
