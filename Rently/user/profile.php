<?php
// user/profile.php - User Dashboard & Settings
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) { redirect(BASE_URL . 'auth/login.php'); }

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$error = '';
$message = '';

// Soft delete active listing check
if (isset($_POST['delete_listing_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $lid = (int) $_POST['delete_listing_id'];
        
        $stmt = $pdo->prepare("SELECT user_id, title, image FROM listings WHERE id = ?");
        $stmt->execute([$lid]);
        $listing = $stmt->fetch();
        
        if ($listing && ($listing['user_id'] == $user_id || $is_admin)) {
            $checkHist = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE listing_id = ?");
            $checkHist->execute([$lid]);
            $hasHistory = ($checkHist->fetchColumn() > 0);
            
            if ($hasHistory) {
                $pdo->prepare("UPDATE listings SET status = 'rejected', rejection_reason = 'Deleted by User' WHERE id = ?")->execute([$lid]);
                $message = __('Listing deactivated (historical booking data preserved).');
            } else {
                if (file_exists('../' . $listing['image'])) {
                    unlink('../' . $listing['image']);
                }
                $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$lid]);
                $message = __('Listing deleted successfully.');
            }
        }
    }
}

// Booking cancel check (Must be at least 24 hours prior)
if (isset($_POST['cancel_booking_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $bid = (int) $_POST['cancel_booking_id'];
        
        $stmt = $pdo->prepare("SELECT b.*, l.title, l.user_id as owner_id FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ? AND b.user_id = ?");
        $stmt->execute([$bid, $user_id]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            $booking_start = strtotime($booking['start_date'] . ($booking['start_time'] ? ' ' . $booking['start_time'] : ''));
            $hours_diff = ($booking_start - time()) / 3600;
            
            if ($booking['status'] === 'approved' && $hours_diff < 24) {
                $error = __('You can only cancel confirmed bookings at least 24 hours prior to the start time.');
            } else {
                $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bid]);
                
                // Notify Owner
                $msg_owner = "A booking request for your listing \"{$booking['title']}\" was cancelled by the renter.";
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$booking['owner_id'], $msg_owner, 'user/profile.php']);
                
                promoteWaitlistedBookings($pdo, $booking['listing_id']);
                $message = __('Booking cancelled successfully.');
            }
        }
    }
}

// Booking actions by Owner (Accept/Reject)
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $bid = (int) $_POST['booking_id'];
        $action = $_POST['action'];
        
        $stmt = $pdo->prepare("SELECT b.*, l.user_id as owner_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
        $stmt->execute([$bid]);
        $booking = $stmt->fetch();
        
        if ($booking && $booking['owner_id'] == $user_id && $booking['status'] === 'pending_owner') {
            if ($action === 'accept') {
                if (!checkAvailability($pdo, $booking['listing_id'], $booking['start_date'], $booking['end_date'], $booking['start_time'], $booking['end_time'], $bid)) {
                    $error = __('Cannot accept. Overlapping dates/hours have already been approved!');
                } else {
                    $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?")->execute([$bid]);
                    
                    // Reject other overlapping pending requests
                    $stmtOverlap = $pdo->prepare("
                        SELECT id, user_id FROM bookings 
                        WHERE listing_id = ? AND id != ? AND status IN ('pending_owner', 'pending_admin', 'waitlist')
                        AND NOT (end_date < ? OR start_date > ?)
                    ");
                    $stmtOverlap->execute([$booking['listing_id'], $bid, $booking['start_date'], $booking['end_date']]);
                    $overlapping = $stmtOverlap->fetchAll();
                    
                    $stmtReject = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
                    foreach ($overlapping as $o) {
                        $start_ts = strtotime($booking['start_date'] . ($booking['start_time'] ? ' ' . $booking['start_time'] : ''));
                        $end_ts = strtotime($booking['end_date'] . ($booking['end_time'] ? ' ' . $booking['end_time'] : ''));
                        
                        $o_stmt = $pdo->prepare("SELECT start_date, end_date, start_time, end_time FROM bookings WHERE id = ?");
                        $o_stmt->execute([$o['id']]);
                        $o_time = $o_stmt->fetch();
                        $o_start = strtotime($o_time['start_date'] . ($o_time['start_time'] ? ' ' . $o_time['start_time'] : ''));
                        $o_end = strtotime($o_time['end_date'] . ($o_time['end_time'] ? ' ' . $o_time['end_time'] : ''));
                        
                        if (!($o_end <= $start_ts || $o_start >= $end_ts)) {
                            $stmtReject->execute([$o['id']]);
                            $msg_reject = "Your booking request for \"{$booking['title']}\" was rejected because another request was approved for overlapping dates/times.";
                            $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$o['user_id'], $msg_reject, 'user/profile.php']);
                        }
                    }
                    
                    $msg_renter = "Your booking request for \"{$booking['title']}\" has been approved by the owner! Check your profile to see contact details.";
                    $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$booking['user_id'], $msg_renter, 'user/profile.php']);
                    $message = __('Booking request approved!');
                }
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bid]);
                $msg_renter = "Your booking request for \"{$booking['title']}\" was rejected by the owner.";
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$booking['user_id'], $msg_renter, 'user/profile.php']);
                
                promoteWaitlistedBookings($pdo, $booking['listing_id']);
                $message = __('Booking request rejected.');
            }
        }
    }
}

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $name = cleanInput($_POST['name']);
        $phone = cleanInput($_POST['phone']);
        $bio = cleanInput($_POST['bio']);
        $email = cleanInput($_POST['email']);
        
        if (empty($name) || empty($email)) {
            $error = __('Name and Email cannot be empty.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('Invalid email.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error = __('Duplicate email. This email is already registered.');
            } else {
                $avatar_sql = "";
                $avatar_params = [];
                
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    // Upload relative to user subfolder
                    $avatarUpload = uploadImage('avatar', '../uploads/avatars/');
                    if (isset($avatarUpload['error'])) {
                        $error = $avatarUpload['error'];
                    } else {
                        if ($user['profile_picture'] && $user['profile_picture'] !== 'assets/img/default_avatar.png' && file_exists('../' . $user['profile_picture'])) {
                            unlink('../' . $user['profile_picture']);
                        }
                        $dbAvatarPath = str_replace('../', '', $avatarUpload['path']);
                        $avatar_sql = ", profile_picture = ?";
                        $avatar_params = [$dbAvatarPath];
                    }
                }
                
                if (empty($error)) {
                    $stmtUpdate = $pdo->prepare("UPDATE users SET name = ?, phone = ?, bio = ?, email = ? $avatar_sql WHERE id = ?");
                    $params = array_merge([$name, $phone, $bio, $email], $avatar_params, [$user_id]);
                    if ($stmtUpdate->execute($params)) {
                        $message = __('Profile updated successfully.');
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = __('Failed to update profile.');
                    }
                }
            }
        }
    }
}

// Fetch Listings
$stmt = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? AND status != 'rejected' ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Bookings made by User
$stmt = $pdo->prepare("
    SELECT b.*, l.title, l.image, l.price, l.price_type, l.user_id as owner_id, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
    FROM bookings b 
    JOIN listings l ON b.listing_id = l.id 
    JOIN users u ON l.user_id = u.id
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Booking requests for User's listings
$stmt = $pdo->prepare("
    SELECT b.*, l.title, l.image, l.price, l.price_type, u.name as renter_name, u.email as renter_email, u.phone as renter_phone, u.id as renter_id
    FROM bookings b 
    JOIN listings l ON b.listing_id = l.id 
    JOIN users u ON b.user_id = u.id
    WHERE l.user_id = ? 
    ORDER BY b.status ASC, b.created_at DESC
");
$stmt->execute([$user_id]);
$incoming_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch support tickets
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$my_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container" style="margin-bottom: 60px; margin-top:20px;">
    
    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <div class="listing-layout">
        <!-- Sidebar Dashboard Navigation -->
        <div class="listing-sidebar" style="position: sticky; top: 100px;">
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); text-align:center; box-shadow:var(--shadow-light);">
                <img src="<?= htmlspecialchars(BASE_URL . (!empty($user['profile_picture']) ? $user['profile_picture'] : 'assets/img/default_avatar.png')) ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover; margin-bottom:15px; border:3px solid var(--primary-color);">
                <h3 style="margin-bottom:5px;"><?= htmlspecialchars($user['name']) ?></h3>
                <p style="color:#718096; font-size:14px; margin-bottom:20px;"><?= htmlspecialchars($user['email']) ?></p>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <a href="<?= BASE_URL ?>user/user.php?id=<?= $_SESSION['user_id'] ?>" class="btn" style="background:var(--bg-color); color:var(--text-color); border:1px solid var(--border-color); font-size:13px; text-decoration:none;"><?= __('View Public Profile') ?></a>
                    <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-danger" style="font-size:13px; text-decoration:none;"><?= __('Logout') ?></a>
                </div>
            </div>
        </div>

        <!-- Main Content area -->
        <div class="listing-main">
            <!-- Tabs Menu -->
            <div style="display:flex; gap:15px; border-bottom: 2px solid var(--border-color); padding-bottom:15px; margin-bottom:30px; overflow-x:auto;">
                <button onclick="switchTab('my-listings')" id="tabBtn-my-listings" class="btn active-tab-btn" style="padding:8px 16px; font-weight:600; border:none; background:none; cursor:pointer; color:var(--text-color);"><?= __('My Listings') ?></button>
                <button onclick="switchTab('my-bookings')" id="tabBtn-my-bookings" class="btn" style="padding:8px 16px; font-weight:600; border:none; background:none; cursor:pointer; color:var(--text-color);"><?= __('My Bookings') ?></button>
                <button onclick="switchTab('incoming-bookings')" id="tabBtn-incoming-bookings" class="btn" style="padding:8px 16px; font-weight:600; border:none; background:none; cursor:pointer; color:var(--text-color);"><?= __('Booking Requests') ?></button>
                <button onclick="switchTab('support')" id="tabBtn-support" class="btn" style="padding:8px 16px; font-weight:600; border:none; background:none; cursor:pointer; color:var(--text-color);"><?= __('Support') ?></button>
                <button onclick="switchTab('settings')" id="tabBtn-settings" class="btn" style="padding:8px 16px; font-weight:600; border:none; background:none; cursor:pointer; color:var(--text-color);"><?= __('Profile Settings') ?></button>
            </div>

            <!-- Tab: My Listings -->
            <div id="tabContent-my-listings" class="tab-pane">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3>🏠 <?= __('My Listings') ?></h3>
                    <a href="<?= BASE_URL ?>listings/add_listing.php" class="btn btn-primary"><?= __('Add Listing') ?></a>
                </div>
                
                <?php if(count($listings) > 0): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:20px;">
                        <?php foreach($listings as $l): ?>
                            <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
                                <a href="<?= BASE_URL ?>listings/view_listing.php?id=<?= $l['id'] ?>" style="text-decoration:none; color:inherit;">
                                    <img src="<?= htmlspecialchars(BASE_URL . $l['image']) ?>" style="width:100%; height:150px; object-fit:cover; border-radius:12px 12px 0 0;" loading="lazy">
                                    <div style="padding:15px;">
                                        <h4 style="margin-bottom:5px;"><?= htmlspecialchars($l['title']) ?></h4>
                                        <span class="badge" style="<?= $l['status'] === 'approved' ? 'background:var(--success-color);' : 'background:#f59e0b; color:black;' ?>"><?= ucfirst($l['status']) ?></span>
                                    </div>
                                </a>
                                <div style="display:flex; gap:10px; padding:15px; border-top:1px solid var(--border-color); background:var(--bg-color);">
                                    <a href="<?= BASE_URL ?>listings/edit_listing.php?id=<?= $l['id'] ?>" class="btn" style="flex:1; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-color); font-size:12px; text-decoration:none; text-align:center; padding:6px 0; border-radius:6px;"><?= __('Edit') ?></a>
                                    <form method="POST" style="flex:1;" onsubmit="return confirm('<?= __('Are you sure you want to delete this listing?') ?>');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="delete_listing_id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="width:100%; font-size:12px; padding:6px 0; border-radius:6px;"><?= __('Delete') ?></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#718096;"><?= __('You have no listings.') ?></p>
                <?php endif; ?>
            </div>

            <!-- Tab: My Bookings -->
            <div id="tabContent-my-bookings" class="tab-pane" style="display:none;">
                <h3 style="margin-bottom:20px;">📅 <?= __('My Bookings') ?></h3>
                <?php if(count($my_bookings) > 0): ?>
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <?php foreach($my_bookings as $b): 
                            $bStart = strtotime($b['start_date'] . ($b['start_time'] ? ' ' . $b['start_time'] : ''));
                            $hours_diff = ($bStart - time()) / 3600;
                            $canCancel = ($b['status'] !== 'rejected' && ($b['status'] !== 'approved' || $hours_diff >= 24));
                        ?>
                            <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color); display:flex; gap:20px; align-items:center; flex-wrap:wrap; box-shadow:var(--shadow-light);">
                                <img src="<?= htmlspecialchars(BASE_URL . $b['image']) ?>" style="width:100px; height:80px; object-fit:cover; border-radius:8px;">
                                <div style="flex:1; min-width:200px;">
                                    <h4 style="margin-bottom:5px;"><a href="<?= BASE_URL ?>listings/view_listing.php?id=<?= $b['listing_id'] ?>" style="color:inherit; text-decoration:none;"><?= htmlspecialchars($b['title']) ?></a></h4>
                                    <p style="font-size:13px; color:#718096; margin-bottom:8px;">📅 <?= htmlspecialchars($b['start_date']) ?> <?= $b['start_time'] ? ' ' . $b['start_time'] : '' ?> &rarr; <?= htmlspecialchars($b['end_date']) ?> <?= $b['end_time'] ? ' ' . $b['end_time'] : '' ?></p>
                                    <p style="margin-bottom:5px;">💰 Total Price: <strong>₪<?= htmlspecialchars($b['total_price']) ?></strong></p>
                                    
                                    <!-- Contact details if approved -->
                                    <?php if ($b['status'] === 'approved'): ?>
                                        <div style="background:var(--bg-color); padding:10px 15px; border-radius:8px; border:1px solid var(--border-color); font-size:13px; margin-top:10px; color:var(--text-color);">
                                            <strong>📞 <?= __('Owner Contact Information:') ?></strong><br>
                                            Name: <?= htmlspecialchars($b['owner_name']) ?><br>
                                            Email: <a href="mailto:<?= htmlspecialchars($b['owner_email']) ?>"><?= htmlspecialchars($b['owner_email']) ?></a><br>
                                            Phone: <?= htmlspecialchars($b['owner_phone'] ?? 'None') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align:right;">
                                    <div class="badge" style="margin-bottom:10px; display:inline-block;"><?= ucfirst(str_replace('_', ' ', $b['status'])) ?></div>
                                    <?php if ($canCancel): ?>
                                        <form method="POST" onsubmit="return confirm('<?= __('Are you sure you want to cancel this booking?') ?>');" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="cancel_booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="font-size:12px; padding:6px 12px;"><?= __('Cancel Booking') ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#718096;"><?= __('You have made no bookings.') ?></p>
                <?php endif; ?>
            </div>

            <!-- Tab: Incoming Bookings -->
            <div id="tabContent-incoming-bookings" class="tab-pane" style="display:none;">
                <h3 style="margin-bottom:20px;">📥 <?= __('Incoming Booking Requests') ?></h3>
                <?php if(count($incoming_bookings) > 0): ?>
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <?php foreach($incoming_bookings as $b): ?>
                            <div style="background:var(--card-bg); padding:20px; border-radius:16px; border:1px solid var(--border-color); display:flex; gap:20px; align-items:center; flex-wrap:wrap; box-shadow:var(--shadow-light);">
                                <img src="<?= htmlspecialchars(BASE_URL . $b['image']) ?>" style="width:100px; height:80px; object-fit:cover; border-radius:8px;">
                                <div style="flex:1; min-width:200px;">
                                    <h4 style="margin-bottom:5px;"><a href="<?= BASE_URL ?>listings/view_listing.php?id=<?= $b['listing_id'] ?>" style="color:inherit; text-decoration:none;"><?= htmlspecialchars($b['title']) ?></a></h4>
                                    <p style="font-size:13px; color:#718096; margin-bottom:8px;">📅 <?= htmlspecialchars($b['start_date']) ?> <?= $b['start_time'] ? ' ' . $b['start_time'] : '' ?> &rarr; <?= htmlspecialchars($b['end_date']) ?> <?= $b['end_time'] ? ' ' . $b['end_time'] : '' ?></p>
                                    <p style="margin-bottom:5px;">👤 Renter: <strong><a href="<?= BASE_URL ?>user/user.php?id=<?= $b['renter_id'] ?>" style="color:var(--primary-color); text-decoration:none;"><?= htmlspecialchars($b['renter_name']) ?></a></strong></p>
                                    <p style="margin-bottom:5px;">💰 Expected Earnings: <strong>₪<?= htmlspecialchars($b['total_price']) ?></strong></p>
                                    
                                    <!-- Contact details if approved -->
                                    <?php if ($b['status'] === 'approved'): ?>
                                        <div style="background:var(--bg-color); padding:10px 15px; border-radius:8px; border:1px solid var(--border-color); font-size:13px; margin-top:10px; color:var(--text-color);">
                                            <strong>📞 <?= __('Renter Contact Information:') ?></strong><br>
                                            Name: <?= htmlspecialchars($b['renter_name']) ?><br>
                                            Email: <a href="mailto:<?= htmlspecialchars($b['renter_email']) ?>"><?= htmlspecialchars($b['renter_email']) ?></a><br>
                                            Phone: <?= htmlspecialchars($b['renter_phone'] ?? 'None') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align:right;">
                                    <div class="badge" style="margin-bottom:10px; display:inline-block;"><?= ucfirst(str_replace('_', ' ', $b['status'])) ?></div>
                                    <?php if ($b['status'] === 'pending_owner'): ?>
                                        <div style="display:flex; gap:10px; margin-top:10px;">
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="action" value="accept">
                                                <button type="submit" class="btn btn-primary" style="font-size:12px; padding:6px 12px;">✅ <?= __('Accept') ?></button>
                                            </form>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('<?= __('Reject this booking?') ?>');">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger" style="font-size:12px; padding:6px 12px;">❌ <?= __('Reject') ?></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#718096;"><?= __('No incoming booking requests.') ?></p>
                <?php endif; ?>
            </div>

            <!-- Tab: Support -->
            <div id="tabContent-support" class="tab-pane" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3>🎫 <?= __('My Support Tickets') ?></h3>
                    <a href="<?= BASE_URL ?>support/support.php" class="btn btn-primary"><?= __('Open Support Center') ?></a>
                </div>
                
                <?php if(count($my_tickets) > 0): ?>
                    <div style="display:flex; flex-direction:column; gap:15px;">
                        <?php foreach($my_tickets as $t): ?>
                            <div style="background:var(--card-bg); padding:20px; border-radius:12px; border:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; box-shadow:var(--shadow-light);">
                                <div>
                                    <h4 style="margin-bottom:5px;"><a href="<?= BASE_URL ?>support/view_ticket.php?id=<?= $t['id'] ?>" style="color:inherit; text-decoration:none;"><?= htmlspecialchars($t['subject']) ?></a></h4>
                                    <small style="color:#718096;"><?= __('Opened on') ?>: <?= date('M d, Y', strtotime($t['created_at'])) ?></small>
                                </div>
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <?php if($t['status'] === 'open'): ?>
                                        <span style="background:#ecc94b; color:black; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;"><?= __('Open') ?></span>
                                    <?php elseif($t['status'] === 'answered'): ?>
                                        <span style="background:var(--success-color); color:white; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;"><?= __('Answered') ?></span>
                                    <?php else: ?>
                                        <span style="background:#64748b; color:white; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Closed') ?></span>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>support/view_ticket.php?id=<?= $t['id'] ?>" class="btn" style="background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-color); font-size:12px; text-decoration:none; padding:6px 12px; border-radius:6px;"><?= __('View') ?></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#718096;"><?= __('You have no open tickets.') ?></p>
                <?php endif; ?>
            </div>

            <!-- Tab: Profile Settings -->
            <div id="tabContent-settings" class="tab-pane" style="display:none;">
                <h3 style="margin-bottom:20px;">⚙️ <?= __('Profile Settings') ?></h3>
                <form method="POST" action="" enctype="multipart/form-data" style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light);">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label><?= __('Full Name') ?></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= __('Email Address') ?></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= __('Phone Number') ?></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. +972 50-123-4567">
                    </div>
                    <div class="form-group">
                        <label><?= __('Bio / Description') ?></label>
                        <textarea name="bio" class="form-control" rows="4" placeholder="<?= __('Tell other users about yourself...') ?>"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= __('Avatar Upload') ?></label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= __('Save Profile Settings') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    // Hide all tab panes
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.style.display = 'none';
    });
    // Remove active style from tab buttons
    document.querySelectorAll('.listing-main button.btn').forEach(btn => {
        btn.classList.remove('active-tab-btn');
    });
    // Show select tab pane
    const activePane = document.getElementById('tabContent-' + tabId);
    if(activePane) activePane.style.display = 'block';
    
    // Set active button
    const activeBtn = document.getElementById('tabBtn-' + tabId);
    if(activeBtn) activeBtn.classList.add('active-tab-btn');
    
    // Save tab position in localStorage
    localStorage.setItem('profile_active_tab', tabId);
}

document.addEventListener('DOMContentLoaded', function() {
    const savedTab = localStorage.getItem('profile_active_tab') || 'my-listings';
    switchTab(savedTab);
});
</script>

<?php require_once '../includes/footer.php'; ?>
