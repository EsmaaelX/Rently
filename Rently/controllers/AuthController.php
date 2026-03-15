<?php
/**
 * AuthController
 * Handles user registration, login, and logout using PHP sessions.
 */
class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Register a new user.
     * Validates input, hashes password, inserts user into database.
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
            $_SESSION['flash_error'] = 'All fields are required.';
            redirect('register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Invalid email address.';
            redirect('register');
        }

        if (strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Password must be at least 6 characters.';
            redirect('register');
        }

        if ($password !== $confirm) {
            $_SESSION['flash_error'] = 'Passwords do not match.';
            redirect('register');
        }

        if (!in_array($role, ['renter', 'owner'])) {
            $role = 'renter';
        }

        // Check for duplicate email
        $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Email already registered.';
            redirect('register');
        }

        // Hash password and insert
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO Users (full_name, email, password_hash, phone_number, role)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$fullName, $email, $hash, $phone, $role]);

        $_SESSION['flash_success'] = 'Registration successful! Please log in.';
        redirect('login');
    }

    /**
     * Authenticate user via email & password.
     * Starts PHP session on success.
     */
    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Email and password are required.';
            redirect('login');
        }

        $stmt = $this->db->prepare(
            "SELECT user_id, full_name, email, password_hash, role, is_blocked
             FROM Users WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid email or password.';
            redirect('login');
        }

        if ($user['is_blocked']) {
            $_SESSION['flash_error'] = 'Your account has been blocked. Contact support.';
            redirect('login');
        }

        // Store session data
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];

        $_SESSION['flash_success'] = 'Welcome back, ' . $user['full_name'] . '!';
        redirect('home');
    }

    /**
     * Destroy session and log out.
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        // Start a fresh session for flash message
        session_start();
        $_SESSION['flash_success'] = 'You have been logged out.';
        redirect('login');
    }
}
