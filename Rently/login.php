<?php
// login.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) { redirect('index.php'); }

$error = '';
$message = '';
$step = 1; 

// Step 1: Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id, password, is_blocked FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['is_blocked']) {
        $error = __('Your account has been blocked. Contact support.');
    } elseif ($user && password_verify($password, $user['password'])) {
        $code = generateVerificationCode();
        $pdo->prepare("UPDATE users SET verification_code = ? WHERE id = ?")->execute([$code, $user['id']]);
        
        $_SESSION['login_pending_id'] = $user['id'];
        $step = 2;
        $message = "Security check: Your login validation code is <strong>$code</strong>";
    } else {
        $error = __('Invalid email or password.');
    }
}

// Step 2: Verify Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    if(!isset($_SESSION['login_pending_id'])) { redirect('login.php'); }
    
    $entered_code = cleanInput($_POST['code']);
    $user_id = $_SESSION['login_pending_id'];
    
    $stmt = $pdo->prepare("SELECT id, role, verification_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['verification_code'] == $entered_code) {
        $pdo->prepare("UPDATE users SET verification_code = NULL WHERE id = ?")->execute([$user_id]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_verified'] = 1;
        unset($_SESSION['login_pending_id']);
        
        redirect('index.php');
    } else {
        $step = 2;
        $error = __('Invalid verification code!');
    }
}

require_once 'includes/header.php';
?>

<div class="auth-box">
    <?php if($step === 1): ?>
        <h2 class="text-center"><?= __('Welcome Back') ?></h2>
        <p class="text-center" style="margin-bottom:20px;"><?= __('Login to your Rently account.') ?></p>
        
        <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><?= __('Email Address') ?></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('Password') ?></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary" style="width:100%"><?= __('Login') ?></button>
        </form>
        <p class="text-center" style="margin-top:15px;">
            <a href="forgot_password.php"><?= __('Forgot Password?') ?></a>
        </p>
        <p class="text-center" style="margin-top:5px;"><?=__("Don't have an account?")?> <a href="register.php"><?= __('Sign Up') ?></a></p>
    
    <?php elseif($step === 2): ?>
        <h2 class="text-center"><?= __('2-Step Verification') ?></h2>
        <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><?= __('Verification Code') ?></label>
                <input type="text" name="code" class="form-control" required>
            </div>
            <button type="submit" name="verify" class="btn btn-primary" style="width:100%"><?= __('Verify & Login') ?></button>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
