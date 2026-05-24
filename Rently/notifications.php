<?php
// notifications.php - View and manage user notifications
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) { redirect('login.php'); }

$user_id = $_SESSION['user_id'];

// Handle Mark as Read
if (isset($_GET['read'])) {
    $notif_id = (int) $_GET['read'];
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$notif_id, $user_id]);
    redirect('notifications.php');
}

// Handle Mark All Read
if (isset($_GET['read_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    redirect('notifications.php');
}

// Fetch user notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px; max-width:800px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h1><?= __('Notifications') ?></h1>
        <?php if(count($notifications) > 0): ?>
            <a href="notifications.php?read_all=1" class="btn" style="background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-color);"><?= __('Mark all read') ?></a>
        <?php endif; ?>
    </div>

    <?php if(count($notifications) > 0): ?>
        <div style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); overflow:hidden;">
            <?php foreach($notifications as $n): ?>
                <div style="padding:20px; border-bottom:1px solid var(--border-color); display:flex; gap:15px; align-items:flex-start; <?= !$n['is_read'] ? 'background:rgba(49, 130, 206, 0.05);' : '' ?>">
                    <div style="font-size:24px; padding-top:2px;">
                        <?= !$n['is_read'] ? '🔴' : '⚪' ?>
                    </div>
                    <div style="flex:1;">
                        <p style="margin-bottom:5px; <?= !$n['is_read'] ? 'font-weight:700;' : '' ?>"><?= htmlspecialchars($n['message']) ?></p>
                        <p style="font-size:12px; color:var(--text-color); opacity:0.7;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></p>
                    </div>
                    <?php if(!$n['is_read']): ?>
                        <a href="notifications.php?read=<?= $n['id'] ?>" class="btn" style="padding:6px 14px; font-size:12px; background:var(--primary-color); color:white;"><?= __('Mark Read') ?></a>
                    <?php endif; ?>
                    <?php if($n['link'] !== '#'): ?>
                        <a href="<?= htmlspecialchars($n['link']) ?>" class="btn" style="padding:6px 14px; font-size:12px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-color);"><?= __('View') ?></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:60px 20px; background:var(--card-bg); border-radius:16px; border:1px solid var(--border-color);">
            <div style="font-size:50px; margin-bottom:15px; opacity:0.5;">📭</div>
            <h3 style="color:var(--text-color); opacity:0.7;"><?= __('No notifications yet.') ?></h3>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
