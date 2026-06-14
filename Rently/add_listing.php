<?php
// add_listing.php - Add a new listing with price type and Sports field category
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || isAdmin()) { redirect('index.php'); }

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    $category = cleanInput($_POST['category']);
    $price = (float) $_POST['price'];
    $price_type = cleanInput($_POST['price_type']);
    $city = cleanInput($_POST['city']);
    $user_id = $_SESSION['user_id'];
    
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
    
    // File upload logic (limit to ~2MB)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileSize = $_FILES['image']['size'];
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadDir = 'uploads/';
        
        if(!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        
        $destination = $uploadDir . $fileName;
        
        if ($fileSize > 2000000) {
            $error = "Image is too large. Max 2MB.";
        } else {
            if (move_uploaded_file($fileTmp, $destination)) {
                $stmt = $pdo->prepare("INSERT INTO listings (user_id, title, description, category, price, price_type, city, image, attributes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$user_id, $title, $description, $category, $price, $price_type, $city, $destination, $attributes])) {
                    $message = __('Listing added successfully! Waiting for admin approval.');
                } else {
                    $error = "Database failed.";
                }
            } else {
                $error = "Failed to upload image.";
            }
        }
    } else {
        $error = "Please upload an image.";
    }
}

require_once 'includes/header.php';
?>

<div class="auth-box" style="max-width:600px;">
    <h2><?= __('Add New Listing') ?></h2>
    <p style="margin-bottom: 20px;"><?= __('Rent out your items') ?></p>
    
    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label><?= __('Title') ?></label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. 2022 Honda Civic">
        </div>
        
        <div class="form-group">
            <label><?= __('Category') ?></label>
            <select name="category" id="listingCategory" class="form-control" required>
                <option value="Cars">Cars</option>
                <option value="Apartments">Apartments</option>
                <option value="Equipment">Equipment</option>
                <option value="Electronics">Electronics</option>
                <option value="Sports field">Sports field</option>
            </select>
        </div>
        
        <div id="dynamicFieldsCars" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
            <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:10px;">
                <div class="form-group" style="flex:1 1 100px; margin-bottom:0;">
                    <label>Make</label>
                    <input type="text" name="car_make" class="form-control" placeholder="e.g. Toyota">
                </div>
                <div class="form-group" style="flex:1 1 100px; margin-bottom:0;">
                    <label>Year</label>
                    <input type="number" name="car_year" class="form-control" placeholder="e.g. 2022">
                </div>
                <div class="form-group" style="flex:1 1 100px; margin-bottom:0;">
                    <label>Seats</label>
                    <input type="number" name="car_seats" class="form-control" placeholder="e.g. 5">
                </div>
                <div class="form-group" style="flex:1 1 150px; margin-bottom:0;">
                    <label>Fuel Type</label>
                    <select name="car_fuel" class="form-control">
                        <option value="Gasoline">Gasoline</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="Diesel">Diesel</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1 1 150px; margin-bottom:0;">
                    <label>Transmission</label>
                    <select name="car_trans" class="form-control">
                        <option value="Automatic">Automatic</option>
                        <option value="Manual">Manual</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="dynamicFieldsApt" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
            <div style="display:flex; gap:15px; margin-bottom:10px;">
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Rooms</label>
                    <input type="number" name="apt_rooms" class="form-control" placeholder="e.g. 3">
                </div>
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Bathrooms</label>
                    <input type="number" name="apt_bathrooms" class="form-control" step="0.5" placeholder="e.g. 1.5">
                </div>
                <div class="form-group" style="flex:1; margin-bottom:0; display:flex; align-items:flex-end; gap:5px;">
                    <input type="checkbox" name="apt_wifi" id="awf" value="1" style="width:20px; height:20px; margin-bottom:12px;"> 
                    <label for="awf" style="margin:0; font-size:14px;">WiFi</label>
                </div>
                <div class="form-group" style="flex:1; margin-bottom:0; display:flex; align-items:flex-end; gap:5px;">
                    <input type="checkbox" name="apt_pool" id="apl" value="1" style="width:20px; height:20px; margin-bottom:12px;"> 
                    <label for="apl" style="margin:0; font-size:14px;">Pool</label>
                </div>
            </div>
        </div>

        <div id="dynamicFieldsEquipment" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
            <div style="display:flex; gap:15px; margin-bottom:10px;">
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Brand</label>
                    <input type="text" name="eq_brand" class="form-control" placeholder="e.g. Bosch">
                </div>
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Condition</label>
                    <select name="eq_condition" class="form-control">
                        <option value="New">New</option>
                        <option value="Excellent">Excellent</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="dynamicFieldsElectronics" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
            <div style="display:flex; gap:15px; margin-bottom:10px;">
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Brand</label>
                    <input type="text" name="el_brand" class="form-control" placeholder="e.g. Apple">
                </div>
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Model</label>
                    <input type="text" name="el_model" class="form-control" placeholder="e.g. MacBook Pro">
                </div>
            </div>
        </div>

        <div id="dynamicFieldsSports" style="display:none; background:var(--card-bg); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--border-color);">
            <div style="display:flex; gap:15px; margin-bottom:10px;">
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Size (sqm)</label>
                    <input type="number" name="sport_size" class="form-control" placeholder="e.g. 500">
                </div>
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Sport Type</label>
                    <select name="sport_type" class="form-control">
                        <option value="Soccer">Soccer</option>
                        <option value="Basketball">Basketball</option>
                        <option value="Tennis">Tennis</option>
                        <option value="Multi-purpose">Multi-purpose</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label><?= __('City') ?></label>
            <input type="text" name="city" class="form-control" required placeholder="e.g. New York">
        </div>

        <div style="display:flex; gap:15px;">
            <div class="form-group" style="flex:2;">
                <label><?= __('Price') ?></label>
                <input type="number" step="0.01" name="price" class="form-control" required placeholder="50.00">
            </div>
            <div class="form-group" style="flex:1;">
                <label><?= __('Per') ?></label>
                <select name="price_type" class="form-control">
                    <option value="day"><?= __('Day') ?></option>
                    <option value="hour"><?= __('Hour') ?></option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label><?= __('Description') ?></label>
            <textarea name="description" class="form-control" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label><?= __('Image Upload') ?></label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
        </div>

        <button type="submit" name="submit" class="btn btn-primary" style="width:100%"><?= __('Add Listing') ?></button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>

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
});
</script>
