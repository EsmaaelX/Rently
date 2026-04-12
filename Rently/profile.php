<?php
// profile.php - User Profile with Edit, Booking Management, and Owner Approvals
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) { redirect('login.php'); }

$user_id = $_SESSION['user_id'];
$error = '';
$message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = cleanInput($_POST['name']);
    $phone = cleanInput($_POST['phone']);
    $bio = cleanInput($_POST['bio']);
    
    // Handle profile picture upload
    $pic_sql = "";
    $pic_params = [];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/avatars/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = time() . '_' . basename($_FILES['profile_pic']['name']);
        $destination = $uploadDir . $fileName;
        if ($_FILES['profile_pic']['size'] > 2000000) {
            $error = "Image too large. Max 2MB.";
        } else {
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination);
            $pic_sql = ", profile_picture = ?";
            $pic_params = [$destination];
        }
    }
    
    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, bio = ? $pic_sql WHERE id = ?");
        $params = array_merge([$name, $phone, $bio], $pic_params, [$user_id]);
        $stmt->execute($params);
        $message = __('Profile updated successfully!');
    }
}

// Handle booking approval/rejection (for listing owners)
if (isset($_GET['approve_booking'])) {
    $bid = (int) $_GET['approve_booking'];
    // Verify this booking belongs to one of the user's listings
    $check = $pdo->prepare("SELECT b.id, b.user_id as renter_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ? AND l.user_id = ?");
    $check->execute([$bid, $user_id]);
    $bData = $check->fetch();
    if ($bData) {
        $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?")->execute([$bid]);
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$bData['renter_id'], "Your booking for \"{$bData['title']}\" was approved!", "profile.php"]);
        $message = __('Booking approved!');
    }
    redirect('profile.php');
}
if (isset($_GET['reject_booking'])) {
    $bid = (int) $_GET['reject_booking'];
    $check = $pdo->prepare("SELECT b.id, b.user_id as renter_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ? AND l.user_id = ?");
    $check->execute([$bid, $user_id]);
    $bData = $check->fetch();
    if ($bData) {
        $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bid]);
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$bData['renter_id'], "Your booking for \"{$bData['title']}\" was rejected by the owner.", "profile.php"]);
    }
    redirect('profile.php');
}

// Handle cancel booking (for the booker/user)
if (isset($_GET['cancel_booking'])) {
    $bid = (int) $_GET['cancel_booking'];
    $bCheck = $pdo->prepare("SELECT created_at, status FROM bookings WHERE id = ? AND user_id = ? AND status != 'rejected'");
    $bCheck->execute([$bid, $user_id]);
    $bInfo = $bCheck->fetch();
    
    if ($bInfo) {
        $age = time() - strtotime($bInfo['created_at']);
        if (in_array($bInfo['status'], ['pending_admin', 'pending_owner']) || $age <= 7200) {
            $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?")->execute([$bid, $user_id]);
        }
    }
    redirect('profile.php');
}

// Handle delete listing
if (isset($_GET['delete_listing'])) {
    $lid = (int) $_GET['delete_listing'];
    $pdo->prepare("DELETE FROM listings WHERE id = ? AND user_id = ?")->execute([$lid, $user_id]);
    redirect('profile.php');
}

// Get user info (with new fields)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's listings
$stmt = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's bookings (as a renter)
$stmt = $pdo->prepare("
    SELECT b.*, l.title, l.city, l.price, l.image 
    FROM bookings b 
    JOIN listings l ON b.listing_id = l.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get booking requests on MY listings (for owner to approve/reject)
$stmt = $pdo->prepare("
    SELECT b.*, l.title as listing_title, l.image as listing_image, u.name as renter_name, u.email as renter_email
    FROM bookings b 
    JOIN listings l ON b.listing_id = l.id 
    JOIN users u ON b.user_id = u.id
    WHERE l.user_id = ? AND b.status = 'pending_owner'
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$booking_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px;">
    
    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    
    <!-- Profile Card -->
    <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:40px; display:flex; gap:30px; align-items:flex-start; flex-wrap:wrap;">
        <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'assets/img/default_avatar.png') ?>" 
             style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid var(--primary-color);">
        <div style="flex:1; min-width:250px;">
            <h2 style="margin-bottom:5px;"><?= htmlspecialchars($user['name']) ?></h2>
            <p style="color:#718096; margin-bottom:15px;"><?= htmlspecialchars($user['bio'] ?? __('No bio yet.')) ?></p>
            <p>📧 <?= htmlspecialchars($user['email']) ?></p>
            <p>📱 <?= htmlspecialchars($user['phone'] ?? 'N/A') ?></p>
        </div>
    </div>
    
    <!-- Edit Profile -->
    <details style="margin-bottom:40px; background:var(--card-bg); padding:25px; border-radius:16px; border:1px solid var(--border-color);">
        <summary style="cursor:pointer; font-size:1.2rem; font-weight:700;">✏️ <?= __('Edit Profile') ?></summary>
        <form method="POST" action="" enctype="multipart/form-data" style="margin-top:20px;">
            <div class="form-group">
                <label><?= __('Full Name') ?></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label><?= __('Phone') ?></label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= __('Bio') ?></label>
                <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label><?= __('Profile Picture') ?></label>
                <input type="file" name="profile_pic" class="form-control" accept="image/*">
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary"><?= __('Save Changes') ?></button>
        </form>
    </details>

    <!-- Booking Requests (Owner) -->
    <?php if(count($booking_requests) > 0): ?>
    <div style="margin-bottom:40px;">
        <h3 style="margin-bottom:20px;">🔔 <?= __('Booking Requests') ?> <span class="badge"><?= count($booking_requests) ?></span></h3>
        <?php foreach($booking_requests as $req): ?>
            <div style="background:var(--card-bg); padding:20px; border-radius:12px; border:1px solid var(--border-color); margin-bottom:15px; display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
                <img src="<?= htmlspecialchars($req['listing_image']) ?>" style="width:80px; height:60px; border-radius:8px; object-fit:cover;" loading="lazy">
                <div style="flex:1; min-width:200px;">
                    <strong><?= htmlspecialchars($req['listing_title']) ?></strong>
                    <p style="color:#718096; font-size:14px;">👤 <?= htmlspecialchars($req['renter_name']) ?> (<?= htmlspecialchars($req['renter_email']) ?>)</p>
                    <p style="font-size:14px;">📅 <?= htmlspecialchars($req['start_date']) ?> → <?= htmlspecialchars($req['end_date']) ?> · <strong>₪<?= htmlspecialchars($req['total_price']) ?></strong></p>
                </div>
                <div>
                    <a href="profile.php?approve_booking=<?= $req['id'] ?>" class="btn btn-primary" style="padding:8px 16px; font-size:13px;"><?= __('Approve') ?></a>
                    <a href="profile.php?reject_booking=<?= $req['id'] ?>" class="btn btn-danger" style="padding:8px 16px; font-size:13px;" onclick="return confirm('Reject this booking?');"><?= __('Reject') ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- My Bookings -->
    <h3 style="margin-bottom:20px;">📅 <?= __('My Bookings') ?></h3>
    <?php if(count($my_bookings) > 0): ?>
        <div class="grid" style="margin-bottom: 40px;">
            <?php foreach($my_bookings as $b): ?>
                <div class="card" style="flex-direction:row; height:auto; min-height:120px;">
                    <img src="<?= htmlspecialchars($b['image']) ?>" alt="<?= htmlspecialchars($b['title']) ?>" style="width:130px; object-fit:cover;" loading="lazy">
                    <div class="card-body" style="padding:15px;">
                        <h4 style="margin-bottom:5px;"><?= htmlspecialchars($b['title']) ?></h4>
                        <p style="font-size:14px; color:#718096; margin-bottom:5px;">📅 <?= htmlspecialchars($b['start_date']) ?> → <?= htmlspecialchars($b['end_date']) ?></p>
                        <p style="font-size:14px; margin-bottom:5px;">💰 ₪<?= htmlspecialchars($b['total_price']) ?></p>
                        <?php if($b['status'] === 'approved'): ?>
                            <span style="background:var(--success-color); color:white; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Approved') ?></span>
                            <?php if(time() - strtotime($b['created_at']) <= 7200): ?>
                                <a href="profile.php?cancel_booking=<?= $b['id'] ?>" style="font-size:13px; color:var(--error-color); margin-left:10px;" onclick="return confirm('Cancel this approved booking? You still have time within the 2-hour window.');"><?= __('Cancel') ?></a>
                            <?php endif; ?>
                        <?php elseif($b['status'] === 'rejected'): ?>
                            <span style="background:var(--error-color); color:white; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Rejected') ?></span>
                        <?php elseif($b['status'] === 'pending_owner'): ?>
                            <span style="background:#64748b; color:white; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Waiting for Owner') ?></span>
                            <a href="profile.php?cancel_booking=<?= $b['id'] ?>" style="font-size:13px; color:var(--error-color); margin-left:10px;" onclick="return confirm('Cancel this booking?');"><?= __('Cancel') ?></a>
                        <?php else: ?>
                            <span style="background:#ecc94b; color:black; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Waiting for Admin') ?></span>
                            <a href="profile.php?cancel_booking=<?= $b['id'] ?>" style="font-size:13px; color:var(--error-color); margin-left:10px;" onclick="return confirm('Cancel this booking?');"><?= __('Cancel') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="margin-bottom: 40px; color:#718096;"><?= __('No bookings yet.') ?></p>
    <?php endif; ?>

    <!-- My Listings -->
    <h3 style="margin-bottom:20px;">🏠 <?= __('My Listings') ?></h3>
    <?php if(count($my_listings) > 0): ?>
        <div class="grid" style="margin-bottom: 40px;">
            <?php foreach($my_listings as $l): ?>
                <div class="card">
                    <a href="view_listing.php?id=<?= $l['id'] ?>" style="text-decoration:none; color:inherit;">
                        <img src="<?= htmlspecialchars($l['image']) ?>" alt="<?= htmlspecialchars($l['title']) ?>" loading="lazy">
                        <div class="card-body" style="padding:15px;">
                            <h4 style="margin-bottom:5px; font-size:16px;"><?= htmlspecialchars($l['title']) ?></h4>
                            <p style="color:#718096; font-size:14px;">📍 <?= htmlspecialchars($l['city']) ?> · ₪<?= htmlspecialchars($l['price']) ?></p>
                            <?php if($l['status'] === 'approved'): ?>
                                <span style="background:var(--success-color); color:white; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Approved') ?></span>
                            <?php else: ?>
                                <span style="background:#ecc94b; color:black; padding:4px 10px; border-radius:20px; font-size:12px;"><?= __('Pending') ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div style="padding:0 15px 15px; display:flex; gap:10px;">
                        <a href="edit_listing.php?id=<?= $l['id'] ?>" class="btn btn-primary" style="padding:6px 14px; font-size:13px; flex:1; text-align:center;">✏️ <?= __('Edit') ?></a>
                        <a href="profile.php?delete_listing=<?= $l['id'] ?>" class="btn btn-danger" style="padding:6px 14px; font-size:13px; flex:1; text-align:center;" onclick="return confirm('Delete this listing?');">🗑️ <?= __('Delete') ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#718096;"><?= __('No listings yet.') ?> <a href="add_listing.php"><?= __('Add one!') ?></a></p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
