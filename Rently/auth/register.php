<?php
// auth/register.php - Secure User Registration with Dev Verification
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) { redirect(BASE_URL . 'index.php'); }

$error = '';
$message = '';
$step = 1; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $name = cleanInput($_POST['name']);
        $email = cleanInput($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = __('Required field missing.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('Invalid email.');
        } elseif (strlen($password) < 6) {
            $error = __('Password must be at least 6 characters.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = __('Duplicate email. This email is already registered.');
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $code = generateVerificationCode();
                
                $insert = $pdo->prepare("INSERT INTO users (name, email, password, verification_code) VALUES (?, ?, ?, ?)");
                if ($insert->execute([$name, $email, $hashed_password, $code])) {
                    $step = 2; 
                    $_SESSION['verify_email'] = $email;
                    $message = "Development verification code: <strong>$code</strong>";
                } else {
                    $error = __('Something went wrong. Please try again.');
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        if (!isset($_SESSION['verify_email'])) { redirect(BASE_URL . 'auth/register.php'); }
        
        $entered_code = cleanInput($_POST['code']);
        $email = $_SESSION['verify_email'];
        
        $stmt = $pdo->prepare("SELECT id, role, verification_code FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['verification_code'] == $entered_code) {
            $pdo->prepare("UPDATE users SET verified = 1, verification_code = NULL WHERE email = ?")->execute([$email]);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_verified'] = 1;
            unset($_SESSION['verify_email']);
            
            redirect(BASE_URL . 'index.php');
        } else {
            $step = 2;
            $code = $user ? $user['verification_code'] : '';
            $message = "Development verification code: <strong>$code</strong>";
            $error = __('Invalid verification code!');
        }
    }
}

require_once '../includes/header.php';
?>

<div class="auth-box">
    <?php if($step === 1): ?>
        <h2 class="text-center"><?= __('Create an Account') ?></h2>
        <p class="text-center" style="margin-bottom:20px;"><?= __('Join Rently today!') ?></p>
        
        <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group">
                <label><?= __('Full Name') ?></label>
                <input type="text" name="name" class="form-control" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label><?= __('Email Address') ?></label>
                <input type="email" name="email" class="form-control" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label><?= __('Password') ?></label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <button type="submit" name="register" class="btn btn-primary" style="width:100%"><?= __('Sign Up') ?></button>
        </form>
        <p class="text-center" style="margin-top:15px;"><?= __('Already have an account?') ?> <a href="<?= BASE_URL ?>auth/login.php"><?= __('Login') ?></a></p>
    
    <?php elseif($step === 2): ?>
        <h2 class="text-center"><?= __('Verification') ?></h2>
        <p class="text-center" style="margin-bottom:20px;"><?= __('Enter the code we "sent" you.') ?></p>
        
        <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group">
                <label><?= __('Verification Code') ?></label>
                <input type="text" name="code" class="form-control" required placeholder="123456">
            </div>
            <button type="submit" name="verify" class="btn btn-primary" style="width:100%"><?= __('Verify & Login') ?></button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
