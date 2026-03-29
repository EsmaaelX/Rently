<?php
/**
 * AuthController
 * Handles registration (with email verification), login (with optional 2FA),
 * logout, and verification flows.
 */
class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── REGISTRATION ─────────────────────────────────────────

    /**
     * Register a new user.
     * Validates input, hashes password, generates verification code, sends email.
     */
    public function register(): void
    {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $phone    = trim($_POST['phone_number'] ?? '');
        $role     = $_POST['role'] ?? 'renter';

        // Validation
        if (empty($fullName) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = t('all_fields_required');
            redirect('register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = t('invalid_email');
            redirect('register');
        }

        if (strlen($password) < 6) {
            $_SESSION['flash_error'] = t('password_min_length');
            redirect('register');
        }

        if ($password !== $confirm) {
            $_SESSION['flash_error'] = t('passwords_no_match');
            redirect('register');
        }

        if (!in_array($role, ['renter', 'owner'])) {
            $role = 'renter';
        }

        // Check for duplicate email
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = t('email_already_registered');
            redirect('register');
        }

        // Generate verification code
        $verificationCode = $this->generateCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Hash password and insert
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, email, password_hash, dev_password, phone_number, role,
                               verification_code, verification_expires)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$fullName, $email, $hash, $password, $phone, $role, $verificationCode, $expiresAt]);

        // Send verification email
        $emailService = new EmailService();
        $sent = $emailService->sendVerificationCode($email, $verificationCode, $fullName);

        // Store email in session for verify page
        $_SESSION['pending_verification_email'] = $email;

        // Show code in flash for development (WAMP mail() typically fails)
        $_SESSION['flash_success'] = t('verification_sent') . ' ' . (!$sent ? '(Dev code: ' . $verificationCode . ')' : '');
        redirect('verify');
    }

    // ─── EMAIL VERIFICATION ───────────────────────────────────

    /**
     * Verify email with the 6-digit code.
     */
    public function verify(): void
    {
        $email = $_SESSION['pending_verification_email'] ?? '';
        $code  = trim($_POST['verification_code'] ?? '');

        if (empty($email) || empty($code)) {
            $_SESSION['flash_error'] = t('invalid_verification');
            redirect('verify');
        }

        $stmt = $this->db->prepare(
            "SELECT user_id, full_name, verification_code, verification_expires
             FROM users WHERE email = ? AND is_verified = 0"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = t('account_not_found');
            redirect('verify');
        }

        // Check code validity
        if ($user['verification_code'] !== $code) {
            $_SESSION['flash_error'] = t('invalid_code');
            redirect('verify');
        }

        // Check expiration
        if (strtotime($user['verification_expires']) < time()) {
            $_SESSION['flash_error'] = t('code_expired');
            redirect('verify');
        }

        // Mark as verified
        $stmt = $this->db->prepare(
            "UPDATE users SET is_verified = 1, verification_code = NULL, verification_expires = NULL
             WHERE user_id = ?"
        );
        $stmt->execute([$user['user_id']]);

        unset($_SESSION['pending_verification_email']);
        $_SESSION['flash_success'] = t('email_verified');
        redirect('login');
    }

    /**
     * Resend verification code.
     */
    public function resendCode(): void
    {
        $email = $_SESSION['pending_verification_email'] ?? '';
        if (empty($email)) {
            redirect('register');
        }

        $stmt = $this->db->prepare(
            "SELECT user_id, full_name FROM users WHERE email = ? AND is_verified = 0"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = t('account_not_found');
            redirect('register');
        }

        $newCode = $this->generateCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $this->db->prepare(
            "UPDATE users SET verification_code = ?, verification_expires = ? WHERE user_id = ?"
        );
        $stmt->execute([$newCode, $expiresAt, $user['user_id']]);

        $emailService = new EmailService();
        $sent = $emailService->sendVerificationCode($email, $newCode, $user['full_name']);

        $_SESSION['flash_success'] = t('new_code_sent') . ' ' . (!$sent ? '(Dev code: ' . $newCode . ')' : '');
        redirect('verify');
    }

    // ─── LOGIN ────────────────────────────────────────────────

    /**
     * Authenticate user via email & password.
     * If 2FA is enabled, redirect to 2FA page instead of logging in.
     */
    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = t('email_password_required');
            redirect('login');
        }

        $stmt = $this->db->prepare(
            "SELECT user_id, full_name, email, password_hash, role, is_blocked,
                    is_verified, two_fa_enabled
             FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = t('invalid_credentials');
            redirect('login');
        }

        if ($user['is_blocked']) {
            $_SESSION['flash_error'] = t('account_blocked');
            redirect('login');
        }

        if (!$user['is_verified']) {
            $_SESSION['pending_verification_email'] = $email;
            $_SESSION['flash_error'] = t('account_not_verified');
            redirect('verify');
        }

        // 2FA check
        if ($user['two_fa_enabled']) {
            $code = $this->generateCode();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $this->db->prepare(
                "UPDATE users SET two_fa_code = ?, two_fa_expires = ? WHERE user_id = ?"
            );
            $stmt->execute([$code, $expiresAt, $user['user_id']]);

            $emailService = new EmailService();
            $sent = $emailService->sendTwoFACode($email, $code, $user['full_name']);

            $_SESSION['two_fa_user_id'] = $user['user_id'];
            $_SESSION['flash_success'] = t('twofa_code_sent') . ' ' . (!$sent ? '(Dev code: ' . $code . ')' : '');
            redirect('two-fa');
        }

        // No 2FA — log in directly
        $this->setSession($user);
        $_SESSION['flash_success'] = t('welcome_back') . ' ' . $user['full_name'] . '!';
        redirect('home');
    }

    // ─── 2FA VERIFICATION ─────────────────────────────────────

    /**
     * Verify 2FA code during login.
     */
    public function verifyTwoFA(): void
    {
        $userId = $_SESSION['two_fa_user_id'] ?? 0;
        $code   = trim($_POST['two_fa_code'] ?? '');

        if (!$userId || empty($code)) {
            $_SESSION['flash_error'] = t('invalid_verification');
            redirect('login');
        }

        $stmt = $this->db->prepare(
            "SELECT user_id, full_name, email, role, two_fa_code, two_fa_expires
             FROM users WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || $user['two_fa_code'] !== $code) {
            $_SESSION['flash_error'] = t('invalid_code');
            redirect('two-fa');
        }

        if (strtotime($user['two_fa_expires']) < time()) {
            $_SESSION['flash_error'] = t('code_expired');
            unset($_SESSION['two_fa_user_id']);
            redirect('login');
        }

        // Clear 2FA code
        $stmt = $this->db->prepare(
            "UPDATE users SET two_fa_code = NULL, two_fa_expires = NULL WHERE user_id = ?"
        );
        $stmt->execute([$userId]);

        unset($_SESSION['two_fa_user_id']);
        $this->setSession($user);
        $_SESSION['flash_success'] = t('welcome_back') . ' ' . $user['full_name'] . '!';
        redirect('home');
    }

    // ─── LOGOUT ───────────────────────────────────────────────

    /**
     * Destroy session and log out.
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_success'] = t('logged_out');
        redirect('login');
    }

    // ─── TOGGLE 2FA ───────────────────────────────────────────

    /**
     * Enable or disable 2FA for the current user.
     */
    public function toggleTwoFA(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => 'Not authenticated.'], 401);
        }

        $stmt = $this->db->prepare("SELECT two_fa_enabled FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        $newStatus = $user['two_fa_enabled'] ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE users SET two_fa_enabled = ? WHERE user_id = ?");
        $stmt->execute([$newStatus, $_SESSION['user_id']]);

        $msg = $newStatus ? t('twofa_enabled') : t('twofa_disabled');
        jsonResponse(['success' => true, 'enabled' => (bool) $newStatus, 'message' => $msg]);
    }

    // ─── HELPERS ──────────────────────────────────────────────

    /**
     * Generate a random 6-digit code.
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Set session variables after successful authentication.
     */
    private function setSession(array $user): void
    {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];
    }
}
