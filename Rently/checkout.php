<?php
// checkout.php - Payment Gateway (Simulated)
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || isAdmin()) { redirect('index.php'); }

$error = '';
$message = '';

// We expect listing_id, start_date, end_date in session or POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_booking'])) {
    $_SESSION['checkout'] = [
        'listing_id' => (int) $_POST['listing_id'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date']
    ];
}

if (!isset($_SESSION['checkout'])) {
    redirect('index.php');
}

$checkout = $_SESSION['checkout'];
$listing_id = $checkout['listing_id'];
$start_date = $checkout['start_date'];
$end_date = $checkout['end_date'];

// Fetch listing details
$stmt = $pdo->prepare("SELECT l.*, u.name as owner_name FROM listings l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    unset($_SESSION['checkout']);
    redirect('index.php');
}

// Calculate total
$days = max(1, (strtotime($end_date) - strtotime($start_date)) / 86400);
$total = $listing['price'] * $days;
$price_label = ($listing['price_type'] ?? 'day') === 'hour' ? __('hours') : __('days');

// Process Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    // Check availability one more time (prevent double booking!)
    if (!isDateAvailable($pdo, $listing_id, $start_date, $end_date)) {
        $error = __('These dates are no longer available. Someone booked them just now!');
    } else {
        // Validate card fields exist (simulated — no real processing)
        $card_name = cleanInput($_POST['card_name'] ?? '');
        $card_number = cleanInput($_POST['card_number'] ?? '');
        $card_exp = cleanInput($_POST['card_expiry'] ?? '');
        $card_cvv = cleanInput($_POST['card_cvv'] ?? '');
        
        if (empty($card_name) || empty($card_number) || empty($card_exp) || empty($card_cvv)) {
            $error = __('Please fill all payment fields.');
        } elseif (strlen(preg_replace('/\D/', '', $card_number)) < 16) {
            $error = __('Invalid card number.');
        } else {
            // Create booking with pending_admin status
            $insert = $pdo->prepare("INSERT INTO bookings (listing_id, user_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending_admin')");
            if ($insert->execute([$listing_id, $_SESSION['user_id'], $start_date, $end_date, $total])) {
                $notifMsg = 'A new booking for your listing "' . $listing['title'] . '" was submitted and is pending admin approval.';
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$listing['user_id'], $notifMsg, 'profile.php']);
                unset($_SESSION['checkout']);
                $message = 'success';
            } else {
                $error = __('Failed to create booking. Please try again.');
            }
        }
    }
}

require_once 'includes/header.php';
?>

<?php if($message === 'success'): ?>
<!-- Success Screen -->
<div class="text-center" style="padding: 80px 20px;">
    <div style="font-size: 80px; margin-bottom: 20px;">✅</div>
    <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?= __('Booking Submitted!') ?></h1>
    <p style="color:#718096; font-size:1.1rem; margin-bottom:30px;"><?= __('Your booking request has been sent to the owner for approval.') ?></p>
    <a href="profile.php" class="btn btn-primary"><?= __('View My Bookings') ?></a>
    <a href="index.php" class="btn btn-primary" style="margin-left:10px;"><?= __('Home') ?></a>
</div>
<?php else: ?>
<!-- Checkout Form -->
<div class="container" style="max-width: 900px; margin-bottom: 60px;">
    <h1 style="margin-bottom: 30px;"><?= __('Checkout') ?></h1>
    
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    
    <div class="listing-layout">
        <!-- Payment Form -->
        <div class="listing-main">
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light);">
                <h3 style="margin-bottom:20px;">💳 <?= __('Payment Details') ?></h3>
                <form method="POST" action="" id="paymentForm">
                    <input type="hidden" name="pay" value="1">
                    <div class="form-group">
                        <label><?= __('Cardholder Name') ?></label>
                        <input type="text" name="card_name" class="form-control" required placeholder="John Doe">
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
                            <input type="text" name="card_cvv" class="form-control" required placeholder="123" maxlength="4">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:16px; font-size: 18px; margin-top:10px;"><?= __('Pay') ?> ₪<?= number_format($total, 2) ?></button>
                </form>
                <p style="text-align:center; margin-top:15px; color:#718096; font-size:13px;">🔒 <?= __('Secure payment simulation for demo purposes.') ?></p>
            </div>
        </div>
        
        <!-- Order Summary Sidebar -->
        <div class="listing-sidebar">
            <div style="background:var(--card-bg); padding:25px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-hover);">
                <img src="<?= htmlspecialchars($listing['image']) ?>" style="width:100%; height:180px; object-fit:cover; border-radius:12px; margin-bottom:15px;" loading="lazy">
                <h4 style="margin-bottom:5px;"><?= htmlspecialchars($listing['title']) ?></h4>
                <p style="color:#718096; font-size:14px; margin-bottom:15px;">📍 <?= htmlspecialchars($listing['city']) ?> · <?= __('Hosted by') ?> <?= htmlspecialchars($listing['owner_name']) ?></p>
                
                <hr style="border:0; border-top:1px solid var(--border-color); margin:15px 0;">
                
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span>₪<?= htmlspecialchars($listing['price']) ?> × <?= $days ?> <?= $price_label ?></span>
                    <strong>₪<?= number_format($total, 2) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:8px; color:#718096; font-size:14px;">
                    <span>📅 <?= htmlspecialchars($start_date) ?></span>
                    <span>→ <?= htmlspecialchars($end_date) ?></span>
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
// Format card number as user types
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').substring(0,16);
    e.target.value = v.replace(/(.{4})/g, '$1 ').trim();
});
// Format expiry date
document.getElementById('cardExpiry').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').substring(0,4);
    if(v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2);
    e.target.value = v;
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
