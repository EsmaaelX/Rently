<?php
/**
 * ProfileController
 * User profile management: view, edit, upload avatar, booking history.
 */
class ProfileController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Show the user profile page */
    public function show(): void
    {
        requireLogin();
        $userId = $_SESSION['user_id'];

        // User data
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        $bookings = [];
        $reviews = [];
        $wishlist = [];

        if (!isAdmin()) {

        // Booking history
        $stmt = $this->db->prepare(
            "SELECT b.*, a.title AS asset_title, a.category, a.image_url AS asset_image
             FROM bookings b
             JOIN assets a ON b.asset_id = a.asset_id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll();

        // User reviews
        $stmt = $this->db->prepare(
            "SELECT r.*, a.title AS asset_title
             FROM reviews r
             JOIN assets a ON r.asset_id = a.asset_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$userId]);
        $reviews = $stmt->fetchAll();

        // Wishlist
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS owner_name
             FROM wishlists w
             JOIN assets a ON w.asset_id = a.asset_id
             JOIN users u ON a.owner_id = u.user_id
             WHERE w.user_id = ?
             ORDER BY w.created_at DESC"
        );
        $stmt->execute([$userId]);
        $wishlist = $stmt->fetchAll();

        }

        require __DIR__ . '/../views/profile/profile.php';
    }

    /** Update user profile */
    public function update(): void
    {
        requireLogin();
        $userId = $_SESSION['user_id'];

        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone_number'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');

        if (empty($fullName)) {
            $_SESSION['flash_error'] = t('name_required');
            redirect('profile');
        }

        // Handle avatar upload
        $profileImage = $this->handleAvatarUpload();

        if ($profileImage) {
            $stmt = $this->db->prepare(
                "UPDATE users SET full_name = ?, phone_number = ?, bio = ?, profile_image = ? WHERE user_id = ?"
            );
            $stmt->execute([$fullName, $phone, $bio, $profileImage, $userId]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE users SET full_name = ?, phone_number = ?, bio = ? WHERE user_id = ?"
            );
            $stmt->execute([$fullName, $phone, $bio, $userId]);
        }

        $_SESSION['full_name'] = $fullName;
        $_SESSION['flash_success'] = t('profile_updated');
        redirect('profile');
    }

    /** Handle avatar image upload */
    private function handleAvatarUpload(): ?string
    {
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['profile_image'];
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
            return null;
        }

        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            return null;
        }

        $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        return 'uploads/avatars/' . $filename;
    }
}
