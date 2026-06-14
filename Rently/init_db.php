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
    $stmtUser = $pdo->prepare("INSERT INTO users (id, name, email, password, phone, role, verified, bio, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtUser->execute([1, 'Admin', 'admin@rently.test', $pwdAdmin, '000000000', 'admin', 1, 'System Administrator.', 'https://randomuser.me/api/portraits/lego/1.jpg']);
    $stmtUser->execute([2, 'Amir Goldstein', 'amir@rently.test', $pwdUser1, '123456789', 'user', 1, 'I love renting cars.', 'https://randomuser.me/api/portraits/men/10.jpg']);
    $stmtUser->execute([3, 'Nour Al-Fayed', 'nour@rently.test', $pwdUser1, '987654321', 'user', 1, 'Apartment owner in SF.', 'https://randomuser.me/api/portraits/women/10.jpg']);
    $stmtUser->execute([4, 'Eitan Cohen', 'eitan@rently.test', $pwdUser1, '111222333', 'user', 1, 'Tech enthusiast and gadget renter.', 'https://randomuser.me/api/portraits/men/20.jpg']);
    $stmtUser->execute([5, 'Ahmed Youssef', 'ahmed@rently.test', $pwdUser1, '0501234567', 'user', 1, 'Car enthusiast and mechanic.', 'https://randomuser.me/api/portraits/men/30.jpg']);
    $stmtUser->execute([6, 'Fatima Ali', 'fatima@rently.test', $pwdUser1, '0547654321', 'user', 1, 'Love renting out my beautiful villa.', 'https://randomuser.me/api/portraits/women/20.jpg']);
    $stmtUser->execute([7, 'Omar Hassan', 'omar@rently.test', $pwdUser1, '0529876543', 'user', 1, 'Professional DJ.', 'https://randomuser.me/api/portraits/men/40.jpg']);
    $stmtUser->execute([8, 'Layla Mahmoud', 'layla@rently.test', $pwdUser1, '0534567890', 'user', 1, 'Art lover and host in Jaffa.', 'https://randomuser.me/api/portraits/women/30.jpg']);
    $stmtUser->execute([9, 'Yossi Cohen', 'yossi@rently.test', $pwdUser1, '0501112233', 'user', 1, 'Daily commuter looking to share rides.', 'https://randomuser.me/api/portraits/men/50.jpg']);
    $stmtUser->execute([10, 'Noa Levi', 'noa@rently.test', $pwdUser1, '0543332211', 'user', 1, 'Real estate investor.', 'https://randomuser.me/api/portraits/women/40.jpg']);
    $stmtUser->execute([11, 'Itzhak Mizrahi', 'itzhak@rently.test', $pwdUser1, '0524445566', 'user', 1, 'Cycling enthusiastic.', 'https://randomuser.me/api/portraits/men/60.jpg']);
    $stmtUser->execute([12, 'Maya Avraham', 'maya@rently.test', $pwdUser1, '0536667788', 'user', 1, 'Love nature and hiking.', 'https://randomuser.me/api/portraits/women/50.jpg']);
    $stmtUser->execute([13, 'Hassan Nasr', 'hassan@rently.test', $pwdUser1, '0541112222', 'user', 1, 'Traveler.', 'https://randomuser.me/api/portraits/men/70.jpg']);
    $stmtUser->execute([14, 'Shira Ben-David', 'shira@rently.test', $pwdUser1, '0523334444', 'user', 1, 'Photographer.', 'https://randomuser.me/api/portraits/women/60.jpg']);
    $stmtUser->execute([15, 'Tariq Mansour', 'tariq@rently.test', $pwdUser1, '0505556666', 'user', 1, 'Business owner.', 'https://randomuser.me/api/portraits/men/80.jpg']);
    $stmtUser->execute([16, 'Yael Rabin', 'yael@rently.test', $pwdUser1, '0537778888', 'user', 1, 'Student.', 'https://randomuser.me/api/portraits/women/70.jpg']);
    $stmtUser->execute([17, 'Zainab Qasim', 'zainab@rently.test', $pwdUser1, '0549990000', 'user', 1, 'Teacher.', 'https://randomuser.me/api/portraits/women/80.jpg']);
    $stmtUser->execute([18, 'Avi Katz', 'avi@rently.test', $pwdUser1, '0521239876', 'user', 1, 'Musician.', 'https://randomuser.me/api/portraits/men/90.jpg']);

    // Listings 
    $stmtListing = $pdo->prepare("INSERT INTO listings (id, user_id, title, description, category, price, price_type, city, image, attributes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmtListing->execute([1, 2, '2022 Honda Civic', 'A reliable and comfortable car for city trips. Excellent gas mileage.', 'Cars', 45.00, 'day', 'Tel Aviv', 'assets/img/honda_civic.png', json_encode(['make'=>'Honda', 'year'=>'2022', 'seats'=>5]), 'approved']);
    $stmtListing->execute([2, 3, 'Cozy Studio Downtown', 'Modern studio apartment right in the heart of the city.', 'Apartments', 120.00, 'day', 'Jerusalem', 'assets/img/studio_apt.png', json_encode(['rooms'=>1, 'bathrooms'=>1]), 'approved']);
    $stmtListing->execute([3, 2, 'Canon EOS R5 Camera', 'Professional camera for your next photoshoot. Comes with 2 lenses.', 'Equipment', 15.00, 'hour', 'Haifa', 'assets/img/canon_r5.png', null, 'approved']);
    $stmtListing->execute([4, 4, 'Jeep Wrangler 4x4', 'Perfect for off-road adventures!', 'Cars', 85.00, 'day', 'Eilat', 'assets/img/jeep_wrangler.png', json_encode(['make'=>'Jeep', 'year'=>'2021', 'seats'=>4]), 'approved']);
    $stmtListing->execute([5, 3, 'Beachfront Villa', 'Wake up to the sound of waves. 3 bedrooms and private pool.', 'Apartments', 350.00, 'day', 'Netanya', 'assets/img/beach_villa.png', json_encode(['rooms'=>3, 'bathrooms'=>2]), 'approved']);
    $stmtListing->execute([6, 4, 'Electric Scooter Pro', 'Fast and fun way to get around the city.', 'Equipment', 5.00, 'hour', 'Tel Aviv', 'assets/img/scooter_pro.png', null, 'approved']);
    $stmtListing->execute([7, 2, 'Tesla Model 3', 'Experience the future of driving with this fully electric sleek car.', 'Cars', 95.00, 'day', 'Jerusalem', 'assets/img/tesla_model3.png', json_encode(['make'=>'Tesla', 'year'=>'2023', 'seats'=>5]), 'approved']);
    $stmtListing->execute([8, 4, 'High-End Gaming PC', 'Rent a massive rig for the weekend! RTX 4090 included.', 'Electronics', 60.00, 'day', 'Eilat', 'assets/img/gaming_pc.png', null, 'approved']);
    $stmtListing->execute([9, 2, 'Indoor Basketball Court', 'Full sized pristine indoor court. 2 hour slots only.', 'Sports field', 25.00, 'hour', 'Tel Aviv', 'assets/img/bball_court.png', null, 'approved']);
    $stmtListing->execute([10, 5, '2023 Toyota Corolla', 'Very reliable car for family trips.', 'Cars', 50.00, 'day', 'Haifa', 'assets/img/toyota_corolla.png', json_encode(['make'=>'Toyota', 'year'=>'2023', 'seats'=>5]), 'approved']);
    $stmtListing->execute([11, 6, 'Traditional Villa', 'Spacious villa with authentic design.', 'Apartments', 250.00, 'day', 'Nazareth', 'assets/img/traditional_villa.png', json_encode(['rooms'=>4, 'bathrooms'=>3]), 'approved']);
    $stmtListing->execute([12, 7, 'DJ Pioneer Setup', 'Full professional DJ setup.', 'Equipment', 30.00, 'hour', 'Ramallah', 'assets/img/dj_setup.png', null, 'approved']);
    $stmtListing->execute([13, 8, 'Sea View Studio', 'Beautiful studio right next to the sea.', 'Apartments', 150.00, 'day', 'Jaffa', 'assets/img/seaview_studio.png', json_encode(['rooms'=>1, 'bathrooms'=>1]), 'approved']);
    $stmtListing->execute([14, 9, '2021 Hyundai Tucson', 'Great SUV for the whole family.', 'Cars', 75.00, 'day', 'Tel Aviv', 'assets/img/hyundai_tucson.png', json_encode(['make'=>'Hyundai', 'year'=>'2021', 'seats'=>5]), 'approved']);
    $stmtListing->execute([15, 10, 'Luxury Penthouse', 'High-end penthouse with a pool.', 'Apartments', 600.00, 'day', 'Herzliya', 'assets/img/luxury_penthouse.png', json_encode(['rooms'=>5, 'bathrooms'=>4]), 'approved']);
    $stmtListing->execute([16, 11, 'E-Bike Commuter', 'Fast electric bike for city transport.', 'Equipment', 10.00, 'hour', 'Jerusalem', 'assets/img/ebike.png', null, 'approved']);
    $stmtListing->execute([17, 12, 'Mountain Bike', 'Professional mountain bike.', 'Equipment', 25.00, 'day', 'Eilat', 'assets/img/mountain_bike.png', null, 'approved']);
    $stmtListing->execute([18, 5, 'Vintage Rolex Watch', 'Beautiful vintage watch for special events.', 'Equipment', 100.00, 'day', 'Haifa', 'assets/img/rolex_watch.png', null, 'approved']);
    $stmtListing->execute([19, 10, '2024 Kia Sportage', 'Brand new SUV.', 'Cars', 90.00, 'day', 'Herzliya', 'https://images.pexels.com/photos/116675/pexels-photo-116675.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['make'=>'Kia', 'year'=>'2024', 'seats'=>5]), 'approved']);
    $stmtListing->execute([20, 13, 'GoPro Hero 11', 'Capture your adventures.', 'Equipment', 15.00, 'day', 'Eilat', 'assets/img/gopro.png', null, 'approved']);
    $stmtListing->execute([21, 14, 'Professional Studio Lights', 'Great for photoshoots.', 'Equipment', 40.00, 'day', 'Tel Aviv', 'assets/img/studiolights.png', null, 'approved']);
    $stmtListing->execute([22, 15, 'Mercedes-Benz C-Class', 'Luxury sedan.', 'Cars', 120.00, 'day', 'Haifa', 'https://images.pexels.com/photos/112452/pexels-photo-112452.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['make'=>'Mercedes-Benz', 'year'=>'2022', 'seats'=>5]), 'approved']);
    $stmtListing->execute([23, 16, 'Cozy Room near University', 'Perfect for students or short stays.', 'Apartments', 50.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=600', json_encode(['rooms'=>1, 'bathrooms'=>1]), 'approved']);
    $stmtListing->execute([24, 17, 'Spacious Family Car - Mazda 5', '7 seater minivan.', 'Cars', 60.00, 'day', 'Nazareth', 'assets/img/mazda5.png', json_encode(['make'=>'Mazda', 'year'=>'2019', 'seats'=>7]), 'approved']);
    $stmtListing->execute([25, 18, 'Acoustic Guitar - Yamaha', 'Beautiful sounding acoustic guitar.', 'Equipment', 12.00, 'day', 'Tel Aviv', 'https://images.pexels.com/photos/164861/pexels-photo-164861.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);
    $stmtListing->execute([26, 13, 'Camping Tent 4-Person', 'Waterproof camping tent.', 'Equipment', 20.00, 'day', 'Eilat', 'https://images.pexels.com/photos/2398220/pexels-photo-2398220.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);
    $stmtListing->execute([27, 14, 'Drone DJI Mavic Air 2', '4K drone with extra batteries.', 'Equipment', 70.00, 'day', 'Herzliya', 'https://images.pexels.com/photos/1087180/pexels-photo-1087180.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);
    $stmtListing->execute([28, 15, 'Office Space Desk', 'Quiet desk in a shared office.', 'Apartments', 25.00, 'day', 'Haifa', 'https://images.pexels.com/photos/1170412/pexels-photo-1170412.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);
    $stmtListing->execute([29, 16, 'Electric Scooter Xiaomi', 'Great for commuting.', 'Equipment', 15.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/3671151/pexels-photo-3671151.jpeg?auto=compress&cs=tinysrgb&w=600', null, 'approved']);


    // Bookings (now with pending_admin vs approved status)
    $stmtBooking = $pdo->prepare("INSERT INTO bookings (listing_id, user_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtBooking->execute([1, 3, date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+3 days')), 90.00, 'approved']);
    $stmtBooking->execute([2, 2, date('Y-m-d', strtotime('+5 days')), date('Y-m-d', strtotime('+7 days')), 240.00, 'pending_admin']);
    $stmtBooking->execute([3, 4, date('Y-m-d', strtotime('+2 days')), date('Y-m-d', strtotime('+2 days')), 30.00, 'approved']);
    $stmtBooking->execute([4, 5, date('Y-m-d', strtotime('+10 days')), date('Y-m-d', strtotime('+12 days')), 170.00, 'pending_owner']);
    $stmtBooking->execute([5, 6, date('Y-m-d', strtotime('+4 days')), date('Y-m-d', strtotime('+6 days')), 700.00, 'approved']);
    $stmtBooking->execute([7, 8, date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+5 days')), 380.00, 'rejected']);
    $stmtBooking->execute([10, 9, date('Y-m-d', strtotime('+2 days')), date('Y-m-d', strtotime('+4 days')), 100.00, 'waitlist']);
    $stmtBooking->execute([11, 10, date('Y-m-d', strtotime('+7 days')), date('Y-m-d', strtotime('+14 days')), 1750.00, 'approved']);
    $stmtBooking->execute([15, 12, date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+3 days')), 1200.00, 'pending_owner']);
    $stmtBooking->execute([16, 13, date('Y-m-d', strtotime('+3 days')), date('Y-m-d', strtotime('+3 days')), 20.00, 'approved']);
    $stmtBooking->execute([22, 14, date('Y-m-d', strtotime('+5 days')), date('Y-m-d', strtotime('+6 days')), 120.00, 'pending_admin']);
    $stmtBooking->execute([26, 15, date('Y-m-d', strtotime('+10 days')), date('Y-m-d', strtotime('+15 days')), 100.00, 'waitlist']);
    // New bulk bookings for analytics
    $stmtBooking->execute([1, 4, date('Y-m-d', strtotime('-10 days')), date('Y-m-d', strtotime('-8 days')), 90.00, 'approved']);
    $stmtBooking->execute([1, 5, date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('-2 days')), 135.00, 'approved']);
    $stmtBooking->execute([2, 6, date('Y-m-d', strtotime('-15 days')), date('Y-m-d', strtotime('-10 days')), 600.00, 'approved']);
    $stmtBooking->execute([3, 7, date('Y-m-d', strtotime('-3 days')), date('Y-m-d', strtotime('-1 days')), 30.00, 'approved']);
    $stmtBooking->execute([4, 8, date('Y-m-d', strtotime('+1 days')), date('Y-m-d', strtotime('+3 days')), 255.00, 'approved']);
    $stmtBooking->execute([5, 9, date('Y-m-d', strtotime('-20 days')), date('Y-m-d', strtotime('-18 days')), 700.00, 'approved']);
    $stmtBooking->execute([5, 10, date('Y-m-d', strtotime('-10 days')), date('Y-m-d', strtotime('-5 days')), 1750.00, 'approved']);
    $stmtBooking->execute([8, 11, date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('-2 days')), 180.00, 'approved']);
    $stmtBooking->execute([8, 12, date('Y-m-d', strtotime('+5 days')), date('Y-m-d', strtotime('+8 days')), 180.00, 'pending_admin']);
    $stmtBooking->execute([11, 13, date('Y-m-d', strtotime('-30 days')), date('Y-m-d', strtotime('-25 days')), 1250.00, 'approved']);
    $stmtBooking->execute([11, 14, date('Y-m-d', strtotime('-15 days')), date('Y-m-d', strtotime('-10 days')), 1250.00, 'approved']);
    $stmtBooking->execute([12, 15, date('Y-m-d', strtotime('-2 days')), date('Y-m-d', strtotime('+2 days')), 120.00, 'approved']);
    $stmtBooking->execute([14, 16, date('Y-m-d', strtotime('-1 days')), date('Y-m-d', strtotime('+5 days')), 450.00, 'approved']);
    $stmtBooking->execute([15, 17, date('Y-m-d', strtotime('-10 days')), date('Y-m-d', strtotime('-8 days')), 1200.00, 'approved']);
    $stmtBooking->execute([15, 18, date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('-3 days')), 1200.00, 'rejected']);
    $stmtBooking->execute([18, 2, date('Y-m-d', strtotime('-7 days')), date('Y-m-d', strtotime('-6 days')), 100.00, 'approved']);
    $stmtBooking->execute([18, 3, date('Y-m-d', strtotime('-2 days')), date('Y-m-d', strtotime('-1 days')), 100.00, 'approved']);
    $stmtBooking->execute([19, 4, date('Y-m-d', strtotime('-12 days')), date('Y-m-d', strtotime('-8 days')), 360.00, 'approved']);
    $stmtBooking->execute([21, 5, date('Y-m-d', strtotime('-8 days')), date('Y-m-d', strtotime('-6 days')), 80.00, 'approved']);
    $stmtBooking->execute([24, 6, date('Y-m-d', strtotime('-4 days')), date('Y-m-d', strtotime('-2 days')), 120.00, 'approved']);
    
    // Reviews
    $stmtReview = $pdo->prepare("INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmtReview->execute([1, 3, 5, "Amazing car! Super clean and ran perfectly."]);
    $stmtReview->execute([1, 4, 4, "Great car, pickup was a bit difficult though."]);
    $stmtReview->execute([1, 5, 5, "Very smooth ride."]);
    $stmtReview->execute([2, 4, 5, "Absolutely loved the studio! Very highly recommended."]);
    $stmtReview->execute([2, 6, 4, "Good location, a bit noisy."]);
    $stmtReview->execute([3, 7, 5, "Perfect camera for my shoot."]);
    $stmtReview->execute([4, 8, 5, "Off-roading was fun."]);
    $stmtReview->execute([5, 2, 5, "The view is unbelievable. Will rent again next summer."]);
    $stmtReview->execute([5, 9, 5, "Amazing villa."]);
    $stmtReview->execute([5, 10, 4, "Great but expensive."]);
    $stmtReview->execute([7, 3, 5, "Driving a Tesla is an experience. Owner was very nice!"]);
    $stmtReview->execute([8, 11, 5, "Played games all weekend at ultra settings."]);
    $stmtReview->execute([11, 13, 5, "Beautiful villa in Nazareth."]);
    $stmtReview->execute([11, 14, 5, "Highly recommended."]);
    $stmtReview->execute([12, 15, 4, "Good DJ set."]);
    $stmtReview->execute([14, 16, 5, "Great family car."]);
    $stmtReview->execute([15, 17, 5, "Luxury at its best."]);
    $stmtReview->execute([18, 2, 5, "Looked great for the wedding."]);
    $stmtReview->execute([18, 3, 5, "Classic watch."]);
    $stmtReview->execute([19, 4, 5, "Brand new car, loved it."]);
    $stmtReview->execute([21, 5, 5, "Very bright lights."]);
    $stmtReview->execute([24, 6, 4, "Spacious and comfortable."]);

    // Favorites
    $stmtFav = $pdo->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)");
    $stmtFav->execute([2, 5]);
    $stmtFav->execute([3, 7]);

    echo "Seed data (users, items, bookings, reviews, favorites) populated successfully! <br>";
    echo "<b>Admin Details:</b> admin@rently.test / admin123 <br>";
    echo "<b>User Details:</b> amir@rently.test / password123 <br>";
    echo "<a href='index.php'>Go to Home</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
