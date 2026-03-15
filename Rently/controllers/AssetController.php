<?php
/**
 * AssetController
 * CRUD for assets + search/filter + owner dashboard.
 */
class AssetController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Get all active assets */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, u.full_name AS owner_name
             FROM Assets a
             JOIN Users u ON a.owner_id = u.user_id
             WHERE a.status = 'active'
             ORDER BY a.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    /** Get a single asset by ID */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS owner_name
             FROM Assets a
             JOIN Users u ON a.owner_id = u.user_id
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
            $_SESSION['flash_error'] = 'Asset not found.';
            redirect('home');
        }

        // Fetch reviews for this asset
        $stmt = $this->db->prepare(
            "SELECT r.*, u.full_name
             FROM Reviews r
             JOIN Users u ON r.user_id = u.user_id
             WHERE r.asset_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$id]);
        $reviews = $stmt->fetchAll();

        // Average rating
        $stmt = $this->db->prepare("SELECT AVG(rating) AS avg_rating FROM Reviews WHERE asset_id = ?");
        $stmt->execute([$id]);
        $avgRating = round((float) $stmt->fetch()['avg_rating'], 1);

        require __DIR__ . '/../views/assets/asset_detail.php';
    }

    /** Search / Filter assets — returns JSON for AJAX */
    public function search(): void
    {
        $category = $_GET['category'] ?? '';
        $location = trim($_GET['location'] ?? '');
        $startDate = $_GET['start_date'] ?? '';
        $endDate   = $_GET['end_date'] ?? '';

        $sql    = "SELECT a.*, u.full_name AS owner_name FROM Assets a JOIN Users u ON a.owner_id = u.user_id WHERE a.status = 'active'";
        $params = [];

        if (!empty($category)) {
            $sql .= " AND a.category = ?";
            $params[] = $category;
        }

        if (!empty($location)) {
            $sql .= " AND (a.address LIKE ? OR a.title LIKE ?)";
            $params[] = "%{$location}%";
            $params[] = "%{$location}%";
        }

        // If date range supplied, exclude assets that have conflicting bookings
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " AND a.asset_id NOT IN (
                        SELECT b.asset_id FROM Bookings b
                        WHERE b.status != 'cancelled'
                          AND b.start_time < ?
                          AND b.end_time   > ?
                      )";
            $params[] = $endDate;
            $params[] = $startDate;
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $assets = $stmt->fetchAll();

        jsonResponse(['assets' => $assets]);
    }

    /** Owner Dashboard */
    public function dashboard(): void
    {
        requireLogin();
        $ownerId = $_SESSION['user_id'];

        // My assets
        $stmt = $this->db->prepare("SELECT * FROM Assets WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$ownerId]);
        $myAssets = $stmt->fetchAll();

        // Incoming bookings for my assets
        $stmt = $this->db->prepare(
            "SELECT b.*, a.title AS asset_title, u.full_name AS renter_name
             FROM Bookings b
             JOIN Assets a ON b.asset_id = a.asset_id
             JOIN Users  u ON b.user_id  = u.user_id
             WHERE a.owner_id = ?
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$ownerId]);
        $incomingBookings = $stmt->fetchAll();

        require __DIR__ . '/../views/owner/dashboard.php';
    }

    /** Create a new asset */
    public function create(): void
    {
        requireLogin();

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = $_POST['category'] ?? '';
        $address     = trim($_POST['address'] ?? '');
        $priceHour   = (float) ($_POST['price_per_hour'] ?? 0);
        $priceDay    = (float) ($_POST['price_per_day'] ?? 0);
        $status      = $_POST['status'] ?? 'active';

        if (empty($title) || empty($category)) {
            $_SESSION['flash_error'] = 'Title and category are required.';
            redirect('dashboard');
        }

        // Handle image upload
        $imageUrl = $this->handleImageUpload();

        // Mock geocoding
        $geo = (new GoogleMapsAPI())->geocode($address);

        $stmt = $this->db->prepare(
            "INSERT INTO Assets (owner_id, title, description, category, address, latitude, longitude, price_per_hour, price_per_day, image_url, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['user_id'], $title, $description, $category, $address,
            $geo['lat'], $geo['lng'], $priceHour, $priceDay, $imageUrl, $status
        ]);

        $_SESSION['flash_success'] = 'Asset added successfully!';
        redirect('dashboard');
    }

    /** Update an existing asset */
    public function update(): void
    {
        requireLogin();
        $assetId = (int) ($_POST['asset_id'] ?? 0);

        // Verify ownership
        $stmt = $this->db->prepare("SELECT * FROM Assets WHERE asset_id = ? AND owner_id = ?");
        $stmt->execute([$assetId, $_SESSION['user_id']]);
        $asset = $stmt->fetch();

        if (!$asset) {
            $_SESSION['flash_error'] = 'Asset not found or access denied.';
            redirect('dashboard');
        }

        $title       = trim($_POST['title'] ?? $asset['title']);
        $description = trim($_POST['description'] ?? $asset['description']);
        $category    = $_POST['category'] ?? $asset['category'];
        $address     = trim($_POST['address'] ?? $asset['address']);
        $priceHour   = (float) ($_POST['price_per_hour'] ?? $asset['price_per_hour']);
        $priceDay    = (float) ($_POST['price_per_day'] ?? $asset['price_per_day']);
        $status      = $_POST['status'] ?? $asset['status'];

        // Handle new image if uploaded
        $imageUrl = $this->handleImageUpload() ?: $asset['image_url'];

        $geo = (new GoogleMapsAPI())->geocode($address);

        $stmt = $this->db->prepare(
            "UPDATE Assets
             SET title=?, description=?, category=?, address=?, latitude=?, longitude=?,
                 price_per_hour=?, price_per_day=?, image_url=?, status=?
             WHERE asset_id=? AND owner_id=?"
        );
        $stmt->execute([
            $title, $description, $category, $address, $geo['lat'], $geo['lng'],
            $priceHour, $priceDay, $imageUrl, $status,
            $assetId, $_SESSION['user_id']
        ]);

        $_SESSION['flash_success'] = 'Asset updated successfully!';
        redirect('dashboard');
    }

    /** Delete an asset (owner only) */
    public function delete(int $assetId): void
    {
        requireLogin();
        $stmt = $this->db->prepare("DELETE FROM Assets WHERE asset_id = ? AND owner_id = ?");
        $stmt->execute([$assetId, $_SESSION['user_id']]);

        $_SESSION['flash_success'] = 'Asset deleted.';
        redirect('dashboard');
    }

    /** Handle image file upload → returns relative URL or null */
    private function handleImageUpload(): ?string
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($ext), $allowed)) {
            return null;
        }

        $filename = uniqid('asset_') . '.' . $ext;
        $dest     = $uploadDir . $filename;
        move_uploaded_file($_FILES['image']['tmp_name'], $dest);

        return 'uploads/' . $filename;
    }
}
