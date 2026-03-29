<?php
/**
 * Header partial — shared across all pages.
 * Includes Bootstrap 5, Google Fonts, custom CSS, navbar with
 * dark mode toggle, language switcher, and notifications.
 */
$currentPage = $_GET['page'] ?? 'home';
$currentLang = getCurrentLang();
$currentTheme = getCurrentTheme();
$isRTL = isRTL();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $isRTL ? 'rtl' : 'ltr' ?>" data-bs-theme="<?= $currentTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rently — Rent apartments, cars, sport venues, equipment and more from real people. Smart rental platform.">
    <title>Rently — <?= sanitize($pageTitle ?? t('explore')) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <?php if ($isRTL): ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= baseUrl() ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- ─── Navbar ─────────────────────────────────────────────── -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="main-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= baseUrl() ?>">
                <i class="bi bi-house-heart-fill me-2"></i><?= t('app_name') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>"
                           href="<?= baseUrl() ?>">
                            <i class="bi bi-grid-fill me-1"></i><?= t('explore') ?>
                        </a>
                    </li>
                    <?php if (isLoggedIn() && !isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
                               href="<?= baseUrl() ?>index.php?page=dashboard">
                                <i class="bi bi-speedometer2 me-1"></i><?= t('dashboard') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'admin' ? 'active' : '' ?>"
                               href="<?= baseUrl() ?>index.php?page=admin">
                                <i class="bi bi-shield-lock me-1"></i><?= t('admin') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <!-- Dark Mode Toggle -->
                    <button class="theme-toggle" id="theme-toggle" title="<?= getCurrentTheme() === 'dark' ? t('light_mode') : t('dark_mode') ?>">
                        <i class="bi <?= getCurrentTheme() === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>"></i>
                    </button>

                    <!-- Language Switcher -->
                    <?php if ($currentLang === 'en'): ?>
                        <a href="<?= baseUrl() ?>index.php?page=<?= urlencode($currentPage) ?>&lang=he" class="lang-switch" title="עברית">🇮🇱 עב</a>
                    <?php else: ?>
                        <a href="<?= baseUrl() ?>index.php?page=<?= urlencode($currentPage) ?>&lang=en" class="lang-switch" title="English">🇺🇸 EN</a>
                    <?php endif; ?>

                    <ul class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                            <!-- Notifications -->
                            <li class="nav-item">
                                <a class="nav-link position-relative <?= $currentPage === 'notifications' ? 'active' : '' ?>"
                                   href="<?= baseUrl() ?>index.php?page=notifications" id="notif-link">
                                    <i class="bi bi-bell"></i>
                                    <span class="notification-badge d-none" id="notif-badge">0</span>
                                </a>
                            </li>
                            <!-- User Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                   data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle me-1"></i><?= sanitize($_SESSION['full_name'] ?? 'User') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><span class="dropdown-item-text text-muted small">
                                        <i class="bi bi-tag me-1"></i><?= ucfirst($_SESSION['role']) ?>
                                    </span></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= baseUrl() ?>index.php?page=profile">
                                        <i class="bi bi-person me-2"></i><?= t('profile') ?>
                                    </a></li>
                                    <?php if (!isAdmin()): ?>
                                    <li><a class="dropdown-item" href="<?= baseUrl() ?>index.php?page=dashboard">
                                        <i class="bi bi-speedometer2 me-2"></i><?= t('dashboard') ?>
                                    </a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= baseUrl() ?>index.php?page=logout">
                                        <i class="bi bi-box-arrow-right me-2"></i><?= t('logout') ?>
                                    </a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= baseUrl() ?>index.php?page=login">
                                    <i class="bi bi-box-arrow-in-right me-1"></i><?= t('login') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-accent btn-sm ms-2 mt-1" href="<?= baseUrl() ?>index.php?page=register">
                                    <?= t('sign_up') ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mt-3">
        <?= flashMessage('error') ?>
        <?= flashMessage('success') ?>
    </div>

    <main>
