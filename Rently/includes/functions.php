<?php
// includes/functions.php
// All reusable simple functions

session_start();

// Handle Language Switch Early!
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'he'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $redirect = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    header("Location: $redirect");
    exit();
}

// Get Current Language
function getLang() {
    return isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
}

// Translation Helper Function
function __($text) {
    global $lang;
    $currentLang = getLang();
    if (empty($lang)) {
        require_once __DIR__ . '/lang.php';
    }
    if (isset($lang[$currentLang][$text])) {
        return $lang[$currentLang][$text];
    }
    return $text;
}

// Redirect to another page safely
function redirect($url) {
    header("Location: $url");
    exit();
}

// Clean text inputs to prevent XSS
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Check if user is verified
function isVerified() {
    return isset($_SESSION['user_verified']) && $_SESSION['user_verified'] == 1;
}

// Generate a random 6 digit verification code
function generateVerificationCode() {
    return rand(100000, 999999);
}

// Check if a listing is favorited by the current user
function isFavorited($pdo, $user_id, $listing_id) {
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
    $stmt->execute([$user_id, $listing_id]);
    return $stmt->rowCount() > 0;
}

// Get average rating for a listing
function getAverageRating($pdo, $listing_id) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE listing_id = ?");
    $stmt->execute([$listing_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'avg' => round($result['avg_rating'] ?? 0, 1),
        'total' => $result['total']
    ];
}

// Check if dates overlap with existing approved bookings
function isDateAvailable($pdo, $listing_id, $start_date, $end_date, $exclude_booking_id = null) {
    $query = "SELECT id FROM bookings WHERE listing_id = ? AND status = 'approved' AND (start_date <= ? AND end_date >= ?)";
    $params = [$listing_id, $end_date, $start_date];
    if ($exclude_booking_id) {
        $query .= " AND id != ?";
        $params[] = $exclude_booking_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->rowCount() === 0; // true = available
}

// Check if there is a pending overlap, so we can assign waitlist status
function hasPendingOverlap($pdo, $listing_id, $start_date, $end_date) {
    $query = "SELECT id FROM bookings WHERE listing_id = ? AND status IN ('pending_admin', 'pending_owner') AND (start_date <= ? AND end_date >= ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$listing_id, $end_date, $start_date]);
    return $stmt->rowCount() > 0;
}

// Promote waitlist bookings if their dates are now available
function promoteWaitlistedBookings($pdo, $listing_id) {
    $stmt = $pdo->prepare("SELECT id, user_id, start_date, end_date FROM bookings WHERE listing_id = ? AND status = 'waitlist' ORDER BY created_at ASC");
    $stmt->execute([$listing_id]);
    $waitlists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($waitlists as $w) {
        if (!hasPendingOverlap($pdo, $listing_id, $w['start_date'], $w['end_date']) && isDateAvailable($pdo, $listing_id, $w['start_date'], $w['end_date'])) {
            $pdo->prepare("UPDATE bookings SET status = 'pending_admin' WHERE id = ?")->execute([$w['id']]);
            
            // Notify Renter
            $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$w['user_id'], "Great news! Your waitlisted booking is now pending admin approval.", 'profile.php']);
            
            // Notify Admin
            $listingStmt = $pdo->prepare("SELECT title FROM listings WHERE id = ?");
            $listingStmt->execute([$listing_id]);
            $title = $listingStmt->fetchColumn();
            $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([1, "A waitlisted booking for \"{$title}\" was automatically promoted.", 'admin.php?tab=bookings']);
        }
    }
}
?>
