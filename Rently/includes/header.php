<?php
// includes/header.php
require_once 'includes/lang.php';
$currLang = getLang();
$dir = ($currLang === 'he') ? 'rtl' : 'ltr';

$notif_count = 0;
global $pdo;
if (isLoggedIn() && isset($pdo)) {
    $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotif->execute([$_SESSION['user_id']]);
    $notif_count = $stmtNotif->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="<?= $currLang ?>" dir="<?= $dir ?>" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Rently - Smart Rental Platform') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">Rently<span>.</span></a>
            
            <div class="nav-links">
                <a href="index.php"><?= __('Home') ?></a>
                
                <?php if(isLoggedIn()): ?>
                    <a href="notifications.php" style="position:relative; margin-left:5px; margin-right:20px; font-size:18px; text-decoration:none;">
                        🔔 <?php if($notif_count > 0): ?><span class="badge" style="position:absolute; top:-10px; right:-15px; background:var(--error-color); color:white; font-size:10px; padding:2px 6px;"><?= $notif_count ?></span><?php endif; ?>
                    </a>
                    <?php if(isAdmin()): ?>
                        <a href="admin.php" class="btn btn-primary" style="margin-left: 10px;"><?= __('Admin Panel') ?></a>
                    <?php else: ?>
                        <a href="support.php" title="<?= __('Support Tickets') ?>" style="margin-right:15px; font-size:18px; text-decoration:none;">🎟</a>
                        <a href="favorites.php" style="font-size:18px; margin-right:15px; text-decoration:none;">❤️</a>
                        <a href="profile.php"><?= __('Profile') ?></a>
                        <a href="add_listing.php" class="btn btn-primary"><?= __('Add Listing') ?></a>
                    <?php endif; ?>
                    <a href="logout.php"><?= __('Logout') ?></a>
                <?php else: ?>
                    <a href="login.php"><?= __('Login') ?></a>
                    <a href="register.php" class="btn btn-primary"><?= __('Register') ?></a>
                <?php endif; ?>

                <!-- Theme Toggle Button -->
                <button id="theme-toggle" class="icon-btn" title="Toggle Dark/Light Mode">🌙</button>
                
                <!-- Language Toggle Button -->
                <?php if($currLang === 'en'): ?>
                    <a href="?lang=he" class="icon-btn" style="text-decoration:none;" title="Switch to Hebrew">HE</a>
                <?php else: ?>
                    <a href="?lang=en" class="icon-btn" style="text-decoration:none;" title="Switch to English">EN</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main content container start -->
    <main class="container main-content <?= isset($isHomepage) && $isHomepage ? 'homepage' : '' ?>">
