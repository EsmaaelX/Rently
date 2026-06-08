<?php
// forgot_password.php - Password Reset with Dev Code
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) { redirect('index.php'); }

$error = '';
$message = '';
$step = 1; // 1=Enter Email, 2=Enter Code+New Password

// Step 1: Request Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = cleanInput($_POST['email']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $code = generateVerificationCode();
        $pdo->prepare("UPDATE users SET verification_code = ? WHERE id = ?")->execute([$code, $user['id']]);
        $_SESSION['reset_user_id'] = $user['id'];
        $step = 2;
        $message = "Reset code (Dev Mode): <strong>$code</strong>";
    } else {
        $error = __('No account found with that email.');
    }
}

// Step 2: Verify Code and Set New Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_user_id'])) { redirect('forgot_password.php'); }
    
    $entered_code = cleanInput($_POST['code']);
    $new_password = $_POST['new_password'];
    $user_id = $_SESSION['reset_user_id'];
    
    $stmt = $pdo->prepare("SELECT verification_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['verification_code'] == $entered_code) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ?, verification_code = NULL WHERE id = ?")->execute([$hashed, $user_id]);
        unset($_SESSION['reset_user_id']);
        $message = __('Password reset successfully!');
        $step = 3; // show success
    } else {
        $step = 2;
        $error = __('Invalid reset code.');
    }
}

require_once 'includes/header.php';
?>

<div class="auth-box">
    <?php if($step === 1): ?>
        <h2 class="text-center"><?= __('Forgot Password') ?></h2>
        <p class="text-center" style="margin-bottom:20px;"><?= __('Enter your email to receive a reset code.') ?></p>
        
        <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><?= __('Email Address') ?></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" name="request_reset" class="btn btn-primary" style="width:100%"><?= __('Send Reset Code') ?></button>
        </form>
        <p class="text-center" style="margin-top:15px;"><a href="login.php"><?= __('Back to Login') ?></a></p>
    
    <?php elseif($step === 2): ?>
        <h2 class="text-center"><?= __('Reset Password') ?></h2>
        <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><?= __('Verification Code') ?></label>
                <input type="text" name="code" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('New Password') ?></label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" name="reset_password" class="btn btn-primary" style="width:100%"><?= __('Reset Password') ?></button>
        </form>
    
    <?php elseif($step === 3): ?>
        <div class="text-center">
            <div style="font-size:60px; margin-bottom:15px;">✅</div>
            <h2><?= __('Password Updated!') ?></h2>
            <p style="margin:15px 0;"><?= $message ?></p>
            <a href="login.php" class="btn btn-primary"><?= __('Login') ?></a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
