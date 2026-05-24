<?php
// user.php - Public User Profile
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) { redirect('index.php'); }

$id = (int)$_GET['id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_blocked = 0");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found or is blocked.");
}

// Get user's approved listings
$stmt = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? AND status = 'approved' ORDER BY created_at DESC");
$stmt->execute([$id]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px;">
    <!-- Profile Card -->
    <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:40px; display:flex; gap:30px; align-items:center; flex-wrap:wrap;">
        <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'assets/img/default_avatar.png') ?>" 
             style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid var(--primary-color);">
        <div style="flex:1; min-width:250px;">
            <h2 style="margin-bottom:5px;"><?= htmlspecialchars($user['name']) ?></h2>
            <p style="color:#718096; margin-bottom:15px;"><?= nl2br(htmlspecialchars($user['bio'] ?? __('No bio provided.'))) ?></p>
            <p style="margin-bottom: 5px;">📧 <a href="mailto:<?= htmlspecialchars($user['email']) ?>" style="color:var(--primary-color); text-decoration:none;"><?= htmlspecialchars($user['email']) ?></a></p>
            <?php if($user['phone']): ?><p>📱 <a href="tel:<?= htmlspecialchars($user['phone']) ?>" style="color:var(--primary-color); text-decoration:none;"><?= htmlspecialchars($user['phone']) ?></a></p><?php endif; ?>
        </div>
    </div>

    <!-- User Listings -->
    <h3 style="margin-bottom:20px;">🏠 <?= htmlspecialchars($user['name']) ?>'s <?= __('Listings') ?></h3>
    <?php if(count($listings) > 0): ?>
        <div class="grid" style="margin-bottom: 40px;">
            <?php foreach($listings as $l): ?>
                <a href="view_listing.php?id=<?= $l['id'] ?>" class="card animate-fade-in" style="text-decoration:none; color:inherit;">
                    <div class="card-img-wrapper" style="position:relative;">
                        <img src="<?= htmlspecialchars($l['image']) ?>" alt="<?= htmlspecialchars($l['title']) ?>" loading="lazy">
                        <span class="badge" style="position:absolute; top:15px; left:15px;"><?= htmlspecialchars($l['category']) ?></span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($l['title']) ?></h3>
                        <p style="color:#718096; font-size:14px;">📍 <?= htmlspecialchars($l['city']) ?></p>
                        <p class="card-price" style="margin-top:auto;">₪<?= htmlspecialchars($l['price']) ?> <span style="font-size:14px; color:#a0aec0; font-weight:normal;">/ <?= htmlspecialchars($l['price_type']) ?></span></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#718096;"><?= __('This user has no active listings.') ?></p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
