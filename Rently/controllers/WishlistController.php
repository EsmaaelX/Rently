<?php
/**
 * WishlistController
 * Add/remove assets from the user's wishlist (AJAX).
 */
class WishlistController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Toggle wishlist (add/remove) — AJAX */
    public function toggle(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => t('login_required')], 401);
        }

        if (isAdmin()) {
            jsonResponse(['success' => false, 'error' => t('access_denied')], 403);
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        if (!$assetId) {
            jsonResponse(['success' => false, 'error' => 'Invalid asset.'], 400);
        }

        $userId = $_SESSION['user_id'];

        // Check if already in wishlist
        $stmt = $this->db->prepare(
            "SELECT wishlist_id FROM wishlists WHERE user_id = ? AND asset_id = ?"
        );
        $stmt->execute([$userId, $assetId]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Remove
            $stmt = $this->db->prepare("DELETE FROM wishlists WHERE user_id = ? AND asset_id = ?");
            $stmt->execute([$userId, $assetId]);
            jsonResponse(['success' => true, 'action' => 'removed', 'message' => t('removed_from_wishlist')]);
        } else {
            // Add
            $stmt = $this->db->prepare("INSERT INTO wishlists (user_id, asset_id) VALUES (?, ?)");
            $stmt->execute([$userId, $assetId]);
            jsonResponse(['success' => true, 'action' => 'added', 'message' => t('added_to_wishlist')]);
        }
    }

    /** Check if asset is in user's wishlist — AJAX */
    public function check(): void
    {
        if (!isLoggedIn() || isAdmin()) {
            jsonResponse(['in_wishlist' => false]);
        }

        $assetId = (int) ($_GET['asset_id'] ?? 0);
        $stmt = $this->db->prepare(
            "SELECT wishlist_id FROM wishlists WHERE user_id = ? AND asset_id = ?"
        );
        $stmt->execute([$_SESSION['user_id'], $assetId]);

        jsonResponse(['in_wishlist' => (bool) $stmt->fetch()]);
    }
}
