<?php
// listings/view_listing.php - Full listing details
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) { redirect(BASE_URL . 'index.php'); }

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT l.*, u.name as owner_name, u.profile_picture as owner_pic, (SELECT COUNT(*) FROM reports WHERE listing_id = l.id) as report_count FROM listings l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
$stmt->execute([$id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) { redirect(BASE_URL . 'index.php'); }

$current_user = $_SESSION['user_id'] ?? 0;
$is_admin = isAdmin();
if ($listing['status'] !== 'approved' && $listing['user_id'] != $current_user && !$is_admin) {
    redirect(BASE_URL . 'index.php');
}

$error = '';
$message = '';

// Handle Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_booking'])) {
    if (!isLoggedIn()) { redirect(BASE_URL . 'auth/login.php'); }
    if (isAdmin()) { $error = __('Admins cannot book items.'); }
    elseif (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $start_date = $_POST['start_date'];
        $end_date = ($listing['price_type'] === 'hour') ? $start_date : $_POST['end_date'];
        $start_time = ($listing['price_type'] === 'hour') ? $_POST['start_time'] : null;
        $end_time = ($listing['price_type'] === 'hour') ? $_POST['end_time'] : null;
        
        $start_ts = strtotime($start_date . ($start_time ? ' ' . $start_time : ''));
        $end_ts = strtotime($end_date . ($end_time ? ' ' . $end_time : ''));
        
        if ($listing['user_id'] == $current_user) {
            $error = __('You cannot book your own listing.');
        } elseif ($start_ts >= $end_ts) {
            $error = __('End time/date must be after start time/date.');
        } elseif ($start_ts < time()) {
            $error = __('You cannot book in the past.');
        } elseif (!checkAvailability($pdo, $id, $start_date, $end_date, $start_time, $end_time)) {
            $error = __('Sorry, these dates/times are already booked!');
        } else {
            $_SESSION['checkout'] = [
                'listing_id' => $id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'start_time' => $start_time,
                'end_time' => $end_time
            ];
            redirect(BASE_URL . 'bookings/checkout.php');
        }
    }
}

// Handle Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) { redirect(BASE_URL . 'auth/login.php'); }
    if (isAdmin()) { $error = __('Admins cannot leave reviews.'); }
    elseif (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $checkRent = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE listing_id = ? AND user_id = ? AND status = 'approved' AND (end_date < CURRENT_DATE OR (end_date = CURRENT_DATE AND end_time <= CURRENT_TIME))");
        $checkRent->execute([$id, $current_user]);
        
        $checkReview = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE listing_id = ? AND user_id = ?");
        $checkReview->execute([$id, $current_user]);
        
        if ($checkRent->fetchColumn() == 0) {
            $error = __('You can only review items you have rented and after the booking has completed.');
        } elseif ($checkReview->fetchColumn() > 0) {
            $error = __('You have already reviewed this listing.');
        } else {
            $rating = (int) $_POST['rating'];
            $comment = cleanInput($_POST['comment']);
            if ($rating < 1 || $rating > 5) {
                $error = __('Rating must be between 1 and 5.');
            } else {
                $insert = $pdo->prepare("INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                if ($insert->execute([$id, $current_user, $rating, $comment])) {
                    $message = __('Review added successfully!');
                } else {
                    $error = __('Failed to add review.');
                }
            }
        }
    }
}

// Handle Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    if (!isLoggedIn()) { redirect(BASE_URL . 'auth/login.php'); }
    elseif (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        if ($listing['user_id'] == $current_user) {
            $error = __('You cannot report your own listing.');
        } else {
            $reason = cleanInput($_POST['report_reason']);
            $insert = $pdo->prepare("INSERT INTO reports (listing_id, user_id, reason) VALUES (?, ?, ?)");
            if ($insert->execute([$id, $current_user, $reason])) {
                $msg_owner = "Your listing '{$listing['title']}' has been reported.";
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$listing['user_id'], $msg_owner, "listings/view_listing.php?id=$id"]);
                
                $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admins as $admin_id) {
                    $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$admin_id, "Listing '{$listing['title']}' has been reported.", "admin/admin.php?tab=reports"]);
                }
                
                $message = __('Report submitted successfully.');
            } else {
                $error = __('Failed to submit report.');
            }
        }
    }
}

// Handle Appeal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appeal'])) {
    if (!isLoggedIn() || $current_user != $listing['user_id']) { redirect(BASE_URL . 'auth/login.php'); }
    elseif (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $reason = cleanInput($_POST['appeal_reason']);
        
        $subject = "Appeal for Report on Listing #{$id}";
        $stmt_ticket = $pdo->prepare("INSERT INTO tickets (user_id, subject) VALUES (?, ?)");
        if ($stmt_ticket->execute([$current_user, $subject])) {
            $ticket_id = $pdo->lastInsertId();
            
            $msg_body = "Listing: {$listing['title']}\nAppeal Reason: $reason";
            $msgStmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
            $msgStmt->execute([$ticket_id, $current_user, $msg_body]);
            
            $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
            $nMsg = "Report Appeal: The owner of '{$listing['title']}' has appealed the reports against their listing.";
            foreach ($admins as $adm_id) {
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$adm_id, $nMsg, "support/view_ticket.php?id=$ticket_id"]);
            }
            
            $message = __('Appeal submitted successfully as a support ticket.');
        } else {
            $error = __('Failed to submit appeal.');
        }
    }
}

// Handle Delete Reports (Admin)
if (isset($_POST['delete_reports']) && isAdmin()) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $pdo->prepare("DELETE FROM reports WHERE listing_id = ?")->execute([$id]);
        
        $msg_owner = "Admin has reviewed and deleted the reports on your listing '{$listing['title']}'.";
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$listing['user_id'], $msg_owner, "listings/view_listing.php?id=$id"]);
        
        redirect(BASE_URL . "listings/view_listing.php?id=$id");
    }
}

$stmtImages = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id = ?");
$stmtImages->execute([$id]);
$additionalImages = $stmtImages->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT r.*, u.name, u.profile_picture FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.listing_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user has rented this listing to allow review
$has_rented = false;
if (isLoggedIn() && !isAdmin() && $current_user != $listing['user_id']) {
    $checkRent = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE listing_id = ? AND user_id = ? AND status = 'approved' AND (end_date < CURRENT_DATE OR (end_date = CURRENT_DATE AND (end_time IS NULL OR end_time <= CURRENT_TIME)))");
    $checkRent->execute([$id, $current_user]);
    $has_rented = ($checkRent->fetchColumn() > 0);
}

$rating_info = getAverageRating($pdo, $id);

if (isLoggedIn() && !isAdmin()) {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE category = ? AND id != ? AND status = 'approved' AND user_id != ? ORDER BY RAND() LIMIT 3");
    $stmt->execute([$listing['category'], $id, $current_user]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE category = ? AND id != ? AND status = 'approved' ORDER BY RAND() LIMIT 3");
    $stmt->execute([$listing['category'], $id]);
}
$suggested = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getDatesFromRange($start, $end) {
    if (!$start || !$end) return [];
    $dates = [];
    $period = new DatePeriod(new DateTime($start), new DateInterval('P1D'), (new DateTime($end))->modify('+1 day'));
    foreach ($period as $date) {
        $dates[] = $date->format('Y-m-d');
    }
    return $dates;
}

$stmtBookings = $pdo->prepare("SELECT start_date, start_time, end_date, end_time, status, user_id FROM bookings WHERE listing_id = ? AND status != 'rejected'");
$stmtBookings->execute([$id]);
$bookedDates = $stmtBookings->fetchAll(PDO::FETCH_ASSOC);

$disabledDates = [];
$redDates = [];
$blueDates = [];
$yellowDates = [];

foreach ($bookedDates as $b) {
    $range = getDatesFromRange($b['start_date'], $b['end_date']);
    if ($b['status'] === 'approved') {
        if ($b['user_id'] == $current_user) {
            $blueDates = array_merge($blueDates, $range);
        } else {
            $redDates = array_merge($redDates, $range);
            $disabledDates[] = ['from' => $b['start_date'], 'to' => $b['end_date']];
        }
    } else {
        if ($b['user_id'] == $current_user) {
            $blueDates = array_merge($blueDates, $range);
        } else {
            $yellowDates = array_merge($yellowDates, $range);
        }
    }
}

$priceLabel = ($listing['price_type'] ?? 'day') === 'hour' ? __('/ hour') : __('/ day');

require_once '../includes/header.php';
?>

<div class="container" style="margin-bottom: 60px;">
    <?php if($message): ?><div class="alert alert-success" style="margin-top:20px;"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error" style="margin-top:20px;"><?= $error ?></div><?php endif; ?>

    <div class="listing-layout">
        <div class="listing-main">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;">
                <?= htmlspecialchars($listing['title']) ?>
                <?php if($listing['report_count'] > 0): ?>
                    <span class="badge" style="background:var(--error-color); color:white; font-size:14px; vertical-align:middle; margin-left:10px;">⚠️ <?= __('Reported') ?> (<?= $listing['report_count'] ?>)</span>
                <?php endif; ?>
            </h1>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <p style="color: #718096; margin: 0; font-size: 1.1rem;">
                    📍 <?= htmlspecialchars($listing['city']) ?> &nbsp;|&nbsp; 
                    <a href="<?= BASE_URL ?>user/user.php?id=<?= $listing['user_id'] ?>" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                        <img src="<?= htmlspecialchars(BASE_URL . (!empty($listing['owner_pic']) ? $listing['owner_pic'] : 'assets/img/default_avatar.png')) ?>" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:2px solid var(--primary-color);">
                        <strong style="color:var(--primary-color);"><?= htmlspecialchars($listing['owner_name']) ?></strong>
                    </a>
                </p>
                <button id="shareBtn" onclick="copyShareLink()" style="background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-color); padding: 6px 12px; border-radius: 20px; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: background 0.2s;">
                    🔗 <?= __('Share') ?>
                </button>
            </div>
            
            <?php if($rating_info['total'] > 0): ?>
                <p class="stars" style="margin-bottom:20px;"><?= str_repeat('★', round($rating_info['avg'])) ?><span style="color:#e2e8f0;"><?= str_repeat('★', 5 - round($rating_info['avg'])) ?></span> <small style="color:#718096;"><?= $rating_info['avg'] ?>/5 (<?= $rating_info['total'] ?> <?= __('Reviews') ?>)</small></p>
            <?php endif; ?>
            
            <!-- Gallery -->
            <div class="gallery-container" style="margin-bottom: 30px;">
                <img id="mainGalleryImage" src="<?= htmlspecialchars(BASE_URL . $listing['image']) ?>" alt="<?= htmlspecialchars($listing['title']) ?>" class="listing-hero-img" style="width:100%; border-radius:16px; object-fit:cover; max-height:450px;" loading="lazy">
                
                <?php if (count($additionalImages) > 0): ?>
                    <div class="gallery-thumbnails" style="display:flex; gap:10px; margin-top:10px; overflow-x:auto;">
                        <img src="<?= htmlspecialchars(BASE_URL . $listing['image']) ?>" class="gallery-thumb active" style="width:80px; height:60px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid var(--primary-color);" onclick="updateGallery(this, '<?= htmlspecialchars(BASE_URL . $listing['image']) ?>')">
                        <?php foreach ($additionalImages as $img): ?>
                            <img src="<?= htmlspecialchars(BASE_URL . $img) ?>" class="gallery-thumb" style="width:80px; height:60px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid transparent;" onclick="updateGallery(this, '<?= htmlspecialchars(BASE_URL . $img) ?>')">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($listing['attributes'])): $attrs = json_decode($listing['attributes'], true); if($attrs && is_array($attrs)): ?>
            <div style="background:var(--card-bg); padding:20px 30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom: 30px; display:flex; gap:20px; flex-wrap:wrap;">
                <?php foreach($attrs as $key => $val): if($val !== ''): ?>
                    <div style="background:var(--bg-color); padding:10px 15px; border-radius:10px; border:1px solid var(--border-color);">
                        <small style="color:var(--text-color); opacity:0.7; text-transform:capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></small>
                        <div style="font-weight:600; font-size:1.1rem; color:var(--text-color);"><?= htmlspecialchars($val) ?></div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
            <?php endif; endif; ?>
            
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom: 30px;">
                <h3 style="margin-bottom:15px;"><?= __('Description') ?></h3>
                <p style="line-height: 1.8; font-size: 1.1rem;"><?= nl2br(htmlspecialchars($listing['description'])) ?></p>
            </div>

            <!-- Map -->
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom: 30px;">
                <h3 style="margin-bottom:15px;">📍 <?= __('Location') ?></h3>
                <iframe width="100%" height="300" style="border:0; border-radius:12px;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=<?= urlencode($listing['city']) ?>&output=embed"></iframe>
            </div>

            <!-- Reviews -->
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:30px;">
                <h3 style="margin-bottom:20px;"><?= __('Reviews') ?> (<?= count($reviews) ?>)</h3>
                
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $r): ?>
                        <div class="review-item" style="border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:15px;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                                <img src="<?= htmlspecialchars(BASE_URL . (!empty($r['profile_picture']) ? $r['profile_picture'] : 'assets/img/default_avatar.png')) ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                                <strong><?= htmlspecialchars($r['name']) ?></strong>
                                <div class="stars"><?= str_repeat('★', $r['rating']) ?><span style="color:#e2e8f0;"><?= str_repeat('★', 5 - $r['rating']) ?></span></div>
                            </div>
                            <p style="margin: 5px 0; font-style:italic;"><?= htmlspecialchars($r['comment']) ?></p>
                            <small style="color: #718096;"><?= date('M d, Y', strtotime($r['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#718096;"><?= __('No reviews yet.') ?></p>
                <?php endif; ?>

                <?php if(isLoggedIn() && !isAdmin() && $current_user != $listing['user_id']): ?>
                    <?php if($has_rented): ?>
                        <div style="margin-top: 30px; background: var(--bg-color); padding: 25px; border-radius: 12px; border:1px solid var(--border-color);">
                            <h4 style="margin-bottom:15px;"><?= __('Leave a Review') ?></h4>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <div class="form-group">
                                    <label><?= __('Rating') ?></label>
                                    <select name="rating" class="form-control" required style="width: 180px;">
                                        <option value="5">★★★★★ (5)</option>
                                        <option value="4">★★★★ (4)</option>
                                        <option value="3">★★★ (3)</option>
                                        <option value="2">★★ (2)</option>
                                        <option value="1">★ (1)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><?= __('Your Comment') ?></label>
                                    <textarea name="comment" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" name="submit_review" class="btn btn-primary"><?= __('Submit Review') ?></button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 30px; padding: 20px; background: var(--bg-color); border-radius: 12px; border: 1px dashed var(--border-color); text-align: center; color: #718096;">
                            <p style="margin: 0; font-size: 0.95rem;">ℹ️ <?= __('You can only leave a review after your booking has ended.') ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="listing-sidebar">
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-hover);">
                <div style="margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:15px;">
                    <h3 style="font-size: 2rem; color: var(--primary-color); margin:0;">₪<?= htmlspecialchars($listing['price']) ?> <span style="font-size:1rem; color:#718096;"><?= $priceLabel ?></span></h3>
                    <span class="badge" style="margin-top:10px;"><?= htmlspecialchars($listing['category']) ?></span>
                </div>
                
                <h4 style="margin-bottom:15px;"><?= __('Book this item') ?></h4>
                <?php if(isLoggedIn() && $current_user == $listing['user_id']): ?>
                    <div style="padding:20px; text-align:center; background:var(--bg-color); border-radius:8px;">
                        <p style="margin-bottom:15px; color:var(--primary-color);"><strong><?= __('This is your listing') ?></strong></p>
                        <a href="<?= BASE_URL ?>listings/edit_listing.php?id=<?= $id ?>" class="btn btn-primary" style="width:100%;"><?= __('Edit Listing') ?></a>
                        <?php if($listing['report_count'] > 0): ?>
                            <button type="button" id="appealBtn" class="btn btn-danger" style="width:100%; margin-top:10px;"><?= __('Appeal Reports') ?></button>
                        <?php endif; ?>
                    </div>
                <?php elseif(isLoggedIn() && !isAdmin()): ?>
                    <form method="POST" action="" id="bookingForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="listing_id" value="<?= $id ?>">
                        
                        <div class="form-group">
                            <label><?= __('Date') ?></label>
                            <input type="text" name="start_date" id="startDate" class="form-control" required placeholder="<?= __('Select Date') ?>">
                        </div>
                        
                        <?php if ($listing['price_type'] === 'day'): ?>
                            <div class="form-group">
                                <label><?= __('To Date') ?></label>
                                <input type="text" name="end_date" id="endDate" class="form-control" required placeholder="<?= __('Select Date') ?>">
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label><?= __('Start Time') ?></label>
                                <input type="time" name="start_time" id="startTime" class="form-control" required step="3600">
                            </div>
                            <div class="form-group">
                                <label><?= __('End Time') ?></label>
                                <input type="time" name="end_time" id="endTime" class="form-control" required step="3600">
                            </div>
                            <div id="hourlyOccupiedSlots" style="display:none; background:#fffff0; border:1px solid #f59e0b; padding:10px 15px; border-radius:8px; font-size:13px; margin-bottom:15px;">
                                ⚠️ <strong><?= __('Occupied hours on this date:') ?></strong>
                                <ul id="occupiedSlotsList" style="margin: 5px 0 0 15px; padding: 0;"></ul>
                            </div>
                        <?php endif; ?>
                        
                        <div id="pricePreview" style="display:none; background:var(--bg-color); padding:15px; border-radius:8px; margin-bottom:15px;">
                            <div style="display:flex; justify-content:space-between;"><span><?= __('Total') ?></span><strong id="totalPrice"></strong></div>
                        </div>
                        <button type="submit" name="start_booking" class="btn btn-primary" style="width:100%; padding:14px; font-size:16px;"><?= __('Proceed to Checkout') ?></button>
                    </form>
                <?php elseif(isAdmin()): ?>
                    <p style="color:var(--error-color); padding:15px; background:rgba(229, 62, 62, 0.1); border-radius:8px;"><?= __('Admins cannot book or review items.') ?></p>
                    <?php if($listing['report_count'] > 0): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <button type="submit" name="delete_reports" class="btn btn-danger" style="width:100%; margin-top:10px;" onclick="return confirm('<?= __('Are you sure you want to delete all reports for this listing?') ?>');"><?= __('Delete Reports') ?></button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="padding:20px; text-align:center; background:var(--bg-color); border-radius:8px;">
                        <p style="margin-bottom:15px;"><?= __('Please login to book.') ?></p>
                        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary" style="width:100%;"><?= __('Login') ?></a>
                    </div>
                <?php endif; ?>

                <?php if(isLoggedIn() && !isAdmin() && $current_user != $listing['user_id']): ?>
                    <button type="button" id="reportBtn" style="width:100%; margin-top:15px; padding:10px; background:transparent; border:1px solid var(--error-color); color:var(--error-color); border-radius:8px; cursor:pointer; transition:background 0.2s;"><?= __('Report this Listing') ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Suggestions -->
    <?php if(count($suggested) > 0): ?>
    <div style="margin-top: 50px;">
        <h2 style="margin-bottom: 25px;"><?= __('You May Also Like') ?></h2>
        <div class="grid">
            <?php foreach($suggested as $s): 
                $sRating = getAverageRating($pdo, $s['id']);
                $sPriceLabel = ($s['price_type'] ?? 'day') === 'hour' ? __('/ hour') : __('/ day');
            ?>
                <a href="<?= BASE_URL ?>listings/view_listing.php?id=<?= $s['id'] ?>" class="card animate-fade-in" style="text-decoration:none; color:inherit;">
                    <div class="card-img-wrapper" style="position:relative;">
                        <img src="<?= htmlspecialchars(BASE_URL . $s['image']) ?>" alt="<?= htmlspecialchars($s['title']) ?>" loading="lazy">
                        <span class="badge" style="position:absolute; top:15px; left:15px;"><?= htmlspecialchars($s['category']) ?></span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($s['title']) ?></h3>
                        <p style="color:#718096; font-size:14px;">📍 <?= htmlspecialchars($s['city']) ?></p>
                        <p class="card-price" style="margin-top:auto;">₪<?= htmlspecialchars($s['price']) ?> <span style="font-size:14px; color:#a0aec0; font-weight:normal;"><?= $sPriceLabel ?></span></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.flatpickr-day.date-red, .flatpickr-day.disabled.date-red { color: #fff !important; background-color: #e53e3e !important; border-color: #e53e3e !important; }
.flatpickr-day.date-blue, .flatpickr-day.disabled.date-blue { color: #fff !important; background-color: #3182ce !important; border-color: #3182ce !important; }
.flatpickr-day.date-yellow { color: #fff !important; background-color: #dd6b20 !important; border-color: #dd6b20 !important; }
</style>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
function updateGallery(thumb, src) {
    document.getElementById('mainGalleryImage').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.style.borderColor = 'transparent');
    thumb.style.borderColor = 'var(--primary-color)';
}

const price = <?= (float)$listing['price'] ?>;
const priceType = '<?= htmlspecialchars($listing['price_type']) ?>';
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');
const startTimeInput = document.getElementById('startTime');
const endTimeInput = document.getElementById('endTime');
const preview = document.getElementById('pricePreview');
const totalEl = document.getElementById('totalPrice');

const disabledDates = <?= json_encode($disabledDates) ?>;
const redDates = <?= json_encode($redDates) ?>;
const blueDates = <?= json_encode($blueDates) ?>;
const yellowDates = <?= json_encode($yellowDates) ?>;
const bookedHours = <?= json_encode($bookedDates) ?>;

function applyDateColors(dObj, dStr, fp, dayElem) {
    if (!dayElem.dateObj) return;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (dayElem.dateObj < today) return;
    const y = dayElem.dateObj.getFullYear();
    const m = String(dayElem.dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dayElem.dateObj.getDate()).padStart(2, '0');
    const dFormat = y + '-' + m + '-' + d;
    if (blueDates.includes(dFormat)) {
        dayElem.classList.add('date-blue');
    } else if (yellowDates.includes(dFormat)) {
        dayElem.classList.add('date-yellow');
    } else if (redDates.includes(dFormat)) {
        dayElem.classList.add('date-red');
    }
}

function updatePrice() {
    if (priceType === 'day') {
        if (startDateInput && endDateInput && startDateInput.value && endDateInput.value) {
            const days = Math.max(1, Math.ceil((new Date(endDateInput.value) - new Date(startDateInput.value)) / 86400000));
            const total = (price * days).toFixed(2);
            if (totalEl) totalEl.textContent = '₪' + total;
            if (preview) preview.style.display = 'block';
        }
    } else {
        if (startDateInput && startDateInput.value && startTimeInput && endTimeInput && startTimeInput.value && endTimeInput.value) {
            const startStr = startDateInput.value + ' ' + startTimeInput.value;
            const endStr = startDateInput.value + ' ' + endTimeInput.value;
            const hours = Math.max(1, (new Date(endStr) - new Date(startStr)) / 3600000);
            const total = (price * hours).toFixed(2);
            if (totalEl) totalEl.textContent = '₪' + total;
            if (preview) preview.style.display = 'block';
        }
    }
}

function showOccupiedSlotsForDate(dateStr) {
    const list = document.getElementById('occupiedSlotsList');
    const container = document.getElementById('hourlyOccupiedSlots');
    if (!list || !container) return;
    list.innerHTML = '';
    const matches = bookedHours.filter(b => b.start_date === dateStr && b.start_time);
    if (matches.length > 0) {
        matches.forEach(m => {
            const li = document.createElement('li');
            li.textContent = m.start_time.substring(0, 5) + ' - ' + m.end_time.substring(0, 5) + ' (' + (m.status === 'approved' ? 'Booked' : 'Pending') + ')';
            list.appendChild(li);
        });
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

if (startDateInput) {
    let fpEnd;
    const fpStart = flatpickr(startDateInput, {
        static: true,
        minDate: "today",
        disable: priceType === 'day' ? disabledDates : [],
        dateFormat: "Y-m-d",
        onDayCreate: applyDateColors,
        onChange: function(selectedDates, dateStr, instance) {
            if(fpEnd) fpEnd.set('minDate', dateStr);
            if(priceType === 'hour') { showOccupiedSlotsForDate(dateStr); }
            updatePrice();
        }
    });
    if (endDateInput) {
        fpEnd = flatpickr(endDateInput, {
            static: true,
            minDate: "today",
            disable: disabledDates,
            dateFormat: "Y-m-d",
            onDayCreate: applyDateColors,
            onChange: function(selectedDates, dateStr, instance) { updatePrice(); }
        });
    }
    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('change', updatePrice);
        endTimeInput.addEventListener('change', updatePrice);
    }
}
</script>

<!-- Report Modal -->
<div id="reportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--card-bg); padding:30px; border-radius:12px; width:90%; max-width:400px; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:15px; color:var(--error-color);">⚠️ <?= __('Report Listing') ?></h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group">
                <label><?= __('Why are you reporting this listing?') ?></label>
                <textarea name="report_reason" class="form-control" rows="4" required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" id="closeReportModal" class="btn" style="background:#e2e8f0; color:#4a5568;"><?= __('Cancel') ?></button>
                <button type="submit" name="submit_report" class="btn btn-danger"><?= __('Submit Report') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Appeal Modal -->
<div id="appealModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--card-bg); padding:30px; border-radius:12px; width:90%; max-width:400px; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:15px; color:var(--primary-color);">📢 <?= __('Appeal Reports') ?></h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group">
                <label><?= __('Why should the reports on this listing be deleted?') ?></label>
                <textarea name="appeal_reason" class="form-control" rows="4" required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" id="closeAppealModal" class="btn" style="background:#e2e8f0; color:#4a5568;"><?= __('Cancel') ?></button>
                <button type="submit" name="submit_appeal" class="btn btn-primary"><?= __('Submit Appeal') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function copyShareLink() {
    const url = window.location.origin + '<?= BASE_URL ?>listings/view_listing.php?id=' + <?= $id ?>;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('shareBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '✅ <?= __('Copied!') ?>';
        setTimeout(() => { btn.innerHTML = originalText; }, 2000);
    }).catch(err => { alert('<?= __('Failed to copy link.') ?>'); });
}

const reportBtn = document.getElementById('reportBtn');
const reportModal = document.getElementById('reportModal');
const closeReportModal = document.getElementById('closeReportModal');
if (reportBtn && reportModal) {
    reportBtn.addEventListener('click', () => reportModal.style.display = 'flex');
    closeReportModal.addEventListener('click', () => reportModal.style.display = 'none');
    window.addEventListener('click', (e) => { if (e.target === reportModal) reportModal.style.display = 'none'; });
}

const appealBtn = document.getElementById('appealBtn');
const appealModal = document.getElementById('appealModal');
const closeAppealModal = document.getElementById('closeAppealModal');
if (appealBtn && appealModal) {
    appealBtn.addEventListener('click', () => appealModal.style.display = 'flex');
    closeAppealModal.addEventListener('click', () => appealModal.style.display = 'none');
    window.addEventListener('click', (e) => { if (e.target === appealModal) appealModal.style.display = 'none'; });
}
</script>

<?php require_once '../includes/footer.php'; ?>
