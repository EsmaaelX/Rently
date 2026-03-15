<?php
/**
 * AdminController
 * Admin panel: list users, list assets, block/unblock users.
 */
class AdminController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Render admin dashboard */
    public function index(): void
    {
        // All users (except current admin)
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE user_id != ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $users = $stmt->fetchAll();

        // All assets
        $stmt = $this->db->query(
            "SELECT a.*, u.full_name AS owner_name
             FROM Assets a
             JOIN Users u ON a.owner_id = u.user_id
             ORDER BY a.created_at DESC"
        );
        $allAssets = $stmt->fetchAll();

        // Stats
        $totalUsers    = $this->db->query("SELECT COUNT(*) FROM Users")->fetchColumn();
        $totalAssets   = $this->db->query("SELECT COUNT(*) FROM Assets")->fetchColumn();
        $totalBookings = $this->db->query("SELECT COUNT(*) FROM Bookings")->fetchColumn();
        $totalRevenue  = $this->db->query("SELECT IFNULL(SUM(amount),0) FROM Payments WHERE status='paid'")->fetchColumn();

        require __DIR__ . '/../views/admin/admin.php';
    }

    /** Toggle block/unblock a user */
    public function toggleBlock(int $userId): void
    {
        $stmt = $this->db->prepare("SELECT is_blocked FROM Users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            redirect('admin');
        }

        $newStatus = $user['is_blocked'] ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE Users SET is_blocked = ? WHERE user_id = ?");
        $stmt->execute([$newStatus, $userId]);

        $action = $newStatus ? 'blocked' : 'unblocked';
        $_SESSION['flash_success'] = "User has been {$action}.";
        redirect('admin');
    }
}
