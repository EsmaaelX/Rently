<?php
// register.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) { redirect('index.php'); }

$error = '';
$message = '';
$step = 1; // 1 = Registration form, 2 = Verification form

// Process Step 1: Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $error = "Email already exists!";
    } else {
        // Generate a 6-digit verification code
        $code = generateVerificationCode();
        
        $insert = $pdo->prepare("INSERT INTO users (name, email, password, verification_code) VALUES (?, ?, ?, ?)");
        if ($insert->execute([$name, $email, $password, $code])) {
            $step = 2; // Move to verification step
            $_SESSION['verify_email'] = $email;
            
            // SIMULATING EMAIL: We show it on the page directly for presentation purposes
            $message = "Code has been 'sent'! (Simulation: Your code is <strong>$code</strong>)";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

// Process Step 2: Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $entered_code = cleanInput($_POST['code']);
    $email = $_SESSION['verify_email'];
    
    $stmt = $pdo->prepare("SELECT id, role, verification_code FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['verification_code'] == $entered_code) {
        // Mark as verified
        $pdo->prepare("UPDATE users SET verified = 1, verification_code = NULL WHERE email = ?")->execute([$email]);
        
        // Log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_verified'] = 1;
        unset($_SESSION['verify_email']);
        
        redirect('index.php');
    } else {
        $step = 2;
        $code = $user ? $user['verification_code'] : '';
        $message = "Please enter the code shown previously. (Simulation: Your code is <strong>$code</strong>)";
        $error = "Invalid code!";
    }
}

require_once 'includes/header.php';
?>

<div class="auth-box">
    <?php if($step === 1): ?>
        <h2 class="text-center"><?= __('Create an Account') ?></h2>
        <p class="text-center" style="margin-bottom:20px;"><?= __('Join Rently today!') ?></p>
        
        <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><?= __('Full Name') ?></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('Email Address') ?></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('Password') ?></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary" style="width:100%"><?= __('Sign Up') ?></button>
        </form>
        <p class="text-center" style="margin-top:15px;"><?= __('Already have an account?') ?> <a href="login.php"><?= __('Login') ?></a></p>
    
    <?php elseif($step === 2): ?>
        <h2 class="text-center"><?= __('Verification') ?></h2>
        <p class="text-center" style="margin-bottom:20px;"><?= __('Enter the code we "sent" you.') ?></p>
        
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
