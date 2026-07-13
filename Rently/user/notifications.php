<?php
// user/notifications.php - User Notifications
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) { redirect(BASE_URL . 'auth/login.php'); }

$user_id = $_SESSION['user_id'];
$error = '';
$message = '';

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
        redirect(BASE_URL . 'user/notifications.php');
    }
}

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container" style="margin-bottom: 60px; max-width:800px; margin-top:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h1>🔔 <?= __('Notifications') ?></h1>
        <?php if(count($notifs) > 0): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" name="mark_all_read" class="btn" style="background:var(--bg-color); color:var(--text-color); border:1px solid var(--border-color); font-size:13px;"><?= __('Mark all as read') ?></button>
            </form>
        <?php endif; ?>
    </div>

    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <?php if(count($notifs) > 0): ?>
        <div style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); overflow:hidden;">
            <?php foreach($notifs as $n): ?>
                <a href="<?= BASE_URL . htmlspecialchars($n['link']) ?>" style="display:block; text-decoration:none; color:inherit; border-bottom:1px solid var(--border-color); padding:20px; transition:background 0.2s; <?= $n['is_read'] ? '' : 'background:rgba(49, 130, 206, 0.05); font-weight:500;' ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <span style="font-size:15px; color:var(--text-color);"><?= htmlspecialchars($n['message']) ?></span>
                        <small style="color:var(--text-color); opacity:0.6; font-size:12px;"><?= date('M d, H:i', strtotime($n['created_at'])) ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center" style="padding: 80px 0;">
            <div style="font-size:60px; margin-bottom:20px;">🔔</div>
            <h2 style="color:#4a5568;"><?= __('All caught up!') ?></h2>
            <p style="color:#718096;"><?= __('You have no notifications.') ?></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
