<?php
/**
 * NotificationController
 * User notifications: list, mark read, unread count.
 */
class NotificationController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Show notifications page */
    public function index(): void
    {
        requireLogin();
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll();

        require __DIR__ . '/../views/notifications/list.php';
    }

    /** Get unread count — AJAX */
    public function unreadCount(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['count' => 0]);
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$_SESSION['user_id']]);
        jsonResponse(['count' => (int) $stmt->fetch()['cnt']]);
    }

    /** Mark a notification as read — AJAX */
    public function markRead(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false], 401);
        }

        $id = (int) ($_POST['notification_id'] ?? 0);
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?"
        );
        $stmt->execute([$id, $_SESSION['user_id']]);

        jsonResponse(['success' => true]);
    }

    /** Mark all as read — AJAX */
    public function markAllRead(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false], 401);
        }

        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$_SESSION['user_id']]);

        jsonResponse(['success' => true]);
    }

    /** Create a notification (called internally by other controllers) */
    public static function create(int $userId, string $title, string $message, string $type = 'system', ?string $link = null): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $title, $message, $type, $link]);
    }
}
