<?php
/**
 * AdminController
 * Admin panel: stats, users management, asset approval, bookings, reports.
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
        // Stats
        $totalUsers    = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalAssets   = $this->db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
        $totalBookings = $this->db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        $totalRevenue  = $this->db->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
        $pendingAssets = $this->db->query("SELECT COUNT(*) FROM assets WHERE is_approved = 0")->fetchColumn();
        $pendingReports = $this->db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

        // Users
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id != ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $users = $stmt->fetchAll();

        // All assets (including pending)
        $allAssets = $this->db->query(
            "SELECT a.*, u.full_name AS owner_name
             FROM assets a
             JOIN users u ON a.owner_id = u.user_id
             ORDER BY a.is_approved ASC, a.created_at DESC"
        )->fetchAll();

        // All bookings
        $allBookings = $this->db->query(
            "SELECT b.*, a.title AS asset_title, u.full_name AS renter_name, o.full_name AS owner_name
             FROM bookings b
             JOIN assets a ON b.asset_id = a.asset_id
             JOIN users u ON b.user_id = u.user_id
             JOIN users o ON a.owner_id = o.user_id
             ORDER BY b.created_at DESC
             LIMIT 50"
        )->fetchAll();

        // Reports
        $allReports = $this->db->query(
            "SELECT rep.*, u.full_name AS reporter_name,
                    a.title AS asset_title,
                    ru.full_name AS reported_user_name
             FROM reports rep
             JOIN users u ON rep.reporter_id = u.user_id
             LEFT JOIN assets a ON rep.asset_id = a.asset_id
             LEFT JOIN users ru ON rep.reported_user_id = ru.user_id
             ORDER BY rep.status ASC, rep.created_at DESC"
        )->fetchAll();

        // Chart data: bookings per month (last 6 months)
        $chartData = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS count,
                    SUM(total_price) AS revenue
             FROM bookings
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY month
             ORDER BY month ASC"
        )->fetchAll();

        require __DIR__ . '/../views/admin/admin.php';
    }

    /** Toggle block/unblock a user */
    public function toggleBlock(int $userId): void
    {
        $stmt = $this->db->prepare("SELECT is_blocked, full_name, email FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = t('user_not_found');
            redirect('admin');
        }

        $newStatus = $user['is_blocked'] ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE users SET is_blocked = ? WHERE user_id = ?");
        $stmt->execute([$newStatus, $userId]);

        // Notify user
        $action = $newStatus ? t('account_blocked_notification') : t('account_unblocked_notification');
        NotificationController::create($userId, $action, $action, 'system');

        $action = $newStatus ? 'blocked' : 'unblocked';
        $_SESSION['flash_success'] = "User has been {$action}.";
        redirect('admin');
    }

    /** Approve a listing */
    public function approveListing(int $assetId): void
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS owner_name
             FROM assets a JOIN users u ON a.owner_id = u.user_id
             WHERE a.asset_id = ?"
        );
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();

        if (!$asset) {
            $_SESSION['flash_error'] = t('asset_not_found');
            redirect('admin');
        }

        $stmt = $this->db->prepare(
            "UPDATE assets SET is_approved = 1, status = 'active' WHERE asset_id = ?"
        );
        $stmt->execute([$assetId]);

        // Notify owner
        NotificationController::create(
            $asset['owner_id'],
            t('listing_approved'),
            "Your listing \"{$asset['title']}\" has been approved and is now live!",
            'approval',
            "index.php?page=asset&action=detail&id={$assetId}"
        );

        $_SESSION['flash_success'] = t('listing_approved');
        redirect('admin');
    }

    /** Reject a listing */
    public function rejectListing(int $assetId): void
    {
        $reason = trim($_POST['reject_reason'] ?? 'Does not meet platform guidelines.');

        $stmt = $this->db->prepare("SELECT owner_id, title FROM assets WHERE asset_id = ?");
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();

        if ($asset) {
            $stmt = $this->db->prepare("DELETE FROM assets WHERE asset_id = ?");
            $stmt->execute([$assetId]);

            NotificationController::create(
                $asset['owner_id'],
                t('listing_rejected'),
                "Your listing \"{$asset['title']}\" was rejected. Reason: {$reason}",
                'approval'
            );
        }

        $_SESSION['flash_success'] = t('listing_rejected');
        redirect('admin');
    }

    /** Resolve a report */
    public function resolveReport(int $reportId): void
    {
        $notes = trim($_POST['admin_notes'] ?? '');

        $stmt = $this->db->prepare(
            "UPDATE reports SET status = 'resolved', admin_notes = ? WHERE report_id = ?"
        );
        $stmt->execute([$notes, $reportId]);

        $_SESSION['flash_success'] = t('report_resolved');
        redirect('admin');
    }
}
