<?php
/**
 * Helper / Utility Functions
 */

/** Redirect to a page within the app */
function redirect(string $page): void
{
    header("Location: index.php?page=" . urlencode($page));
    exit;
}

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
        $_SESSION['flash_error'] = 'Please log in to continue.';
        redirect('login');
    }
}

/** Require a specific role */
function requireRole(string $role): void
{
    requireLogin();
    if (getUserRole() !== $role) {
        $_SESSION['flash_error'] = 'Access denied.';
        redirect('home');
    }
}

/** Sanitize a string for safe HTML output */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
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
        unset($_SESSION[$key]);
        return "<div class='alert {$class} alert-dismissible fade show' role='alert'>
                    {$msg}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}
