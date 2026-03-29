<?php
/**
 * AssetController
 * CRUD for assets + smart search/filter + autocomplete + recommendations + owner dashboard.
 */
class AssetController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── READ ─────────────────────────────────────────────────

    /** Get all approved, active assets */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, u.full_name AS owner_name,
                    COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.asset_id = a.asset_id), 0) AS avg_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.asset_id = a.asset_id) AS review_count
             FROM assets a
             JOIN users u ON a.owner_id = u.user_id
             WHERE a.status = 'active' AND a.is_approved = 1
             ORDER BY a.created_at DESC"
        );
        $assets = $stmt->fetchAll();
        return $this->attachWishlistStatus($assets);
    }

    /** Get a single asset by ID */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS owner_name, u.profile_image AS owner_avatar, u.bio AS owner_bio
             FROM assets a
             JOIN users u ON a.owner_id = u.user_id
             WHERE a.asset_id = ?"
        );
        $stmt->execute([$id]);
        $asset = $stmt->fetch();
        return $asset ?: null;
    }

    /** Show asset detail page */
    public function show(int $id): void
    {
        $asset = $this->getById($id);
        if (!$asset) {
            $_SESSION['flash_error'] = t('asset_not_found');
            redirect('home');
        }

        // Fetch gallery images
        $stmt = $this->db->prepare(
            "SELECT * FROM asset_images WHERE asset_id = ? ORDER BY sort_order"
        );
        $stmt->execute([$id]);
        $galleryImages = $stmt->fetchAll();

        // Fetch reviews
        $stmt = $this->db->prepare(
            "SELECT r.*, u.full_name, u.profile_image
             FROM reviews r
             JOIN users u ON r.user_id = u.user_id
             WHERE r.asset_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$id]);
        $reviews = $stmt->fetchAll();

        // Average rating
        $stmt = $this->db->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE asset_id = ?");
        $stmt->execute([$id]);
        $avgRating = round((float) $stmt->fetch()['avg_rating'], 1);

        // Similar assets
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS owner_name,
                    COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.asset_id = a.asset_id), 0) AS avg_rating
             FROM assets a
             JOIN users u ON a.owner_id = u.user_id
             WHERE a.category = ? AND a.asset_id != ? AND a.status = 'active' AND a.is_approved = 1
             ORDER BY RAND() LIMIT 3"
        );
        $stmt->execute([$asset['category'], $id]);
        $similarAssets = $stmt->fetchAll();

        // Wishlist check
        $inWishlist = false;
        if (isLoggedIn()) {
            $stmt = $this->db->prepare(
                "SELECT wishlist_id FROM wishlists WHERE user_id = ? AND asset_id = ?"
            );
            $stmt->execute([$_SESSION['user_id'], $id]);
            $inWishlist = (bool) $stmt->fetch();
        }

        require __DIR__ . '/../views/assets/asset_detail.php';
    }

    // ─── SMART SEARCH ─────────────────────────────────────────

    /** Search / Filter assets — returns JSON for AJAX */
    public function search(): void
    {
        $keyword   = trim($_GET['keyword'] ?? '');
        $category  = $_GET['category'] ?? '';
        $city      = trim($_GET['city'] ?? '');
        $location  = trim($_GET['location'] ?? '');
        $minPrice  = isset($_GET['min_price']) ? (float) $_GET['min_price'] : null;
        $maxPrice  = isset($_GET['max_price']) ? (float) $_GET['max_price'] : null;
        $startDate = $_GET['start_date'] ?? '';
        $endDate   = $_GET['end_date'] ?? '';
        $sort      = $_GET['sort'] ?? 'newest';

        // Category-specific filters
        $carYear   = $_GET['car_year'] ?? '';
        $carMake   = $_GET['car_make'] ?? '';
        $rooms     = isset($_GET['rooms']) ? (int) $_GET['rooms'] : null;
        $sportType = $_GET['sport_type'] ?? '';

        $sql = "SELECT a.*, u.full_name AS owner_name,
                       COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.asset_id = a.asset_id), 0) AS avg_rating,
                       (SELECT COUNT(*) FROM reviews r WHERE r.asset_id = a.asset_id) AS review_count
                FROM assets a
                JOIN users u ON a.owner_id = u.user_id
                WHERE a.status = 'active' AND a.is_approved = 1";
        $params = [];

        // Keyword search (title + description)
        if (!empty($keyword)) {
            $sql .= " AND (a.title LIKE ? OR a.description LIKE ? OR a.address LIKE ?)";
            $like = "%{$keyword}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Category filter
        if (!empty($category)) {
            $sql .= " AND a.category = ?";
            $params[] = $category;
        }

        // City filter
        $locationTerm = !empty($city) ? $city : (!empty($location) ? $location : '');
        if (!empty($locationTerm)) {
            $sql .= " AND (a.city LIKE ? OR a.address LIKE ?)";
            $params[] = "%{$locationTerm}%";
            $params[] = "%{$locationTerm}%";
        }

        // Price range
        if ($minPrice !== null) {
            $sql .= " AND (a.price_per_day >= ? OR a.price_per_hour >= ?)";
            $params[] = $minPrice;
            $params[] = $minPrice;
        }
        if ($maxPrice !== null) {
            $sql .= " AND (a.price_per_day <= ? OR (a.price_per_day = 0 AND a.price_per_hour <= ?))";
            $params[] = $maxPrice;
            $params[] = $maxPrice;
        }

        // Date range — exclude assets with conflicting bookings
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " AND a.asset_id NOT IN (
                        SELECT b.asset_id FROM bookings b
                        WHERE b.status != 'cancelled'
                          AND b.start_time < ?
                          AND b.end_time   > ?
                      )";
            $params[] = $endDate;
            $params[] = $startDate;
        }

        // Category-specific filters (JSON fields)
        if (!empty($carYear) && $category === 'car') {
            $sql .= " AND JSON_EXTRACT(a.extra_fields, '$.year') = ?";
            $params[] = (int) $carYear;
        }
        if (!empty($carMake) && $category === 'car') {
            $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.extra_fields, '$.make')) LIKE ?";
            $params[] = "%{$carMake}%";
        }
        if ($rooms !== null && $category === 'apartment') {
            $sql .= " AND JSON_EXTRACT(a.extra_fields, '$.rooms') >= ?";
            $params[] = $rooms;
        }
        if (!empty($sportType) && $category === 'sport_venue') {
            $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.extra_fields, '$.sport_type')) LIKE ?";
            $params[] = "%{$sportType}%";
        }

        // Sort
        switch ($sort) {
            case 'price_low':
                $sql .= " ORDER BY GREATEST(a.price_per_day, a.price_per_hour) ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY GREATEST(a.price_per_day, a.price_per_hour) DESC";
                break;
            case 'rating':
                $sql .= " ORDER BY avg_rating DESC";
                break;
            default:
                $sql .= " ORDER BY a.created_at DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $assets = $stmt->fetchAll();

        // Save search to history
        if (isLoggedIn() && (!empty($keyword) || !empty($category) || !empty($locationTerm))) {
            $this->saveSearchHistory($keyword ?: $category, $category, $locationTerm);
        }

        $assets = $this->attachWishlistStatus($assets);
        jsonResponse(['assets' => $assets, 'count' => count($assets)]);
    }

    // ─── AUTOCOMPLETE ─────────────────────────────────────────

    /** Return search suggestions — AJAX */
    public function autocomplete(): void
    {
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            jsonResponse(['suggestions' => []]);
        }

        $stmt = $this->db->prepare(
            "SELECT DISTINCT title, category, city
             FROM assets
             WHERE status = 'active' AND is_approved = 1
               AND (title LIKE ? OR city LIKE ? OR description LIKE ?)
             LIMIT 8"
        );
        $like = "%{$query}%";
        $stmt->execute([$like, $like, $like]);
        $results = $stmt->fetchAll();

        $suggestions = array_map(function ($r) {
            $icons = ['apartment' => '🏠', 'car' => '🚗', 'sport_venue' => '⚽',
                       'equipment' => '🔧', 'studio' => '🎨', 'parking' => '🅿️'];
            return [
                'text' => $r['title'],
                'category' => $r['category'],
                'city' => $r['city'],
                'icon' => $icons[$r['category']] ?? '📦'
            ];
        }, $results);

        jsonResponse(['suggestions' => $suggestions]);
    }

    // ─── RECOMMENDATIONS ──────────────────────────────────────

    /** Get recommended assets for the logged-in user */
    public function getRecommendations(): void
    {
        $recs = [];

        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];

            // Based on booking history — find categories user has booked before
            $stmt = $this->db->prepare(
                "SELECT DISTINCT a.category
                 FROM bookings b
                 JOIN assets a ON b.asset_id = a.asset_id
                 WHERE b.user_id = ?
                 ORDER BY b.created_at DESC LIMIT 3"
            );
            $stmt->execute([$userId]);
            $bookedCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($bookedCategories)) {
                $placeholders = implode(',', array_fill(0, count($bookedCategories), '?'));
                $stmt = $this->db->prepare(
                    "SELECT a.*, u.full_name AS owner_name,
                            COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.asset_id = a.asset_id), 0) AS avg_rating
                     FROM assets a
                     JOIN users u ON a.owner_id = u.user_id
                     WHERE a.status = 'active' AND a.is_approved = 1
                       AND a.category IN ({$placeholders})
                       AND a.asset_id NOT IN (SELECT b.asset_id FROM bookings b WHERE b.user_id = ?)
                     ORDER BY avg_rating DESC, a.created_at DESC
                     LIMIT 6"
                );
                $stmt->execute([...$bookedCategories, $userId]);
                $recs = $stmt->fetchAll();
            }

            // Based on search history
            if (count($recs) < 6) {
                $stmt = $this->db->prepare(
                    "SELECT DISTINCT category FROM search_history
                     WHERE user_id = ? AND category IS NOT NULL
                     ORDER BY created_at DESC LIMIT 3"
                );
                $stmt->execute([$userId]);
                $searchCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($searchCategories)) {
                    $existingIds = array_column($recs, 'asset_id');
                    $placeholders = implode(',', array_fill(0, count($searchCategories), '?'));
                    $excludePlaceholders = !empty($existingIds) ? implode(',', array_fill(0, count($existingIds), '?')) : '0';

                    $stmt = $this->db->prepare(
                        "SELECT a.*, u.full_name AS owner_name,
                                COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.asset_id = a.asset_id), 0) AS avg_rating
                         FROM assets a
                         JOIN users u ON a.owner_id = u.user_id
                         WHERE a.status = 'active' AND a.is_approved = 1
                           AND a.category IN ({$placeholders})
                           AND a.asset_id NOT IN ({$excludePlaceholders})
                         ORDER BY avg_rating DESC
                         LIMIT ?"
                    );
                    $limit = 6 - count($recs);
                    $stmt->execute([...$searchCategories, ...$existingIds, $limit]);
                    $recs = array_merge($recs, $stmt->fetchAll());
                }
            }
        }

        // Fallback: top-rated popular assets
        if (count($recs) < 6) {
            $existingIds = array_column($recs, 'asset_id');
            $excludePlaceholders = !empty($existingIds) ? implode(',', array_fill(0, count($existingIds), '?')) : '0';
            $limit = 6 - count($recs);

            $stmt = $this->db->prepare(
                "SELECT a.*, u.full_name AS owner_name,
                        COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.asset_id = a.asset_id), 0) AS avg_rating
                 FROM assets a
                 JOIN users u ON a.owner_id = u.user_id
                 WHERE a.status = 'active' AND a.is_approved = 1
                   AND a.asset_id NOT IN ({$excludePlaceholders})
                 ORDER BY avg_rating DESC, a.created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([...$existingIds, $limit]);
            $recs = array_merge($recs, $stmt->fetchAll());
        }

        $recs = $this->attachWishlistStatus($recs);
        jsonResponse(['assets' => $recs]);
    }

    // ─── OWNER DASHBOARD ──────────────────────────────────────

    /** Owner Dashboard */
    public function dashboard(): void
    {
        requireLogin();
        if (isAdmin()) {
            $_SESSION['flash_error'] = t('access_denied');
            redirect('admin');
        }
        $ownerId = $_SESSION['user_id'];

        // My assets
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$ownerId]);
        $myAssets = $stmt->fetchAll();

        // Incoming bookings for my assets
        $stmt = $this->db->prepare(
            "SELECT b.*, a.title AS asset_title, u.full_name AS renter_name
             FROM bookings b
             JOIN assets a ON b.asset_id = a.asset_id
             JOIN users  u ON b.user_id  = u.user_id
             WHERE a.owner_id = ?
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$ownerId]);
        $incomingBookings = $stmt->fetchAll();

        require __DIR__ . '/../views/owner/dashboard.php';
    }

    // ─── CREATE ───────────────────────────────────────────────

    /** Create a new asset */
    public function create(): void
    {
        requireLogin();
        if (isAdmin()) {
            $_SESSION['flash_error'] = t('access_denied');
            redirect('admin');
        }

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = $_POST['category'] ?? '';
        $address     = trim($_POST['address'] ?? '');
        $city        = trim($_POST['city'] ?? '');
        $priceHour   = (float) ($_POST['price_per_hour'] ?? 0);
        $priceDay    = (float) ($_POST['price_per_day'] ?? 0);
        $latitude    = (float) ($_POST['latitude'] ?? 0);
        $longitude   = (float) ($_POST['longitude'] ?? 0);

        if (empty($title) || empty($category)) {
            $_SESSION['flash_error'] = t('title_category_required');
            redirect('dashboard');
        }

        // Build extra fields JSON
        $extraFields = $this->buildExtraFields($category);

        // Handle main image upload
        $imageUrl = $this->handleImageUpload();

        // New listings require admin approval
        $stmt = $this->db->prepare(
            "INSERT INTO assets (owner_id, title, description, category, address, city,
                                latitude, longitude, price_per_hour, price_per_day,
                                image_url, status, is_approved, extra_fields)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, ?)"
        );
        $stmt->execute([
            $_SESSION['user_id'], $title, $description, $category, $address, $city,
            $latitude, $longitude, $priceHour, $priceDay, $imageUrl,
            $extraFields ? json_encode($extraFields) : null
        ]);

        $assetId = (int) $this->db->lastInsertId();

        // Handle gallery images
        $this->handleGalleryUpload($assetId);

        // Notify admins about new listing
        $admins = $this->db->query("SELECT user_id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            NotificationController::create(
                $admin['user_id'],
                t('new_listing_pending'),
                "New listing '{$title}' requires approval.",
                'approval',
                'index.php?page=admin&tab=assets'
            );
        }

        $_SESSION['flash_success'] = t('asset_pending_approval');
        redirect('dashboard');
    }

    /** Update an existing asset */
    public function update(): void
    {
        requireLogin();
        if (isAdmin()) {
            $_SESSION['flash_error'] = t('access_denied');
            redirect('admin');
        }
        $assetId = (int) ($_POST['asset_id'] ?? 0);

        // Verify ownership
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE asset_id = ? AND owner_id = ?");
        $stmt->execute([$assetId, $_SESSION['user_id']]);
        $asset = $stmt->fetch();

        if (!$asset) {
            $_SESSION['flash_error'] = t('asset_not_found_access');
            redirect('dashboard');
        }

        $title       = trim($_POST['title'] ?? $asset['title']);
        $description = trim($_POST['description'] ?? $asset['description']);
        $category    = $_POST['category'] ?? $asset['category'];
        $address     = trim($_POST['address'] ?? $asset['address']);
        $city        = trim($_POST['city'] ?? $asset['city'] ?? '');
        $priceHour   = (float) ($_POST['price_per_hour'] ?? $asset['price_per_hour']);
        $priceDay    = (float) ($_POST['price_per_day'] ?? $asset['price_per_day']);
        $latitude    = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : $asset['latitude'];
        $longitude   = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : $asset['longitude'];

        $extraFields = $this->buildExtraFields($category);
        $imageUrl = $this->handleImageUpload() ?: $asset['image_url'];

        $stmt = $this->db->prepare(
            "UPDATE assets
             SET title=?, description=?, category=?, address=?, city=?,
                 latitude=?, longitude=?, price_per_hour=?, price_per_day=?,
                 image_url=?, extra_fields=?
             WHERE asset_id=? AND owner_id=?"
        );
        $stmt->execute([
            $title, $description, $category, $address, $city,
            $latitude, $longitude, $priceHour, $priceDay, $imageUrl,
            $extraFields ? json_encode($extraFields) : $asset['extra_fields'],
            $assetId, $_SESSION['user_id']
        ]);

        // Handle gallery images
        $this->handleGalleryUpload($assetId);

        $_SESSION['flash_success'] = t('asset_updated');
        redirect('dashboard');
    }

    /** Delete an asset (owner only) */
    public function delete(int $assetId): void
    {
        requireLogin();
        $stmt = $this->db->prepare("DELETE FROM assets WHERE asset_id = ? AND owner_id = ?");
        $stmt->execute([$assetId, $_SESSION['user_id']]);

        $_SESSION['flash_success'] = t('asset_deleted');
        redirect('dashboard');
    }

    // ─── HELPERS ──────────────────────────────────────────────

    /** Handle main image file upload */
    private function handleImageUpload(): ?string
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['image'];
        if ($file['size'] > 2 * 1024 * 1024) return null; // 2MB limit

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) return null;

        $filename = uniqid('asset_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        return 'uploads/' . $filename;
    }

    /** Handle multiple gallery image uploads */
    private function handleGalleryUpload(int $assetId): void
    {
        if (!isset($_FILES['gallery']) || !is_array($_FILES['gallery']['name'])) return;

        $uploadDir = __DIR__ . '/../uploads/gallery/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFiles = 5;
        $count = 0;

        foreach ($_FILES['gallery']['name'] as $i => $name) {
            if ($count >= $maxFiles) break;
            if ($_FILES['gallery']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['gallery']['size'][$i] > 2 * 1024 * 1024) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;

            $filename = uniqid("gallery_{$assetId}_{$i}_") . '.' . $ext;
            if (move_uploaded_file($_FILES['gallery']['tmp_name'][$i], $uploadDir . $filename)) {
                $stmt = $this->db->prepare(
                    "INSERT INTO asset_images (asset_id, image_url, sort_order) VALUES (?, ?, ?)"
                );
                $stmt->execute([$assetId, 'uploads/gallery/' . $filename, $count]);
                $count++;
            }
        }
    }

    /** Build extra fields JSON from POST data based on category */
    private function buildExtraFields(string $category): ?array
    {
        switch ($category) {
            case 'apartment':
                return [
                    'rooms'    => (int) ($_POST['rooms'] ?? 0),
                    'size_sqm' => (int) ($_POST['size_sqm'] ?? 0),
                    'floor'    => (int) ($_POST['floor'] ?? 0),
                    'amenities'=> array_filter(explode(',', $_POST['amenities'] ?? ''))
                ];
            case 'car':
                return [
                    'year'         => (int) ($_POST['car_year'] ?? 0),
                    'make'         => trim($_POST['car_make'] ?? ''),
                    'model'        => trim($_POST['car_model'] ?? ''),
                    'fuel'         => trim($_POST['fuel'] ?? ''),
                    'seats'        => (int) ($_POST['seats'] ?? 0),
                    'transmission' => trim($_POST['transmission'] ?? '')
                ];
            case 'sport_venue':
                return [
                    'sport_type' => trim($_POST['sport_type'] ?? ''),
                    'indoor'     => isset($_POST['indoor']),
                    'capacity'   => (int) ($_POST['capacity'] ?? 0),
                    'has_lights' => isset($_POST['has_lights'])
                ];
            case 'equipment':
                return [
                    'equipment_type' => trim($_POST['equipment_type'] ?? ''),
                    'brand'          => trim($_POST['equipment_brand'] ?? ''),
                    'condition'      => trim($_POST['equipment_condition'] ?? 'Good')
                ];
            case 'studio':
                return [
                    'studio_type'    => trim($_POST['studio_type'] ?? ''),
                    'size_sqm'       => (int) ($_POST['size_sqm'] ?? 0),
                    'has_equipment'  => isset($_POST['has_equipment'])
                ];
            case 'parking':
                return [
                    'covered'     => isset($_POST['covered']),
                    'ev_charging' => isset($_POST['ev_charging']),
                    'size'        => trim($_POST['parking_size'] ?? 'Standard')
                ];
            default:
                return null;
        }
    }

    /** Save search query to history */
    private function saveSearchHistory(string $query, ?string $category, ?string $city): void
    {
        if (!isLoggedIn()) return;
        $stmt = $this->db->prepare(
            "INSERT INTO search_history (user_id, query, category, city) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $query, $category ?: null, $city ?: null]);
    }

    /** Attach wishlist status to a list of assets */
    private function attachWishlistStatus(array $assets): array
    {
        if (empty($assets) || !isLoggedIn()) {
            return $assets;
        }
        $stmt = $this->db->prepare("SELECT asset_id FROM wishlists WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $wishlists = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($assets as &$asset) {
            $asset['in_wishlist'] = in_array($asset['asset_id'], $wishlists);
        }
        return $assets;
    }
}
