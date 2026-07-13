<?php
// includes/functions.php
// All reusable simple functions and security helpers

session_start();

// Dynamically compute BASE_URL for routing subfolder files correctly
if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $dir = str_replace('\\', '/', __DIR__);
    $projectDir = str_replace($docRoot, '', dirname($dir));
    $base = '/' . trim($projectDir, '/') . '/';
    if ($base === '//') $base = '/';
    define('BASE_URL', $base);
}

// Kick out blocked users immediately (except on login.php, logout.php, or if not logged in or running from CLI)
$current_page = basename($_SERVER['PHP_SELF']);
if (isset($_SESSION['user_id']) && $current_page !== 'login.php' && $current_page !== 'logout.php' && php_sapi_name() !== 'cli') {
    global $pdo;
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT is_blocked FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $is_blocked = $stmt->fetchColumn();
        if ($is_blocked) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            header("Location: login.php?blocked=1");
            exit();
        }
    }
}

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

// Generate a basic CSRF token if it doesn't exist
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify if the CSRF token matches
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Check if viewer can view target user's contact details
function canViewContactDetails($viewerId, $targetUserId, $listingId = null) {
    if (!$viewerId) return false;
    
    // Admins see everything
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    // A user sees their own info
    if ($viewerId == $targetUserId) {
        return true;
    }
    
    global $pdo;
    if (!isset($pdo)) {
        return false;
    }
    
    // Check for approved booking between viewer and target
    $sql = "SELECT COUNT(*) FROM bookings b 
            JOIN listings l ON b.listing_id = l.id 
            WHERE b.status = 'approved' 
              AND (
                (b.user_id = ? AND l.user_id = ?) 
                OR 
                (b.user_id = ? AND l.user_id = ?)
              )";
    $params = [$viewerId, $targetUserId, $targetUserId, $viewerId];
    
    if ($listingId) {
        $sql .= " AND b.listing_id = ?";
        $params[] = (int)$listingId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

// Validate and upload a single image securely
function uploadImage($fileField, $subdir = 'uploads/') {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        return ['error' => __('Please upload an image.')];
    }
    
    $file = $_FILES[$fileField];
    
    // Validate size (max 2MB)
    if ($file['size'] > 2000000) {
        return ['error' => __('Image is too large. Max 2MB.')];
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    
    if (!array_key_exists($mime, $allowedTypes)) {
        return ['error' => __('Invalid image format. Only JPG, PNG, and WebP are allowed.')];
    }
    
    if (!is_dir($subdir)) {
        mkdir($subdir, 0777, true);
    }
    
    $ext = $allowedTypes[$mime];
    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $subdir . $randomName;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['path' => $destination];
    } else {
        return ['error' => __('Failed to move uploaded file.')];
    }
}

// Validate and upload multiple images securely
function uploadMultipleImages($fileField, $subdir = 'uploads/') {
    if (!isset($_FILES[$fileField]) || !is_array($_FILES[$fileField]['name'])) {
        return [];
    }
    
    $uploadedPaths = [];
    $errors = [];
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    
    $filesCount = count($_FILES[$fileField]['name']);
    for ($i = 0; $i < $filesCount; $i++) {
        if ($_FILES[$fileField]['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $size = $_FILES[$fileField]['size'][$i];
        $tmpName = $_FILES[$fileField]['tmp_name'][$i];
        
        if ($size > 2000000) {
            $errors[] = "File " . $_FILES[$fileField]['name'][$i] . " is too large. Max 2MB.";
            continue;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        
        if (!array_key_exists($mime, $allowedTypes)) {
            $errors[] = "File " . $_FILES[$fileField]['name'][$i] . " has invalid format.";
            continue;
        }
        
        if (!is_dir($subdir)) {
            mkdir($subdir, 0777, true);
        }
        
        $ext = $allowedTypes[$mime];
        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $subdir . $randomName;
        
        if (move_uploaded_file($tmpName, $destination)) {
            $uploadedPaths[] = $destination;
        } else {
            $errors[] = "Failed to upload file " . $_FILES[$fileField]['name'][$i];
        }
    }
    
    return ['paths' => $uploadedPaths, 'errors' => $errors];
}

// Calculate Booking Price (daily or hourly)
function calculateBookingPrice($price, $price_type, $start_date, $end_date, $start_time = null, $end_time = null) {
    if ($price_type === 'hour') {
        if (!$start_time || !$end_time) return $price;
        $hours = (strtotime($end_time) - strtotime($start_time)) / 3600;
        return max(1, $hours) * $price;
    } else {
        $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
        return max(1, ceil($days)) * $price;
    }
}

// Check availability (prevent double booking)
function checkAvailability($pdo, $listing_id, $start_date, $end_date, $start_time = null, $end_time = null, $exclude_booking_id = null) {
    $stmt = $pdo->prepare("SELECT price_type FROM listings WHERE id = ?");
    $stmt->execute([$listing_id]);
    $price_type = $stmt->fetchColumn();
    
    if ($price_type === 'hour') {
        // Hourly bookings on the same date and overlapping hours
        $query = "SELECT id FROM bookings 
                  WHERE listing_id = ? 
                    AND status = 'approved' 
                    AND start_date = ? 
                    AND (start_time < ? AND end_time > ?)";
        $params = [$listing_id, $start_date, $end_time, $start_time];
        if ($exclude_booking_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_booking_id;
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount() === 0;
    } else {
        // Daily bookings overlapping dates
        $query = "SELECT id FROM bookings 
                  WHERE listing_id = ? 
                    AND status = 'approved' 
                    AND (start_date <= ? AND end_date >= ?)";
        $params = [$listing_id, $end_date, $start_date];
        if ($exclude_booking_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_booking_id;
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount() === 0;
    }
}

// Check if there is a pending overlap, so we can assign waitlist status
function hasPendingOverlap($pdo, $listing_id, $start_date, $end_date, $start_time = null, $end_time = null) {
    $stmt = $pdo->prepare("SELECT price_type FROM listings WHERE id = ?");
    $stmt->execute([$listing_id]);
    $price_type = $stmt->fetchColumn();
    
    if ($price_type === 'hour') {
        $query = "SELECT id FROM bookings 
                  WHERE listing_id = ? 
                    AND status IN ('pending_admin', 'pending_owner') 
                    AND start_date = ? 
                    AND (start_time < ? AND end_time > ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$listing_id, $start_date, $end_time, $start_time]);
        return $stmt->rowCount() > 0;
    } else {
        $query = "SELECT id FROM bookings 
                  WHERE listing_id = ? 
                    AND status IN ('pending_admin', 'pending_owner') 
                    AND (start_date <= ? AND end_date >= ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$listing_id, $end_date, $start_date]);
        return $stmt->rowCount() > 0;
    }
}

// Wrapper for backward compatibility
function isDateAvailable($pdo, $listing_id, $start_date, $end_date, $exclude_booking_id = null) {
    return checkAvailability($pdo, $listing_id, $start_date, $end_date, null, null, $exclude_booking_id);
}

// Check if user is allowed to cancel booking
function canCancelBooking($booking) {
    if (in_array($booking['status'], ['pending_admin', 'pending_owner', 'waitlist'])) {
        return true;
    }
    if ($booking['status'] === 'approved') {
        $start_ts = strtotime($booking['start_date'] . ' ' . ($booking['start_time'] ?? '00:00:00'));
        return ($start_ts - time()) >= 86400; // 24 hours
    }
    return false;
}

// Promote waitlist bookings if their dates are now available
function promoteWaitlistedBookings($pdo, $listing_id) {
    $stmt = $pdo->prepare("SELECT id, user_id, start_date, start_time, end_date, end_time FROM bookings WHERE listing_id = ? AND status = 'waitlist' ORDER BY created_at ASC");
    $stmt->execute([$listing_id]);
    $waitlists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($waitlists as $w) {
        if (!hasPendingOverlap($pdo, $listing_id, $w['start_date'], $w['end_date'], $w['start_time'], $w['end_time']) && 
            checkAvailability($pdo, $listing_id, $w['start_date'], $w['end_date'], $w['start_time'], $w['end_time'])) {
            
            // Begin transaction to promote booking
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE bookings SET status = 'pending_admin' WHERE id = ?")->execute([$w['id']]);
                
                // Notify Renter
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$w['user_id'], "Great news! Your waitlisted booking is now pending admin approval.", 'profile.php']);
                
                // Notify Admin
                $listingStmt = $pdo->prepare("SELECT title FROM listings WHERE id = ?");
                $listingStmt->execute([$listing_id]);
                $title = $listingStmt->fetchColumn();
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([1, "A waitlisted booking for \"{$title}\" was automatically promoted.", 'admin.php?tab=bookings']);
                
                $pdo->commit();
            } catch (Exception $ex) {
                $pdo->rollBack();
                // Log error
                error_log("Promotion failed for booking ID {$w['id']}: " . $ex->getMessage());
            }
        }
    }
}
?>
