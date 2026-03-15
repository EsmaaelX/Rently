<?php
/**
 * Header partial — shared across all pages.
 * Includes Bootstrap 5 CDN, Google Fonts, custom CSS, and responsive navbar.
 */
$currentPage = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rently — Rent apartments, cars, and sports venues from real people. P2P sharing economy platform.">
    <title>Rently — <?= sanitize($pageTitle ?? 'Rent Anything') ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= baseUrl() ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- ─── Navbar ─────────────────────────────────────────────── -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="main-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= baseUrl() ?>">
                <i class="bi bi-house-heart-fill me-2"></i>Rently
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>"
                           href="<?= baseUrl() ?>">
                            <i class="bi bi-grid-fill me-1"></i>Explore
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
                               href="<?= baseUrl() ?>index.php?page=dashboard">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'admin' ? 'active' : '' ?>"
                               href="<?= baseUrl() ?>index.php?page=admin">
                                <i class="bi bi-shield-lock me-1"></i>Admin
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                               data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?= sanitize($_SESSION['full_name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item-text text-muted small">
                                    <i class="bi bi-tag me-1"></i><?= ucfirst($_SESSION['role']) ?>
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= baseUrl() ?>index.php?page=dashboard">
                                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                </a></li>
                                <li><a class="dropdown-item text-danger" href="<?= baseUrl() ?>index.php?page=logout">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= baseUrl() ?>index.php?page=login">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-accent btn-sm ms-2 mt-1" href="<?= baseUrl() ?>index.php?page=register">
                                Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mt-3">
        <?= flashMessage('error') ?>
        <?= flashMessage('success') ?>
    </div>

    <main>
