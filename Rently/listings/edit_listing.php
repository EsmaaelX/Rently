<?php
// listings/edit_listing.php - Edit listing details
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || isAdmin()) { redirect(BASE_URL . 'index.php'); }

if (!isset($_GET['id'])) { redirect(BASE_URL . 'user/profile.php'); }

$id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];
$draftKey = "rently_edit_listing_draft_{$id}_user_{$user_id}";

// Fetch listing
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) { redirect(BASE_URL . 'user/profile.php'); }

$error = '';
$message = '';
$success = false;

// Handle delete additional image
if (isset($_POST['delete_image_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $imgId = (int)$_POST['delete_image_id'];
        
        $stmtImg = $pdo->prepare("SELECT image_path FROM listing_images WHERE id = ? AND listing_id = ?");
        $stmtImg->execute([$imgId, $id]);
        $path = $stmtImg->fetchColumn();
        
        if ($path) {
            if (file_exists('../' . $path)) {
                unlink('../' . $path);
            }
            $pdo->prepare("DELETE FROM listing_images WHERE id = ?")->execute([$imgId]);
            $message = __('Image deleted successfully.');
        } else {
            $error = __('Image not found.');
        }
    }
}

// Handle Update Listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = __('CSRF token verification failed.');
    } else {
        $title = cleanInput($_POST['title']);
        $description = cleanInput($_POST['description']);
        $category = cleanInput($_POST['category']);
        $price = (float) $_POST['price'];
        $price_type = cleanInput($_POST['price_type']);
        $city = cleanInput($_POST['city']);
        
        if ($price <= 0) {
            $error = __('Price must be a positive number.');
        } else {
            $attributes = null;
            if ($category === 'Cars') {
                $attributes = json_encode([
                    'make' => cleanInput($_POST['car_make'] ?? ''),
                    'year' => cleanInput($_POST['car_year'] ?? ''),
                    'seats' => cleanInput($_POST['car_seats'] ?? ''),
                    'fuel_type' => cleanInput($_POST['car_fuel'] ?? ''),
                    'transmission' => cleanInput($_POST['car_trans'] ?? '')
                ]);
            } elseif ($category === 'Apartments') {
                $attributes = json_encode([
                    'rooms' => cleanInput($_POST['apt_rooms'] ?? ''),
                    'bathrooms' => cleanInput($_POST['apt_bathrooms'] ?? ''),
                    'wifi' => isset($_POST['apt_wifi']) ? 'Yes' : 'No',
                    'pool' => isset($_POST['apt_pool']) ? 'Yes' : 'No'
                ]);
            } elseif ($category === 'Sports field') {
                $attributes = json_encode([
                    'size_sqm' => cleanInput($_POST['sport_size'] ?? ''),
                    'sport_type' => cleanInput($_POST['sport_type'] ?? '')
                ]);
            } elseif ($category === 'Equipment') {
                $attributes = json_encode([
                    'brand' => cleanInput($_POST['eq_brand'] ?? ''),
                    'condition' => cleanInput($_POST['eq_condition'] ?? '')
                ]);
            } elseif ($category === 'Electronics') {
                $attributes = json_encode([
                    'brand' => cleanInput($_POST['el_brand'] ?? ''),
                    'model' => cleanInput($_POST['el_model'] ?? '')
                ]);
            }

            $image_sql = "";
            $image_params = [];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $mainUpload = uploadImage('image', '../uploads/');
                if (isset($mainUpload['error'])) {
                    $error = $mainUpload['error'];
                } else {
                    if (file_exists('../' . $listing['image'])) {
                        unlink('../' . $listing['image']);
                    }
                    $dbMainPath = str_replace('../', '', $mainUpload['path']);
                    $image_sql = ", image = ?";
                    $image_params = [$dbMainPath];
                }
            }
            
            if (!$error) {
                $additionalUploads = uploadMultipleImages('additional_images', '../uploads/');
                $additionalPaths = $additionalUploads['paths'] ?? [];
                
                if (isset($additionalUploads['errors']) && count($additionalUploads['errors']) > 0) {
                    $error = implode("<br>", $additionalUploads['errors']);
                }
                
                if (empty($error)) {
                    try {
                        $pdo->beginTransaction();
                        
                        $stmtUpdate = $pdo->prepare("UPDATE listings SET title=?, description=?, category=?, price=?, price_type=?, city=?, attributes=?, status='pending' $image_sql WHERE id = ? AND user_id = ?");
                        $params = array_merge([$title, $description, $category, $price, $price_type, $city, $attributes], $image_params, [$id, $user_id]);
                        $stmtUpdate->execute($params);
                        
                        if (count($additionalPaths) > 0) {
                            $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id, image_path) VALUES (?, ?)");
                            foreach ($additionalPaths as $path) {
                                $dbPath = str_replace('../', '', $path);
                                $stmtImg->execute([$id, $dbPath]);
                            }
                        }
                        
                        $pdo->commit();
                        $message = __('Listing updated successfully! Status reset to pending for admin approval.');
                        $success = true;
                        
                        $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
                        $stmt->execute([$id, $user_id]);
                        $listing = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                    } catch (Exception $ex) {
                        $pdo->rollBack();
                        foreach ($additionalPaths as $path) {
                            if (file_exists($path)) unlink($path);
                        }
                        $error = __('Database error: ') . $ex->getMessage();
                    }
                }
            }
        }
    }
}

$stmtImgs = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ?");
$stmtImgs->execute([$id]);
$existingImages = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

$listingAttrs = json_decode($listing['attributes'] ?? '{}', true);

require_once '../includes/header.php';
?>

<div class="auth-box" style="max-width:600px;">
    <h2>✏️ <?= __('Edit Listing') ?></h2>
    
    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <?php if ($success): ?>
        <script>
            localStorage.removeItem('<?= $draftKey ?>');
        </script>
        <div style="text-align:center; margin-top:20px;">
            <a href="<?= BASE_URL ?>user/profile.php" class="btn btn-primary"><?= __('Go to My Listings') ?></a>
        </div>
    <?php else: ?>
        <form method="POST" action="" enctype="multipart/form-data" id="editListingForm">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="update" value="1">
            
            <div class="form-group">
                <label><?= __('Title') ?></label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($listing['title']) ?>">
            </div>
            
            <div class="form-group">
                <label><?= __('Category') ?></label>
                <select name="category" id="listingCategory" class="form-control" required>
                    <option value="Cars" <?= $listing['category'] === 'Cars' ? 'selected' : '' ?>><?= __('Cars') ?></option>
                    <option value="Apartments" <?= $listing['category'] === 'Apartments' ? 'selected' : '' ?>><?= __('Apartments') ?></option>
                    <option value="Equipment" <?= $listing['category'] === 'Equipment' ? 'selected' : '' ?>><?= __('Equipment') ?></option>
                    <option value="Electronics" <?= $listing['category'] === 'Electronics' ? 'selected' : '' ?>><?= __('Electronics') ?></option>
                    <option value="Sports field" <?= $listing['category'] === 'Sports field' ? 'selected' : '' ?>><?= __('Sports field') ?></option>
                </select>
            </div>
            
            <!-- Dynamic Fields -->
            <div id="dynamicFieldsCars" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
                <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:10px;">
                    <div class="form-group" style="flex:1 1 100px; margin-bottom:0;">
                        <label>Make</label>
                        <input type="text" name="car_make" class="form-control" value="<?= htmlspecialchars($listingAttrs['make'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1 1 100px; margin-bottom:0;">
                        <label>Year</label>
                        <input type="number" name="car_year" class="form-control" value="<?= htmlspecialchars($listingAttrs['year'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1 1 100px; margin-bottom:0;">
                        <label>Seats</label>
                        <input type="number" name="car_seats" class="form-control" value="<?= htmlspecialchars($listingAttrs['seats'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1 1 150px; margin-bottom:0;">
                        <label>Fuel Type</label>
                        <select name="car_fuel" class="form-control">
                            <option value="Gasoline" <?= ($listingAttrs['fuel_type'] ?? '') === 'Gasoline' ? 'selected' : '' ?>>Gasoline</option>
                            <option value="Electric" <?= ($listingAttrs['fuel_type'] ?? '') === 'Electric' ? 'selected' : '' ?>>Electric</option>
                            <option value="Hybrid" <?= ($listingAttrs['fuel_type'] ?? '') === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                            <option value="Diesel" <?= ($listingAttrs['fuel_type'] ?? '') === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1 1 150px; margin-bottom:0;">
                        <label>Transmission</label>
                        <select name="car_trans" class="form-control">
                            <option value="Automatic" <?= ($listingAttrs['transmission'] ?? '') === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                            <option value="Manual" <?= ($listingAttrs['transmission'] ?? '') === 'Manual' ? 'selected' : '' ?>>Manual</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="dynamicFieldsApt" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
                <div style="display:flex; gap:15px; margin-bottom:10px;">
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Rooms</label>
                        <input type="number" name="apt_rooms" class="form-control" value="<?= htmlspecialchars($listingAttrs['rooms'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Bathrooms</label>
                        <input type="number" name="apt_bathrooms" class="form-control" step="0.5" value="<?= htmlspecialchars($listingAttrs['bathrooms'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1; margin-bottom:0; display:flex; align-items:flex-end; gap:5px;">
                        <input type="checkbox" name="apt_wifi" id="awf" value="1" style="width:20px; height:20px; margin-bottom:12px;" <?= ($listingAttrs['wifi'] ?? '') === 'Yes' ? 'checked' : '' ?>> 
                        <label for="awf" style="margin:0; font-size:14px;">WiFi</label>
                    </div>
                    <div class="form-group" style="flex:1; margin-bottom:0; display:flex; align-items:flex-end; gap:5px;">
                        <input type="checkbox" name="apt_pool" id="apl" value="1" style="width:20px; height:20px; margin-bottom:12px;" <?= ($listingAttrs['pool'] ?? '') === 'Yes' ? 'checked' : '' ?>> 
                        <label for="apl" style="margin:0; font-size:14px;">Pool</label>
                    </div>
                </div>
            </div>

            <div id="dynamicFieldsEquipment" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
                <div style="display:flex; gap:15px; margin-bottom:10px;">
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Brand</label>
                        <input type="text" name="eq_brand" class="form-control" value="<?= htmlspecialchars($listingAttrs['brand'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Condition</label>
                        <select name="eq_condition" class="form-control">
                            <option value="New" <?= ($listingAttrs['condition'] ?? '') === 'New' ? 'selected' : '' ?>>New</option>
                            <option value="Excellent" <?= ($listingAttrs['condition'] ?? '') === 'Excellent' ? 'selected' : '' ?>>Excellent</option>
                            <option value="Good" <?= ($listingAttrs['condition'] ?? '') === 'Good' ? 'selected' : '' ?>>Good</option>
                            <option value="Fair" <?= ($listingAttrs['condition'] ?? '') === 'Fair' ? 'selected' : '' ?>>Fair</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="dynamicFieldsElectronics" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
                <div style="display:flex; gap:15px; margin-bottom:10px;">
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Brand</label>
                        <input type="text" name="el_brand" class="form-control" value="<?= htmlspecialchars($listingAttrs['brand'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Model</label>
                        <input type="text" name="el_model" class="form-control" value="<?= htmlspecialchars($listingAttrs['model'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div id="dynamicFieldsSports" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
                <div style="display:flex; gap:15px; margin-bottom:10px;">
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Size (sqm)</label>
                        <input type="number" name="sport_size" class="form-control" value="<?= htmlspecialchars($listingAttrs['size_sqm'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Sport Type</label>
                        <select name="sport_type" class="form-control">
                            <option value="Soccer" <?= ($listingAttrs['sport_type'] ?? '') === 'Soccer' ? 'selected' : '' ?>>Soccer</option>
                            <option value="Basketball" <?= ($listingAttrs['sport_type'] ?? '') === 'Basketball' ? 'selected' : '' ?>>Basketball</option>
                            <option value="Tennis" <?= ($listingAttrs['sport_type'] ?? '') === 'Tennis' ? 'selected' : '' ?>>Tennis</option>
                            <option value="Multi-purpose" <?= ($listingAttrs['sport_type'] ?? '') === 'Multi-purpose' ? 'selected' : '' ?>>Multi-purpose</option>
                        </select>
                    </div>
                </div>
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
                <img src="<?= htmlspecialchars(BASE_URL . $listing['image']) ?>" style="width:100%; max-height:200px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
                <label><?= __('Upload New Image (optional)') ?></label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>

            <!-- Existing Additional Images -->
            <?php if (count($existingImages) > 0): ?>
                <div class="form-group">
                    <label><?= __('Current Additional Images') ?></label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                        <?php foreach($existingImages as $img): ?>
                            <div style="position:relative; width:80px; height:60px;">
                                <img src="<?= htmlspecialchars(BASE_URL . $img['image_path']) ?>" style="width:80px; height:60px; object-fit:cover; border-radius:6px;">
                                <button type="submit" name="delete_image_id" value="<?= $img['id'] ?>" style="position:absolute; top:-5px; right:-5px; background:var(--error-color); color:white; border:none; width:18px; height:18px; border-radius:50%; font-size:10px; cursor:pointer; line-height:16px; text-align:center;" onclick="return confirm('Delete this image?');">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label><?= __('Upload Additional Images (Optional)') ?></label>
                <input type="file" name="additional_images[]" class="form-control" accept="image/*" multiple>
                <small style="color:#718096;"><?= __('Selected images must be chosen again if restoring a draft.') ?></small>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%"><?= __('Save Changes') ?></button>
        </form>
    <?php endif; ?>
</div>

<script src="../assets/js/form_recovery.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('listingCategory');
    const carFields = document.getElementById('dynamicFieldsCars');
    const aptFields = document.getElementById('dynamicFieldsApt');
    const eqFields = document.getElementById('dynamicFieldsEquipment');
    const elFields = document.getElementById('dynamicFieldsElectronics');
    const spFields = document.getElementById('dynamicFieldsSports');

    function updateFields() {
        if(!catSelect) return;
        carFields.style.display = 'none';
        aptFields.style.display = 'none';
        eqFields.style.display = 'none';
        elFields.style.display = 'none';
        spFields.style.display = 'none';
        
        switch(catSelect.value) {
            case 'Cars': carFields.style.display = 'block'; break;
            case 'Apartments': aptFields.style.display = 'block'; break;
            case 'Equipment': eqFields.style.display = 'block'; break;
            case 'Electronics': elFields.style.display = 'block'; break;
            case 'Sports field': spFields.style.display = 'block'; break;
        }
    }
    if(catSelect) {
        catSelect.addEventListener('change', updateFields);
        updateFields();
    }
    
    // Initialize LocalStorage Form Recovery
    <?php if (!$success): ?>
        initFormRecovery('editListingForm', '<?= $draftKey ?>');
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>
