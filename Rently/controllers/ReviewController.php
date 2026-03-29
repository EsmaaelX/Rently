<?php
/**
 * ReviewController
 * Submit and fetch reviews for assets.
 */
class ReviewController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Add a review (AJAX POST) */
    public function addReview(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => t('login_required')], 401);
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if (!$assetId || $rating < 1 || $rating > 5) {
            jsonResponse(['success' => false, 'error' => 'Invalid rating or asset.'], 400);
        }

        // Prevent duplicate review
        $stmt = $this->db->prepare(
            "SELECT review_id FROM reviews WHERE user_id = ? AND asset_id = ?"
        );
        $stmt->execute([$_SESSION['user_id'], $assetId]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => t('already_reviewed')], 409);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reviews (user_id, asset_id, rating, comment) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $assetId, $rating, $comment]);

        // Notify asset owner
        $stmt = $this->db->prepare(
            "SELECT a.owner_id, a.title FROM assets a WHERE a.asset_id = ?"
        );
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();
        if ($asset) {
            NotificationController::create(
                $asset['owner_id'],
                'New Review',
                "{$_SESSION['full_name']} left a {$rating}-star review on \"{$asset['title']}\".",
                'review',
                "index.php?page=asset&action=detail&id={$assetId}"
            );
        }

        jsonResponse([
            'success' => true,
            'message' => t('review_submitted'),
            'review'  => [
                'full_name' => $_SESSION['full_name'],
                'rating'    => $rating,
                'comment'   => $comment,
            ]
        ]);
    }
}
