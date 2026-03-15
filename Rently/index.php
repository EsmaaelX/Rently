<?php
/**
 * Rently — Front Controller
 * Routes all requests via ?page= parameter.
 */
session_start();

// Core includes
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/helpers.php';

// Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AssetController.php';
require_once __DIR__ . '/controllers/BookingController.php';
require_once __DIR__ . '/controllers/ReviewController.php';
require_once __DIR__ . '/controllers/PaymentController.php';
require_once __DIR__ . '/controllers/AdminController.php';

// Services
require_once __DIR__ . '/services/StripeAPI.php';
require_once __DIR__ . '/services/GoogleMapsAPI.php';

// ---- Routing ----
$page   = $_GET['page']   ?? 'home';
$action = $_GET['action'] ?? null;

switch ($page) {

    // ── Authentication ──────────────────────
    case 'login':
        $ctrl = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->login();
        } else {
            require __DIR__ . '/views/auth/login.php';
        }
        break;

    case 'register':
        $ctrl = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->register();
        } else {
            require __DIR__ . '/views/auth/register.php';
        }
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    // ── Assets ──────────────────────────────
    case 'asset':
        $ctrl = new AssetController();
        if ($action === 'detail' && isset($_GET['id'])) {
            $ctrl->show((int) $_GET['id']);
        } else {
            redirect('home');
        }
        break;

    // ── Bookings (AJAX) ─────────────────────
    case 'booking':
        $ctrl = new BookingController();
        if ($action === 'check') {
            $ctrl->checkAvailability();
        } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->createBooking();
        } else {
            redirect('home');
        }
        break;

    // ── Reviews (AJAX) ──────────────────────
    case 'review':
        $ctrl = new ReviewController();
        if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->addReview();
        }
        break;

    // ── Owner Dashboard ─────────────────────
    case 'dashboard':
        requireLogin();
        $ctrl = new AssetController();
        if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->create();
        } elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->update();
        } elseif ($action === 'delete' && isset($_GET['id'])) {
            $ctrl->delete((int) $_GET['id']);
        } else {
            $ctrl->dashboard();
        }
        break;

    // ── Admin Panel ─────────────────────────
    case 'admin':
        requireRole('admin');
        $ctrl = new AdminController();
        if ($action === 'block' && isset($_GET['id'])) {
            $ctrl->toggleBlock((int) $_GET['id']);
        } else {
            $ctrl->index();
        }
        break;

    // ── Search / Filter (AJAX) ──────────────
    case 'search':
        $ctrl = new AssetController();
        $ctrl->search();
        break;

    // ── Home ────────────────────────────────
    case 'home':
    default:
        $ctrl = new AssetController();
        $assets = $ctrl->getAll();
        require __DIR__ . '/views/home.php';
        break;
}
