<?php
/**
 * Helper / Utility Functions
 */

// ─── Language System ──────────────────────────────────────

/** Load language strings - cached per request */
function getLangStrings(): array
{
    static $strings = null;
    if ($strings !== null) return $strings;

    $lang = getCurrentLang();
    $file = __DIR__ . "/lang/{$lang}.php";
    if (!file_exists($file)) {
        $file = __DIR__ . '/lang/en.php';
    }
    $strings = require $file;
    return $strings;
}

/** Translate a key to current language */
function t(string $key): string
{
    $strings = getLangStrings();
    return $strings[$key] ?? $key;
}

/** Get current language from cookie or session */
function getCurrentLang(): string
{
    if (isset($_COOKIE['rently_lang'])) {
        return in_array($_COOKIE['rently_lang'], ['en', 'he']) ? $_COOKIE['rently_lang'] : 'en';
    }
    return $_SESSION['lang'] ?? 'en';
}

/** Set language preference */
function setLang(string $lang): void
{
    $lang = in_array($lang, ['en', 'he']) ? $lang : 'en';
    $_SESSION['lang'] = $lang;
    setcookie('rently_lang', $lang, time() + (365 * 24 * 60 * 60), '/');

    // Update user preference if logged in
    if (isLoggedIn()) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET preferred_lang = ? WHERE user_id = ?");
        $stmt->execute([$lang, $_SESSION['user_id']]);
    }
}

/** Check if current language is RTL */
function isRTL(): bool
{
    return getCurrentLang() === 'he';
}

// ─── Theme ────────────────────────────────────────────────

/** Get current theme from cookie */
function getCurrentTheme(): string
{
    return $_COOKIE['rently_theme'] ?? 'light';
}

// ─── Routing ──────────────────────────────────────────────

/** Redirect to a page within the app */
function redirect(string $page): void
{
    header("Location: index.php?page=" . urlencode($page));
    exit;
}

// ─── Auth Helpers ─────────────────────────────────────────

/** Check if user is logged in */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/** Get current user's role */
function getUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/** Check if current user is an owner */
function isOwner(): bool
{
    return isLoggedIn() && getUserRole() === 'owner';
}

/** Check if current user is an admin */
function isAdmin(): bool
{
    return isLoggedIn() && getUserRole() === 'admin';
}

/** Require login — redirect to login page if not authenticated */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = t('login_required');
        redirect('login');
    }
}

/** Require a specific role */
function requireRole(string $role): void
{
    requireLogin();
    if (getUserRole() !== $role) {
        $_SESSION['flash_error'] = t('access_denied');
        redirect('home');
    }
}

// ─── Output Helpers ───────────────────────────────────────

/** Sanitize a string for safe HTML output */
function sanitize(?string $value): string
{
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Send a JSON response and exit */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/** Get base URL for the project */
function baseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . '/Rently/';
}

/** Display flash message if present and clear it */
function flashMessage(string $type = 'error'): string
{
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg   = sanitize($_SESSION[$key]);
        $class = ($type === 'success') ? 'alert-success' : 'alert-danger';
        $icon  = ($type === 'success') ? 'bi-check-circle' : 'bi-exclamation-circle';
        unset($_SESSION[$key]);
        return "<div class='alert {$class} alert-dismissible fade show d-flex align-items-center' role='alert'>
                    <i class='bi {$icon} me-2'></i>
                    {$msg}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}

/** Format category name for display */
function formatCategory(string $category): string
{
    $icons = [
        'apartment'   => '🏠', 'car'         => '🚗', 'sport_venue' => '⚽',
        'equipment'   => '🔧', 'studio'      => '🎨', 'parking'     => '🅿️'
    ];
    $icon = $icons[$category] ?? '📦';
    return $icon . ' ' . t($category);
}

/** Get category icon class */
function categoryIcon(string $category): string
{
    $icons = [
        'apartment'   => 'bi-building',
        'car'         => 'bi-car-front',
        'sport_venue' => 'bi-trophy',
        'equipment'   => 'bi-tools',
        'studio'      => 'bi-easel2',
        'parking'     => 'bi-p-circle'
    ];
    return $icons[$category] ?? 'bi-box';
}

/** Check if category uses hourly pricing */
function isHourlyCategory(string $category): bool
{
    return in_array($category, ['sport_venue', 'studio', 'parking']);
}

/** Get price display for an asset */
function getAssetPrice(array $asset): string
{
    if (isHourlyCategory($asset['category']) && (float) $asset['price_per_hour'] > 0) {
        return '$' . number_format($asset['price_per_hour'], 2) . t('per_hour');
    }
    return '$' . number_format($asset['price_per_day'], 2) . t('per_day');
}

/** Time ago helper */
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
