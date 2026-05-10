<?php
require_once 'includes/db.php';

try {
    // 1. Drop old tables and recreate with new schema
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("DROP TABLE IF EXISTS favorites, reviews, bookings, listings, users, notifications, tickets, ticket_messages;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        profile_picture VARCHAR(255) DEFAULT 'assets/img/default_avatar.png',
        bio TEXT,
        is_blocked TINYINT(1) DEFAULT 0,
        role ENUM('user', 'admin') DEFAULT 'user',
        verified TINYINT(1) DEFAULT 0,
        verification_code VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS listings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        price_type ENUM('day', 'hour') DEFAULT 'day',
        city VARCHAR(100) NOT NULL,
        image VARCHAR(255) NOT NULL,
        attributes JSON DEFAULT NULL,
        status ENUM('pending', 'approved') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        listing_id INT NOT NULL,
        user_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('pending_admin', 'pending_owner', 'approved', 'rejected', 'waitlist') DEFAULT 'pending_admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        listing_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        listing_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
        UNIQUE KEY user_listing (user_id, listing_id)
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT '#',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('open', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS ticket_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";
    
    $pdo->exec($sql);
    echo "Tables created successfully.<br>";

    // 2. Clear existing entries
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE favorites; TRUNCATE TABLE reviews; TRUNCATE TABLE bookings; TRUNCATE TABLE listings; TRUNCATE TABLE users; TRUNCATE TABLE notifications; TRUNCATE TABLE tickets; TRUNCATE TABLE ticket_messages; SET FOREIGN_KEY_CHECKS = 1;");

    // 3. Insert Seed Data
    $pwdAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    $pwdUser1 = password_hash('password123', PASSWORD_DEFAULT);

    // Users
    $stmtUser = $pdo->prepare("INSERT INTO users (id, name, email, password, phone, role, verified, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtUser->execute([1, 'Admin', 'admin@rently.test', $pwdAdmin, '000000000', 'admin', 1, 'System Administrator.']);
    $stmtUser->execute([2, 'John Doe', 'john@rently.test', $pwdUser1, '123456789', 'user', 1, 'I love renting cars.']);
    $stmtUser->execute([3, 'Jane Smith', 'jane@rently.test', $pwdUser1, '987654321', 'user', 1, 'Apartment owner in SF.']);
    $stmtUser->execute([4, 'Mike Ross', 'mike@rently.test', $pwdUser1, '111222333', 'user', 1, 'Tech enthusiast and gadget renter.']);

    // Listings 
    $stmtListing = $pdo->prepare("INSERT INTO listings (id, user_id, title, description, category, price, price_type, city, image, attributes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmtListing->execute([1, 2, '2022 Honda Civic', 'A reliable and comfortable car for city trips. Excellent gas mileage.', 'Cars', 45.00, 'day', 'Tel Aviv', 'https://images.pexels.com/photos/1149137/pexels-photo-1149137.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['make'=>'Honda', 'year'=>'2022', 'seats'=>5]), 'approved']);
    $stmtListing->execute([2, 3, 'Cozy Studio Downtown', 'Modern studio apartment right in the heart of the city.', 'Apartments', 120.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/439227/pexels-photo-439227.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['rooms'=>1, 'bathrooms'=>1]), 'approved']);
    $stmtListing->execute([3, 2, 'Canon EOS R5 Camera', 'Professional camera for your next photoshoot. Comes with 2 lenses.', 'Equipment', 15.00, 'hour', 'Haifa', 'https://images.pexels.com/photos/51383/photo-camera-subject-photographer-51383.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);
    $stmtListing->execute([4, 4, 'Jeep Wrangler 4x4', 'Perfect for off-road adventures!', 'Cars', 85.00, 'day', 'Eilat', 'https://images.pexels.com/photos/119435/pexels-photo-119435.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['make'=>'Jeep', 'year'=>'2021', 'seats'=>4]), 'approved']);
    $stmtListing->execute([5, 3, 'Beachfront Villa', 'Wake up to the sound of waves. 3 bedrooms and private pool.', 'Apartments', 350.00, 'day', 'Netanya', 'https://images.pexels.com/photos/258159/pexels-photo-258159.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['rooms'=>3, 'bathrooms'=>2]), 'approved']);
    $stmtListing->execute([6, 4, 'Electric Scooter Pro', 'Fast and fun way to get around the city.', 'Equipment', 5.00, 'hour', 'Tel Aviv', 'https://images.pexels.com/photos/3671151/pexels-photo-3671151.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);
    $stmtListing->execute([7, 2, 'Tesla Model 3', 'Experience the future of driving with this fully electric sleek car.', 'Cars', 95.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/11194510/pexels-photo-11194510.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['make'=>'Tesla', 'year'=>'2023', 'seats'=>5]), 'approved']);
    $stmtListing->execute([8, 4, 'High-End Gaming PC', 'Rent a massive rig for the weekend! RTX 4090 included.', 'Electronics', 60.00, 'day', 'Eilat', 'https://images.pexels.com/photos/777001/pexels-photo-777001.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);
    $stmtListing->execute([9, 2, 'Indoor Basketball Court', 'Full sized pristine indoor court. 2 hour slots only.', 'Sports field', 25.00, 'hour', 'Tel Aviv', 'https://images.pexels.com/photos/358042/pexels-photo-358042.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);


    // Bookings (now with pending_admin vs approved status)
    $stmtBooking = $pdo->prepare("INSERT INTO bookings (listing_id, user_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtBooking->execute([1, 3, date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+3 days')), 90.00, 'approved']);
    $stmtBooking->execute([2, 2, date('Y-m-d', strtotime('+5 days')), date('Y-m-d', strtotime('+7 days')), 240.00, 'pending_admin']);
    
    // Reviews
    $stmtReview = $pdo->prepare("INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmtReview->execute([1, 3, 5, "Amazing car! Super clean and ran perfectly."]);
    $stmtReview->execute([1, 4, 4, "Great car, pickup was a bit difficult though."]);
    $stmtReview->execute([2, 4, 5, "Absolutely loved the studio! Very highly recommended."]);
    $stmtReview->execute([5, 2, 5, "The view is unbelievable. Will rent again next summer."]);
    $stmtReview->execute([7, 3, 5, "Driving a Tesla is an experience. Owner was very nice!"]);

    // Favorites
    $stmtFav = $pdo->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)");
    $stmtFav->execute([2, 5]);
    $stmtFav->execute([3, 7]);

    echo "Seed data (users, items, bookings, reviews, favorites) populated successfully! <br>";
    echo "<b>Admin Details:</b> admin@rently.test / admin123 <br>";
    echo "<b>User Details:</b> john@rently.test / password123 <br>";
    echo "<a href='index.php'>Go to Home</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
