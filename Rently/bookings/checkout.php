<?php
// bookings/checkout.php - Payment Gateway Simulation
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || isAdmin()) { redirect(BASE_URL . 'index.php'); }

$error = '';
$message = '';

if (!isset($_SESSION['checkout'])) {
    redirect(BASE_URL . 'index.php');
}

$checkout = $_SESSION['checkout'];
$listing_id = $checkout['listing_id'];
$start_date = $checkout['start_date'];
$end_date = $checkout['end_date'];
$start_time = $checkout['start_time'] ?? null;
$end_time = $checkout['end_time'] ?? null;

// Fetch listing details
$stmt = $pdo->prepare("SELECT l.*, u.name as owner_name FROM listings l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    unset($_SESSION['checkout']);
    redirect(BASE_URL . 'index.php');
}

$total = calculateBookingPrice($listing['price'], $listing['price_type'], $start_date, $end_date, $start_time, $end_time);

if ($listing['price_type'] === 'hour') {
    $duration = max(1, (strtotime($end_time) - strtotime($start_time)) / 3600);
    $price_label = __('hours');
} else {
    $duration = max(1, ceil((strtotime($end_date) - strtotime($start_date)) / 86400));
    $price_label = __('days');
}

// Process Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        if (!checkAvailability($pdo, $listing_id, $start_date, $end_date, $start_time, $end_time)) {
            $error = __('These dates/times are no longer available. Someone booked them just now!');
        } else {
            $card_name = cleanInput($_POST['card_name'] ?? '');
            $card_number = cleanInput($_POST['card_number'] ?? '');
            $card_exp = cleanInput($_POST['card_expiry'] ?? '');
            $card_cvv = cleanInput($_POST['card_cvv'] ?? '');
            
            if (empty($card_name) || empty($card_number) || empty($card_exp) || empty($card_cvv)) {
                $error = __('Please fill all payment fields.');
            } elseif (strlen(preg_replace('/\D/', '', $card_number)) < 16) {
                $error = __('Invalid card number.');
            } elseif (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $card_exp, $expMatches)) {
                $error = __('Invalid expiry date. Please use the MM/YY format.');
            } elseif (mktime(0, 0, 0, (int)$expMatches[1] + 1, 1, 2000 + (int)$expMatches[2]) - 1 < time()) {
                $error = __('This card has expired. Please use a valid, unexpired card.');
            } else {
                $final_status = hasPendingOverlap($pdo, $listing_id, $start_date, $end_date, $start_time, $end_time) ? 'waitlist' : 'pending_admin';

                try {
                    $pdo->beginTransaction();

                    $insert = $pdo->prepare("INSERT INTO bookings (listing_id, user_id, start_date, start_time, end_date, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([$listing_id, $_SESSION['user_id'], $start_date, $start_time, $end_date, $end_time, $total, $final_status]);

                    if ($final_status === 'waitlist') {
                        $notifMsg = 'You joined the waitlist for "' . $listing['title'] . '". Your request will automatically promote if pending requests resolve.';
                        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$_SESSION['user_id'], $notifMsg, 'user/profile.php']);
                        $message = 'waitlist_success';
                    } else {
                        // Notify Owner
                        $notifMsg = 'A new booking for your listing "' . $listing['title'] . '" was submitted and awaits admin pre-approval.';
                        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$listing['user_id'], $notifMsg, 'user/profile.php']);

                        // Notify Admin
                        $adminMsg = 'New pending booking for listing "' . $listing['title'] . '" awaits your approval.';
                        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([1, $adminMsg, 'admin/admin.php?tab=bookings']);
                        notifyAdmins($pdo, "New Booking Requires Approval", "A new booking for '<strong>{$listing['title']}</strong>' has been submitted and is waiting for your approval.<br><br>Please check the admin dashboard.");

                        $message = 'success';
                    }

                    $pdo->commit();
                    unset($_SESSION['checkout']);
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    $error = __('Failed to process booking. Database error: ') . $ex->getMessage();
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<?php if($message === 'success' || $message === 'waitlist_success'): ?>
<!-- Success Screen -->
<div class="text-center" style="padding: 80px 20px;">
    <div style="font-size: 80px; margin-bottom: 20px;">✅</div>
    <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?= $message === 'waitlist_success' ? __('You are on the Waitlist!') : __('Booking Submitted!') ?></h1>
    <p style="color:#718096; font-size:1.1rem; margin-bottom:30px;">
        <?= $message === 'waitlist_success' 
            ? __('Because these dates/times are pending approval, you joined the waitlist. If they become available, your slot will promote automatically.')
            : __('Your booking has been submitted for admin pre-approval.') 
        ?>
    </p>
    <a href="<?= BASE_URL ?>user/profile.php" class="btn btn-primary"><?= __('View My Bookings') ?></a>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary" style="margin-left:10px;"><?= __('Home') ?></a>
</div>
<?php else: ?>
<!-- Checkout Form -->
<div class="container" style="max-width: 900px; margin-bottom: 60px; margin-top:20px;">
    <h1 style="margin-bottom: 30px;"><?= __('Checkout') ?></h1>
    
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    
    <div class="listing-layout">
        <div class="listing-main">
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light);">
                <h3 style="margin-bottom:20px;">💳 <?= __('Payment Details') ?></h3>
                <form method="POST" action="" id="paymentForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <div class="form-group">
                        <label><?= __('Cardholder Name') ?></label>
                        <input type="text" name="card_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?= __('Card Number') ?></label>
                        <input type="text" name="card_number" id="cardNumber" class="form-control" required placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label><?= __('Expiry Date') ?></label>
                            <input type="text" name="card_expiry" id="cardExpiry" class="form-control" required placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>CVV</label>
                            <input type="password" name="card_cvv" class="form-control" required placeholder="***" maxlength="4">
                        </div>
                    </div>
                    
                    <p style="color:#718096; font-size:12px; margin-bottom:15px;">⚠️ Development verification mode: Card values are simulated and card credentials will not be stored.</p>
                    
                    <button type="submit" name="pay" class="btn btn-primary" style="width:100%; padding:16px; font-size: 18px; margin-top:10px;"><?= __('Pay') ?> ₪<?= number_format($total, 2) ?></button>
                </form>
            </div>
        </div>
        
        <div class="listing-sidebar">
            <div style="background:var(--card-bg); padding:25px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-hover);">
                <img src="<?= htmlspecialchars(BASE_URL . $listing['image']) ?>" style="width:100%; height:180px; object-fit:cover; border-radius:12px; margin-bottom:15px;" loading="lazy">
                <h4 style="margin-bottom:5px;"><?= htmlspecialchars($listing['title']) ?></h4>
                <p style="color:#718096; font-size:14px; margin-bottom:15px;">📍 <?= htmlspecialchars($listing['city']) ?> · <?= __('Hosted by') ?> <?= htmlspecialchars($listing['owner_name']) ?></p>
                
                <hr style="border:0; border-top:1px solid var(--border-color); margin:15px 0;">
                
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span>₪<?= htmlspecialchars($listing['price']) ?> × <?= $duration ?> <?= $price_label ?></span>
                    <strong>₪<?= number_format($total, 2) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:8px; color:#718096; font-size:13px; flex-direction:column; gap:4px;">
                    <div>📅 <?= htmlspecialchars($start_date) ?> <?= $start_time ? ' ' . $start_time : '' ?></div>
                    <div>→ <?= htmlspecialchars($end_date) ?> <?= $end_time ? ' ' . $end_time : '' ?></div>
                </div>
                
                <hr style="border:0; border-top:1px solid var(--border-color); margin:15px 0;">
                
                <div style="display:flex; justify-content:space-between; font-size:1.3rem; font-weight:700;">
                    <span><?= __('Total') ?></span>
                    <span style="color:var(--primary-color);">₪<?= number_format($total, 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').substring(0,16);
    e.target.value = v.replace(/(.{4})/g, '$1 ').trim();
});
document.getElementById('cardExpiry').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').substring(0,4);
    if(v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2);
    e.target.value = v;
    e.target.setCustomValidity('');
});

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const expiryInput = document.getElementById('cardExpiry');
    const match = expiryInput.value.match(/^(0[1-9]|1[0-2])\/(\d{2})$/);

    if (!match) {
        expiryInput.setCustomValidity('<?= __('Invalid expiry date. Please use the MM/YY format.') ?>');
        expiryInput.reportValidity();
        e.preventDefault();
        return;
    }

    const expMonth = parseInt(match[1], 10);
    const expYear = 2000 + parseInt(match[2], 10);
    const expiresAt = new Date(expYear, expMonth, 1); // first day of the month AFTER expiry
    if (expiresAt <= new Date()) {
        expiryInput.setCustomValidity('<?= __('This card has expired. Please use a valid, unexpired card.') ?>');
        expiryInput.reportValidity();
        e.preventDefault();
    }
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
