<?php
/**
 * ReportController
 * Submit and manage reports for assets/users.
 */
class ReportController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Submit a report — AJAX */
    public function submit(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => t('login_required')], 401);
        }

        $assetId       = !empty($_POST['asset_id']) ? (int) $_POST['asset_id'] : null;
        $reportedUserId = !empty($_POST['reported_user_id']) ? (int) $_POST['reported_user_id'] : null;
        $reason        = trim($_POST['reason'] ?? '');

        if (empty($reason)) {
            jsonResponse(['success' => false, 'error' => t('reason_required')], 400);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reports (reporter_id, asset_id, reported_user_id, reason) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $assetId, $reportedUserId, $reason]);

        // Notify admins
        $admins = $this->db->query("SELECT user_id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            NotificationController::create(
                $admin['user_id'],
                t('new_report'),
                t('report_submitted_msg'),
                'report',
                'index.php?page=admin&tab=reports'
            );
        }

        jsonResponse(['success' => true, 'message' => t('report_submitted')]);
    }
}
