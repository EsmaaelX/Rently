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
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/WishlistController.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/ReportController.php';

// Services
require_once __DIR__ . '/services/StripeAPI.php';
require_once __DIR__ . '/services/GoogleMapsAPI.php';
require_once __DIR__ . '/services/EmailService.php';

// ── Language Switch ──
if (isset($_GET['lang'])) {
    setLang($_GET['lang']);
    // Redirect back to same page without lang param
    $redirect = $_GET['page'] ?? 'home';
    redirect($redirect);
}

// ── Routing ──
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

    case 'verify':
        $ctrl = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->verify();
        } else {
            require __DIR__ . '/views/auth/verify.php';
        }
        break;

    case 'resend-code':
        (new AuthController())->resendCode();
        break;

    case 'two-fa':
        $ctrl = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->verifyTwoFA();
        } else {
            require __DIR__ . '/views/auth/two_fa.php';
        }
        break;

    case 'toggle-2fa':
        (new AuthController())->toggleTwoFA();
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

    // ── Search / Filter (AJAX) ──────────────
    case 'search':
        $ctrl = new AssetController();
        $ctrl->search();
        break;

    // ── Autocomplete (AJAX) ─────────────────
    case 'autocomplete':
        (new AssetController())->autocomplete();
        break;

    // ── Recommendations (AJAX) ──────────────
    case 'recommendations':
        (new AssetController())->getRecommendations();
        break;

    // ── Bookings (AJAX) ─────────────────────
    case 'booking':
        $ctrl = new BookingController();
        if ($action === 'check') {
            $ctrl->checkAvailability();
        } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->createBooking();
        } elseif ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->cancelBooking();
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

    // ── Profile ─────────────────────────────
    case 'profile':
        $ctrl = new ProfileController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->update();
        } else {
            $ctrl->show();
        }
        break;

    // ── Wishlist (AJAX) ─────────────────────
    case 'wishlist':
        $ctrl = new WishlistController();
        if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->toggle();
        } elseif ($action === 'check') {
            $ctrl->check();
        } else {
            redirect('profile');
        }
        break;

    // ── Notifications ───────────────────────
    case 'notifications':
        $ctrl = new NotificationController();
        if ($action === 'unread-count') {
            $ctrl->unreadCount();
        } elseif ($action === 'mark-read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->markRead();
        } elseif ($action === 'mark-all-read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->markAllRead();
        } else {
            $ctrl->index();
        }
        break;

    // ── Reports (AJAX) ──────────────────────
    case 'report':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new ReportController())->submit();
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
        } elseif ($action === 'approve' && isset($_GET['id'])) {
            $ctrl->approveListing((int) $_GET['id']);
        } elseif ($action === 'reject' && isset($_GET['id'])) {
            $ctrl->rejectListing((int) $_GET['id']);
        } elseif ($action === 'resolve-report' && isset($_GET['id'])) {
            $ctrl->resolveReport((int) $_GET['id']);
        } else {
            $ctrl->index();
        }
        break;

    // ── Home ────────────────────────────────
    case 'home':
    default:
        $ctrl = new AssetController();
        $assets = $ctrl->getAll();
        require __DIR__ . '/views/home.php';
        break;
}
