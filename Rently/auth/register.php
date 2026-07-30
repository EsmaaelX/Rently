<?php
// auth/register.php - Secure User Registration
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
        $phone = cleanInput($_POST['phone']);
        $password = $_POST['password'];
        
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = __('Required field missing.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('Invalid email.');
        } elseif (strlen($password) < 6) {
            $error = __('Password must be at least 6 characters.');
        } elseif (!preg_match('/^\d{10}$/', $phone)) {
            $error = __('Invalid phone number. It must be exactly 10 digits.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = __('Duplicate email. This email is already registered.');
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $code = generateVerificationCode();
                
                $insert = $pdo->prepare("INSERT INTO users (name, email, phone, password, verification_code) VALUES (?, ?, ?, ?, ?)");
                if ($insert->execute([$name, $email, $phone, $hashed_password, $code])) {
                    $step = 2; 
                    $_SESSION['verify_email'] = $email;
                    
                    if (sendVerificationEmail($email, $code)) {
                        $message = __('A verification code has been sent to your email.');
                        if (str_ends_with($email, '.test')) {
                            $message .= '<br><strong style="color:red; font-size: 1.1em;">[DEV MODE] Verification Code: ' . $code . '</strong>';
                        }
                    } else {
                        $error = __('Failed to send verification email. Please contact support.');
                    }
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
                <input type="text" name="name" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('Email Address') ?></label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('Phone Number') ?></label>
                <input type="tel" name="phone" class="form-control" required pattern="\d{10}" title="Phone number must be exactly 10 digits">
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
        <p class="text-center" style="margin-bottom:20px;"><?= __('Enter the code we sent you.') ?></p>
        
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
