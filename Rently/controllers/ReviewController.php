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
            jsonResponse(['success' => false, 'error' => 'Please log in first.'], 401);
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if (!$assetId || $rating < 1 || $rating > 5) {
            jsonResponse(['success' => false, 'error' => 'Invalid rating or asset.'], 400);
        }

        // Prevent duplicate review
        $stmt = $this->db->prepare(
            "SELECT review_id FROM Reviews WHERE user_id = ? AND asset_id = ?"
        );
        $stmt->execute([$_SESSION['user_id'], $assetId]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => 'You have already reviewed this asset.'], 409);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO Reviews (user_id, asset_id, rating, comment) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $assetId, $rating, $comment]);

        jsonResponse([
            'success' => true,
            'message' => 'Review submitted!',
            'review'  => [
                'full_name' => $_SESSION['full_name'],
                'rating'    => $rating,
                'comment'   => $comment,
            ]
        ]);
    }
}
