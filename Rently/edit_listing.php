<?php
// edit_listing.php - Edit an existing listing
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || isAdmin()) { redirect('index.php'); }

if (!isset($_GET['id'])) { redirect('profile.php'); }

$id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch listing (only if owned by user)
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) { redirect('profile.php'); }

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    $category = cleanInput($_POST['category']);
    $price = (float) $_POST['price'];
    $price_type = cleanInput($_POST['price_type']);
    $city = cleanInput($_POST['city']);
    
    $image_sql = "";
    $image_params = [];
    
    // Handle optional new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $destination = $uploadDir . $fileName;
        
        if ($_FILES['image']['size'] > 2000000) {
            $error = "Image too large. Max 2MB.";
        } else {
            move_uploaded_file($_FILES['image']['tmp_name'], $destination);
            $image_sql = ", image = ?";
            $image_params = [$destination];
        }
    }
    
    if (!$error) {
        $stmt = $pdo->prepare("UPDATE listings SET title=?, description=?, category=?, price=?, price_type=?, city=? $image_sql WHERE id = ? AND user_id = ?");
        $params = array_merge([$title, $description, $category, $price, $price_type, $city], $image_params, [$id, $user_id]);
        $stmt->execute($params);
        $message = __('Listing updated successfully!');
        
        // Refresh listing data
        $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
        $stmt->execute([$id]);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

require_once 'includes/header.php';
?>

<div class="auth-box" style="max-width:600px;">
    <h2>✏️ <?= __('Edit Listing') ?></h2>
    
    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label><?= __('Title') ?></label>
            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($listing['title']) ?>">
        </div>
        
        <div class="form-group">
            <label><?= __('Category') ?></label>
            <select name="category" class="form-control" required>
                <?php foreach(['Cars','Apartments','Equipment','Electronics','Sports field'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= $listing['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label><?= __('City') ?></label>
            <input type="text" name="city" class="form-control" required value="<?= htmlspecialchars($listing['city']) ?>">
        </div>

        <div style="display:flex; gap:15px;">
            <div class="form-group" style="flex:2;">
                <label><?= __('Price') ?></label>
                <input type="number" step="0.01" name="price" class="form-control" required value="<?= htmlspecialchars($listing['price']) ?>">
            </div>
            <div class="form-group" style="flex:1;">
                <label><?= __('Per') ?></label>
                <select name="price_type" class="form-control">
                    <option value="day" <?= ($listing['price_type'] ?? 'day') === 'day' ? 'selected' : '' ?>><?= __('Day') ?></option>
                    <option value="hour" <?= ($listing['price_type'] ?? 'day') === 'hour' ? 'selected' : '' ?>><?= __('Hour') ?></option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label><?= __('Description') ?></label>
            <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($listing['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label><?= __('Current Image') ?></label>
            <img src="<?= htmlspecialchars($listing['image']) ?>" style="width:100%; max-height:200px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
            <label><?= __('Upload New Image (optional)') ?></label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <button type="submit" name="update" class="btn btn-primary" style="width:100%"><?= __('Save Changes') ?></button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
