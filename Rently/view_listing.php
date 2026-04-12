 <?php
// view_listing.php - Full listing details with booking, reviews, map, and suggestions
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) { redirect('index.php'); }

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT l.*, u.name as owner_name, u.profile_picture as owner_pic FROM listings l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
$stmt->execute([$id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) { redirect('index.php'); }

$error = '';
$message = '';

// Handle Booking → Redirect to Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_booking'])) {
    if (!isLoggedIn()) { redirect('login.php'); }
    if (isAdmin()) { $error = __('Admins cannot book items.'); }
    else {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        if (strtotime($start_date) > strtotime($end_date)) {
            $error = __('End date must be after start date.');
        } elseif (!isDateAvailable($pdo, $id, $start_date, $end_date)) {
            $error = __('Sorry, these dates are already booked!');
        } else {
            // Store in session and redirect to checkout
            $_SESSION['checkout'] = [
                'listing_id' => $id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
            redirect('checkout.php');
        }
    }
}

// Handle Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) { redirect('login.php'); }
    if (isAdmin()) { $error = __('Admins cannot leave reviews.'); }
    else {
        $rating = (int) $_POST['rating'];
        $comment = cleanInput($_POST['comment']);
        $insert = $pdo->prepare("INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        if ($insert->execute([$id, $_SESSION['user_id'], $rating, $comment])) {
            $message = __('Review added successfully!');
        } else {
            $error = __('Failed to add review.');
        }
    }
}

// Fetch Reviews
$stmt = $pdo->prepare("SELECT r.*, u.name, u.profile_picture FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.listing_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Rating
$rating_info = getAverageRating($pdo, $id);

// Fetch Suggested Listings (same category, different listing)
$stmt = $pdo->prepare("SELECT * FROM listings WHERE category = ? AND id != ? AND status = 'approved' ORDER BY RAND() LIMIT 3");
$stmt->execute([$listing['category'], $id]);
$suggested = $stmt->fetchAll(PDO::FETCH_ASSOC);

$priceLabel = ($listing['price_type'] ?? 'day') === 'hour' ? __('/ hour') : __('/ day');

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px;">
    <?php if($message): ?><div class="alert alert-success" style="margin-top:20px;"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error" style="margin-top:20px;"><?= $error ?></div><?php endif; ?>

    <div class="listing-layout">
        <div class="listing-main">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?= htmlspecialchars($listing['title']) ?></h1>
            <p style="color: #718096; margin-bottom: 5px; font-size: 1.1rem;">
                📍 <?= htmlspecialchars($listing['city']) ?> &nbsp;|&nbsp; 
                👤 <?= __('Hosted by') ?> <?= htmlspecialchars($listing['owner_name']) ?>
            </p>
            <?php if($rating_info['total'] > 0): ?>
                <p class="stars" style="margin-bottom:20px;"><?= str_repeat('★', round($rating_info['avg'])) ?><span style="color:#e2e8f0;"><?= str_repeat('★', 5 - round($rating_info['avg'])) ?></span> <small style="color:#718096;"><?= $rating_info['avg'] ?>/5 (<?= $rating_info['total'] ?> <?= __('Reviews') ?>)</small></p>
            <?php endif; ?>
            
            <img src="<?= htmlspecialchars($listing['image']) ?>" alt="<?= htmlspecialchars($listing['title']) ?>" class="listing-hero-img" loading="lazy">
            
            <?php if(!empty($listing['attributes'])): $attrs = json_decode($listing['attributes'], true); if($attrs && is_array($attrs)): ?>
            <!-- Attributes -->
            <div style="background:var(--card-bg); padding:20px 30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom: 30px; display:flex; gap:20px; flex-wrap:wrap;">
                <?php foreach($attrs as $key => $val): if($val !== ''): ?>
                    <div style="background:var(--bg-color); padding:10px 15px; border-radius:10px; border:1px solid var(--border-color);">
                        <small style="color:var(--text-color); opacity:0.7; text-transform:capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></small>
                        <div style="font-weight:600; font-size:1.1rem; color:var(--text-color);"><?= htmlspecialchars($val) ?></div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
            <?php endif; endif; ?>
            
            <!-- Description -->
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom: 30px;">
                <h3 style="margin-bottom:15px;"><?= __('Description') ?></h3>
                <p style="line-height: 1.8; font-size: 1.1rem;"><?= nl2br(htmlspecialchars($listing['description'])) ?></p>
            </div>

            <!-- Map -->
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom: 30px;">
                <h3 style="margin-bottom:15px;">📍 <?= __('Location') ?></h3>
                <iframe 
                    width="100%" 
                    height="300" 
                    style="border:0; border-radius:12px;" 
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://maps.google.com/maps?q=<?= urlencode($listing['city']) ?>&output=embed">
                </iframe>
            </div>

            <!-- Reviews -->
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:30px;">
                <h3 style="margin-bottom:20px;"><?= __('Reviews') ?> (<?= count($reviews) ?>)</h3>
                
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $r): ?>
                        <div class="review-item">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                                <strong><?= htmlspecialchars($r['name']) ?></strong>
                                <div class="stars"><?= str_repeat('★', $r['rating']) ?><span style="color:#e2e8f0;"><?= str_repeat('★', 5 - $r['rating']) ?></span></div>
                            </div>
                            <p style="margin: 5px 0; font-style:italic;"><?= htmlspecialchars($r['comment']) ?></p>
                            <small style="color: #718096;"><?= date('M d, Y', strtotime($r['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#718096;"><?= __('No reviews yet.') ?></p>
                <?php endif; ?>

                <?php if(isLoggedIn() && !isAdmin()): ?>
                    <div style="margin-top: 30px; background: var(--bg-color); padding: 25px; border-radius: 12px; border:1px solid var(--border-color);">
                        <h4 style="margin-bottom:15px;"><?= __('Leave a Review') ?></h4>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label><?= __('Rating') ?></label>
                                <select name="rating" class="form-control" required style="width: 180px;">
                                    <option value="5">★★★★★ (5)</option>
                                    <option value="4">★★★★ (4)</option>
                                    <option value="3">★★★ (3)</option>
                                    <option value="2">★★ (2)</option>
                                    <option value="1">★ (1)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?= __('Your Comment') ?></label>
                                <textarea name="comment" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary"><?= __('Submit Review') ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar: Booking & Price -->
        <div class="listing-sidebar">
            <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-hover);">
                <div style="margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:15px;">
                    <h3 style="font-size: 2rem; color: var(--primary-color); margin:0;">₪<?= htmlspecialchars($listing['price']) ?> <span style="font-size:1rem; color:#718096;"><?= $priceLabel ?></span></h3>
                    <span class="badge" style="margin-top:10px;"><?= htmlspecialchars($listing['category']) ?></span>
                </div>
                
                <h4 style="margin-bottom:15px;"><?= __('Book this item') ?></h4>
                <?php if(isLoggedIn() && !isAdmin()): ?>
                    <form method="POST" action="" id="bookingForm">
                        <input type="hidden" name="listing_id" value="<?= $id ?>">
                        <div class="form-group">
                            <label><?= __('From Date') ?></label>
                            <input type="date" name="start_date" id="startDate" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label><?= __('To Date') ?></label>
                            <input type="date" name="end_date" id="endDate" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="pricePreview" style="display:none; background:var(--bg-color); padding:15px; border-radius:8px; margin-bottom:15px;">
                            <div style="display:flex; justify-content:space-between;"><span><?= __('Total') ?></span><strong id="totalPrice"></strong></div>
                        </div>
                        <button type="submit" name="start_booking" class="btn btn-primary" style="width:100%; padding:14px; font-size:16px;"><?= __('Proceed to Checkout') ?></button>
                    </form>
                <?php elseif(isAdmin()): ?>
                    <p style="color:var(--error-color); padding:15px; background:rgba(229, 62, 62, 0.1); border-radius:8px;"><?= __('Admins cannot book or review items.') ?></p>
                <?php else: ?>
                    <div style="padding:20px; text-align:center; background:var(--bg-color); border-radius:8px;">
                        <p style="margin-bottom:15px;"><?= __('Please login to book.') ?></p>
                        <a href="login.php" class="btn btn-primary" style="width:100%;"><?= __('Login') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Suggested Listings -->
    <?php if(count($suggested) > 0): ?>
    <div style="margin-top: 50px;">
        <h2 style="margin-bottom: 25px;"><?= __('You May Also Like') ?></h2>
        <div class="grid">
            <?php foreach($suggested as $s): 
                $sRating = getAverageRating($pdo, $s['id']);
                $sPriceLabel = ($s['price_type'] ?? 'day') === 'hour' ? __('/ hour') : __('/ day');
            ?>
                <a href="view_listing.php?id=<?= $s['id'] ?>" class="card animate-fade-in" style="text-decoration:none; color:inherit;">
                    <div class="card-img-wrapper" style="position:relative;">
                        <img src="<?= htmlspecialchars($s['image']) ?>" alt="<?= htmlspecialchars($s['title']) ?>" loading="lazy">
                        <span class="badge" style="position:absolute; top:15px; left:15px;"><?= htmlspecialchars($s['category']) ?></span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($s['title']) ?></h3>
                        <p style="color:#718096; font-size:14px;">📍 <?= htmlspecialchars($s['city']) ?></p>
                        <p class="card-price" style="margin-top:auto;">₪<?= htmlspecialchars($s['price']) ?> <span style="font-size:14px; color:#a0aec0; font-weight:normal;"><?= $sPriceLabel ?></span></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Dynamic price calculation
const price = <?= (float)$listing['price'] ?>;
const startDate = document.getElementById('startDate');
const endDate = document.getElementById('endDate');
const preview = document.getElementById('pricePreview');
const totalEl = document.getElementById('totalPrice');

function updatePrice() {
    if (startDate && endDate && startDate.value && endDate.value) {
        const days = Math.max(1, Math.ceil((new Date(endDate.value) - new Date(startDate.value)) / 86400000));
        const total = (price * days).toFixed(2);
        if (totalEl) totalEl.textContent = '₪' + total;
        if (preview) preview.style.display = 'block';
    }
}
if (startDate) startDate.addEventListener('change', updatePrice);
if (endDate) endDate.addEventListener('change', updatePrice);
</script>

<?php require_once 'includes/footer.php'; ?>
