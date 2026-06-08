<?php
// index.php - Homepage with Smart Search
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle favorite toggle via AJAX
if (isset($_POST['toggle_favorite']) && isLoggedIn() && !isAdmin()) {
    header('Content-Type: application/json');
    $lid = (int) $_POST['listing_id'];
    $uid = $_SESSION['user_id'];
    $check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
    $check->execute([$uid, $lid]);
    if ($check->rowCount() > 0) {
        $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND listing_id = ?")->execute([$uid, $lid]);
        echo json_encode(['status' => 'removed']);
    } else {
        $pdo->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)")->execute([$uid, $lid]);
        echo json_encode(['status' => 'added']);
    }
    exit();
}

// Prepare search filters
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$category = isset($_GET['category']) ? cleanInput($_GET['category']) : '';
$city = isset($_GET['city']) ? cleanInput($_GET['city']) : '';
$sort = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'newest';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$check_in = isset($_GET['check_in']) && $_GET['check_in'] !== '' ? $_GET['check_in'] : null;
$check_out = isset($_GET['check_out']) && $_GET['check_out'] !== '' ? $_GET['check_out'] : null;
$car_make = isset($_GET['car_make']) ? cleanInput($_GET['car_make']) : '';
$apt_min_rooms = isset($_GET['apt_min_rooms']) && $_GET['apt_min_rooms'] !== '' ? (int) $_GET['apt_min_rooms'] : null;
$eq_brand = isset($_GET['eq_brand']) ? cleanInput($_GET['eq_brand']) : '';
$el_brand = isset($_GET['el_brand']) ? cleanInput($_GET['el_brand']) : '';
$sport_type = isset($_GET['sport_type']) ? cleanInput($_GET['sport_type']) : '';

// Check if any filter is active (to auto-open filters)
$hasFilters = ($category !== '' || $city !== '' || $min_price !== null || $max_price !== null || $check_in || $check_out || $sort !== 'newest' || $car_make !== '' || $apt_min_rooms !== null || $eq_brand !== '' || $el_brand !== '' || $sport_type !== '');

// Build the SQL query dynamically
$query = "SELECT *, (SELECT COUNT(*) FROM reports WHERE listing_id = listings.id) as report_count FROM listings WHERE status = 'approved'";
$params = [];

if (isLoggedIn() && !isAdmin()) {
    $query .= " AND user_id != ?";
    $params[] = $_SESSION['user_id'];
}


if ($search !== '') {
    $query .= " AND (title LIKE ? OR description LIKE ? OR category LIKE ? OR city LIKE ?)";
    $likeSearch = '%' . $search . '%';
    array_push($params, $likeSearch, $likeSearch, $likeSearch, $likeSearch);
}
if ($category !== '') { $query .= " AND category = ?"; $params[] = $category; }
if ($city !== '') { $query .= " AND city = ?"; $params[] = $city; }
if ($category === 'Cars' && $car_make !== '') {
    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.make')) LIKE ?";
    $params[] = '%' . $car_make . '%';
}
if ($category === 'Apartments' && $apt_min_rooms !== null) {
    $query .= " AND CAST(JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.rooms')) AS UNSIGNED) >= ?";
    $params[] = $apt_min_rooms;
}
if ($category === 'Equipment' && $eq_brand !== '') {
    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.brand')) LIKE ?";
    $params[] = '%' . $eq_brand . '%';
}
if ($category === 'Electronics' && $el_brand !== '') {
    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.brand')) LIKE ?";
    $params[] = '%' . $el_brand . '%';
}
if ($category === 'Sports field' && $sport_type !== '') {
    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.sport_type')) = ?";
    $params[] = $sport_type;
}
if ($min_price !== null) { $query .= " AND price >= ?"; $params[] = $min_price; }
if ($max_price !== null) { $query .= " AND price <= ?"; $params[] = $max_price; }

// Smart availability filter
if ($check_in && $check_out) {
    $query .= " AND id NOT IN (
        SELECT listing_id FROM bookings 
        WHERE status != 'rejected' 
        AND start_date <= ? AND end_date >= ?
    )";
    $params[] = $check_out;
    $params[] = $check_in;
}

if ($sort === 'price_asc') { $query .= " ORDER BY price ASC"; }
elseif ($sort === 'price_desc') { $query .= " ORDER BY price DESC"; }
else { $query .= " ORDER BY created_at DESC"; }

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT DISTINCT category FROM listings WHERE status='approved'")->fetchAll(PDO::FETCH_COLUMN);
$cities = $pdo->query("SELECT DISTINCT city FROM listings WHERE status='approved'")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-inner">
        <h1><?= __('Find What You Need') ?></h1>
        <p class="hero-subtitle"><?= __('Cars, Apartments') ?></p>
        
        <!-- Main Search Bar -->
        <form method="GET" action="index.php" class="hero-search-bar">
            <div class="hero-search-input">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" id="searchKeyword" placeholder="<?= __('Search keyword...') ?>" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary hero-search-btn"><?= __('Search') ?></button>
        </form>

        <!-- Toggle Filters Button -->
        <button type="button" id="toggleFilters" class="filter-toggle-btn">
            ⚙️ <?= __('Show Filters') ?>
        </button>
    </div>
</div>

<!-- Collapsible Advanced Filters -->
<div class="container">
    <div id="advancedFilters" class="advanced-filters <?= $hasFilters ? 'show' : '' ?>">
        <form method="GET" action="index.php" class="filters-grid">
            <!-- Keep search value -->
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            
            <div class="filter-group">
                <label><?= __('Category') ?></label>
                <select name="category" id="searchCategory" class="form-control">
                    <option value=""><?= __('All Categories') ?></option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><?= __('City') ?></label>
                <select name="city" class="form-control">
                    <option value=""><?= __('All Cities') ?></option>
                    <?php foreach($cities as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><?= __('Min ₪') ?></label>
                <input type="number" name="min_price" class="form-control" placeholder="0" value="<?= $min_price !== null ? htmlspecialchars($min_price) : '' ?>">
            </div>

            <div class="filter-group">
                <label><?= __('Max ₪') ?></label>
                <input type="number" name="max_price" class="form-control" placeholder="999" value="<?= $max_price !== null ? htmlspecialchars($max_price) : '' ?>">
            </div>

            <div class="filter-group">
                <label><?= __('Check-in') ?></label>
                <input type="date" name="check_in" class="form-control" value="<?= $check_in ? htmlspecialchars($check_in) : '' ?>">
            </div>

            <div class="filter-group">
                <label><?= __('Check-out') ?></label>
                <input type="date" name="check_out" class="form-control" value="<?= $check_out ? htmlspecialchars($check_out) : '' ?>">
            </div>

            <div class="filter-group" id="filterCarMake" style="display:none;">
                <label><?= __('Car Make') ?></label>
                <input type="text" name="car_make" class="form-control" placeholder="<?= __('e.g. Tesla') ?>" value="<?= htmlspecialchars($car_make) ?>">
            </div>

            <div class="filter-group" id="filterAptRooms" style="display:none;">
                <label><?= __('Min Rooms') ?></label>
                <input type="number" name="apt_min_rooms" class="form-control" placeholder="1" value="<?= $apt_min_rooms !== null ? htmlspecialchars($apt_min_rooms) : '' ?>">
            </div>

            <div class="filter-group" id="filterEqBrand" style="display:none;">
                <label><?= __('Equipment Brand') ?></label>
                <input type="text" name="eq_brand" class="form-control" placeholder="<?= __('e.g. Bosch') ?>" value="<?= htmlspecialchars($eq_brand) ?>">
            </div>
            
            <div class="filter-group" id="filterElBrand" style="display:none;">
                <label><?= __('Electronics Brand') ?></label>
                <input type="text" name="el_brand" class="form-control" placeholder="<?= __('e.g. Apple') ?>" value="<?= htmlspecialchars($el_brand) ?>">
            </div>

            <div class="filter-group" id="filterSportType" style="display:none;">
                <label><?= __('Sport Type') ?></label>
                <select name="sport_type" class="form-control">
                    <option value=""><?= __('Any') ?></option>
                    <option value="Soccer" <?= $sport_type === 'Soccer' ? 'selected' : '' ?>>Soccer</option>
                    <option value="Basketball" <?= $sport_type === 'Basketball' ? 'selected' : '' ?>>Basketball</option>
                    <option value="Tennis" <?= $sport_type === 'Tennis' ? 'selected' : '' ?>>Tennis</option>
                    <option value="Multi-purpose" <?= $sport_type === 'Multi-purpose' ? 'selected' : '' ?>>Multi-purpose</option>
                </select>
            </div>

            <div class="filter-group">
                <label><?= __('Sort') ?></label>
                <select name="sort" class="form-control">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= __('Newest First') ?></option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>><?= __('Price: Low to High') ?></option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= __('Price: High to Low') ?></option>
                </select>
            </div>

            <div class="filter-group filter-actions">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= __('Apply Filters') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <?php if(count($listings) > 0): ?>
        <p style="color:#718096; margin-bottom:20px;"><?= count($listings) ?> <?= __('results found') ?></p>
        <div class="grid">
            <?php foreach($listings as $item): 
                $rating = getAverageRating($pdo, $item['id']);
                $priceLabel = ($item['price_type'] ?? 'day') === 'hour' ? __('/ hour') : __('/ day');
            ?>
                <div class="card animate-fade-in" style="position:relative;">
                    <?php if(isLoggedIn() && !isAdmin()): ?>
                        <button class="fav-btn" data-id="<?= $item['id'] ?>" title="<?= __('Add to Favorites') ?>">
                            <?= isFavorited($pdo, $_SESSION['user_id'], $item['id']) ? '❤️' : '🤍' ?>
                        </button>
                    <?php endif; ?>
                    <a href="view_listing.php?id=<?= $item['id'] ?>" style="text-decoration:none; color:inherit;">
                        <div class="card-img-wrapper" style="position:relative;">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                            <div style="position:absolute; top:15px; left:15px; display:flex; flex-direction:column; gap:10px; align-items:flex-start;">
                                <span class="badge" style="box-shadow:0 4px 10px rgba(0,0,0,0.3);"><?= htmlspecialchars($item['category']) ?></span>
                                <?php if(isset($item['report_count']) && $item['report_count'] > 0): ?>
                                    <span class="badge" style="background:var(--error-color); color:white; box-shadow:0 4px 10px rgba(0,0,0,0.3);">⚠️ <?= __('Reported') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <p style="color:#718096; font-size:14px; margin-bottom:10px;">📍 <?= htmlspecialchars($item['city']) ?></p>
                            <?php if($rating['total'] > 0): ?>
                                <p class="stars" style="margin-bottom:10px;"><?= str_repeat('★', round($rating['avg'])) ?><span style="color:#e2e8f0;"><?= str_repeat('★', 5 - round($rating['avg'])) ?></span> <small style="color:#718096;">(<?= $rating['total'] ?>)</small></p>
                            <?php endif; ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto;">
                                <p class="card-price" style="margin:0;">₪<?= htmlspecialchars($item['price']) ?> <span style="font-size:14px; color:#a0aec0; font-weight:normal;"><?= $priceLabel ?></span></p>
                                <span class="btn btn-primary" style="padding: 8px 16px; font-size: 13px; border-radius: 20px;"><?= __('View Details') ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center" style="padding: 100px 0;">
            <div style="font-size:60px; margin-bottom:15px;">🔍</div>
            <h2 style="font-size: 2rem; margin-bottom: 15px; color:#4a5568;"><?= __('No listings found.') ?></h2>
            <p style="color:#718096; font-size: 1.1rem;"><?= __('Try adjusting') ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle advanced filters
document.getElementById('toggleFilters').addEventListener('click', function() {
    const filters = document.getElementById('advancedFilters');
    filters.classList.toggle('show');
    this.textContent = filters.classList.contains('show') ? '⚙️ <?= __('Hide Filters') ?>' : '⚙️ <?= __('Show Filters') ?>';
});

// Dynamic category filters
const searchCat = document.getElementById('searchCategory');
const filterCarMake = document.getElementById('filterCarMake');
const filterAptRooms = document.getElementById('filterAptRooms');
const filterEqBrand = document.getElementById('filterEqBrand');
const filterElBrand = document.getElementById('filterElBrand');
const filterSportType = document.getElementById('filterSportType');

function updateSearchFilters() {
    if(!searchCat) return;
    filterCarMake.style.display = 'none';
    filterAptRooms.style.display = 'none';
    filterEqBrand.style.display = 'none';
    filterElBrand.style.display = 'none';
    filterSportType.style.display = 'none';
    
    if(searchCat.value === 'Cars') filterCarMake.style.display = 'block';
    if(searchCat.value === 'Apartments') filterAptRooms.style.display = 'block';
    if(searchCat.value === 'Equipment') filterEqBrand.style.display = 'block';
    if(searchCat.value === 'Electronics') filterElBrand.style.display = 'block';
    if(searchCat.value === 'Sports field') filterSportType.style.display = 'block';
}
if(searchCat) {
    searchCat.addEventListener('change', updateSearchFilters);
    updateSearchFilters();
}

// Favorite toggle via AJAX
document.querySelectorAll('.fav-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const id = this.dataset.id;
        const el = this;
        fetch('index.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'toggle_favorite=1&listing_id=' + id
        })
        .then(r => r.json())
        .then(data => { el.textContent = data.status === 'added' ? '❤️' : '🤍'; });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
