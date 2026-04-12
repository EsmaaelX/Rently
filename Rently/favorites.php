<?php
// favorites.php - User's Saved Listings
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || isAdmin()) { redirect('index.php'); }

$uid = $_SESSION['user_id'];

// Remove favorite
if (isset($_GET['remove'])) {
    $lid = (int) $_GET['remove'];
    $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND listing_id = ?")->execute([$uid, $lid]);
    redirect('favorites.php');
}

// Fetch favorites
$stmt = $pdo->prepare("
    SELECT l.* FROM favorites f 
    JOIN listings l ON f.listing_id = l.id 
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC
");
$stmt->execute([$uid]);
$favs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px;">
    <h1 style="margin-bottom: 30px;">❤️ <?= __('My Favorites') ?></h1>
    
    <?php if(count($favs) > 0): ?>
        <div class="grid">
            <?php foreach($favs as $item): 
                $rating = getAverageRating($pdo, $item['id']);
                $priceLabel = ($item['price_type'] ?? 'day') === 'hour' ? __('/ hour') : __('/ day');
            ?>
                <div class="card animate-fade-in" style="position:relative;">
                    <a href="favorites.php?remove=<?= $item['id'] ?>" class="fav-btn" title="<?= __('Remove') ?>" onclick="return confirm('Remove from favorites?');">❤️</a>
                    <a href="view_listing.php?id=<?= $item['id'] ?>" style="text-decoration:none; color:inherit;">
                        <div class="card-img-wrapper" style="position:relative;">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                            <span class="badge" style="position:absolute; top:15px; left:15px;"><?= htmlspecialchars($item['category']) ?></span>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <p style="color:#718096; font-size:14px; margin-bottom:10px;">📍 <?= htmlspecialchars($item['city']) ?></p>
                            <?php if($rating['total'] > 0): ?>
                                <p class="stars" style="margin-bottom:10px;"><?= str_repeat('★', round($rating['avg'])) ?><span style="color:#e2e8f0;"><?= str_repeat('★', 5 - round($rating['avg'])) ?></span></p>
                            <?php endif; ?>
                            <p class="card-price" style="margin:0;">₪<?= htmlspecialchars($item['price']) ?> <span style="font-size:14px; color:#a0aec0; font-weight:normal;"><?= $priceLabel ?></span></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center" style="padding: 80px 0;">
            <div style="font-size:60px; margin-bottom:20px;">🤍</div>
            <h2 style="color:#4a5568;"><?= __('No favorites yet.') ?></h2>
            <p style="color:#718096; margin-bottom:20px;"><?= __('Browse listings and click the heart icon to save items here.') ?></p>
            <a href="index.php" class="btn btn-primary"><?= __('Browse Listings') ?></a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
