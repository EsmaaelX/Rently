-- ============================================
-- Rently — Sample / Seed Data
-- Run AFTER rently_db.sql has been imported
-- ============================================
USE rently;

-- ============================================
-- Users  (password for all: "password")
-- Hash = password_hash('password', PASSWORD_DEFAULT)
-- ============================================
INSERT INTO users (full_name, email, password_hash, phone_number, role) VALUES
('Sarah Cohen',   'sarah@rently.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0501111111', 'owner'),
('David Levi',    'david@rently.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0502222222', 'owner'),
('Maya Shapira',  'maya@rently.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0503333333', 'renter'),
('Tom Barker',    'tom@rently.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0504444444', 'renter'),
('Noa Mizrahi',   'noa@rently.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0505555555', 'owner');

-- ============================================
-- Assets (owner_id references users above)
-- Sarah (id=2), David (id=3), Noa (id=6)
-- ============================================
INSERT INTO assets (owner_id, title, description, category, address, latitude, longitude, price_per_hour, price_per_day, image_url, status) VALUES
-- Apartments
(2, 'Cozy Studio in Downtown',
    'Modern studio apartment with full kitchen, fast WiFi, and city views. Perfect for solo travelers or couples. Located steps away from restaurants and nightlife.',
    'apartment', '14 Rothschild Blvd, Tel Aviv', 32.0636, 34.7736, 0.00, 120.00, NULL, 'active'),
(3, 'Seaside Penthouse Suite',
    'Luxurious 3-bedroom penthouse with panoramic sea views, private balcony, and rooftop access. Sleeps up to 6 guests comfortably.',
    'apartment', '88 HaYarkon St, Tel Aviv', 32.0804, 34.7677, 0.00, 350.00, NULL, 'active'),
(6, 'Charming Old City Loft',
    'Beautifully restored loft in the historic quarter. Stone walls, high ceilings, and authentic Middle Eastern decor. Walking distance to all major sites.',
    'apartment', '22 David St, Jerusalem', 31.7767, 35.2345, 0.00, 180.00, NULL, 'active'),

-- Cars
(2, 'Tesla Model 3 - White',
    '2024 Tesla Model 3 Long Range in pearl white. Autopilot included, full charge covers 350+ miles. Pickup from central Tel Aviv.',
    'car', 'Dizengoff Center Parking, Tel Aviv', 32.0753, 34.7748, 0.00, 95.00, NULL, 'active'),
(3, 'Jeep Wrangler - Desert Ready',
    'Rugged 4x4 Jeep Wrangler, perfect for desert trips and off-road adventures. Comes with GPS, cooler, and camping gear.',
    'car', '5 HaMasger St, Beer Sheva', 31.2530, 34.7915, 0.00, 150.00, NULL, 'active'),
(6, 'BMW 3-Series Convertible',
    'Sleek BMW 320i convertible in metallic blue. Leather seats, premium sound, and retractable hardtop. Great for coastal drives.',
    'car', '33 Hanassi Ave, Haifa', 32.8021, 34.9871, 0.00, 130.00, NULL, 'active'),

-- Sport Venues
(2, 'Indoor Basketball Court - Premium',
    'Full-size indoor basketball court with sprung hardwood floor, electronic scoreboard, bleacher seating for 50, and locker rooms. Climate controlled.',
    'sport_venue', '10 Sportek, Tel Aviv', 32.0980, 34.7870, 75.00, 0.00, NULL, 'active'),
(3, 'Professional Soccer Field',
    'FIFA-standard artificial turf soccer field with floodlights, player benches, and changing rooms. Ideal for leagues, training sessions, or casual 5-a-side games.',
    'sport_venue', '3 Wingate Institute, Netanya', 32.2713, 34.8516, 120.00, 0.00, NULL, 'active'),
(6, 'Yoga & Dance Studio',
    'Beautiful 80sqm studio with mirrored walls, bamboo flooring, sound system, and natural lighting. Perfect for yoga classes, dance rehearsals, or Pilates.',
    'sport_venue', '45 Ben Yehuda St, Tel Aviv', 32.0789, 34.7701, 45.00, 0.00, NULL, 'active');

-- ============================================
-- Bookings
-- asset_id 1-9 match the assets above
-- ============================================
INSERT INTO bookings (user_id, asset_id, start_time, end_time, total_price, status) VALUES
-- Maya (id=4) books the Downtown Studio for 3 days
(4, 1, '2026-03-10 14:00:00', '2026-03-13 11:00:00', 360.00, 'confirmed'),
-- Tom (id=5) books the Tesla for 2 days
(5, 4, '2026-03-08 09:00:00', '2026-03-10 09:00:00', 190.00, 'confirmed'),
-- Maya books the Basketball Court for 2 hours
(4, 7, '2026-03-15 18:00:00', '2026-03-15 20:00:00', 150.00, 'confirmed'),
-- Tom books the Seaside Penthouse for 5 days
(5, 2, '2026-03-20 15:00:00', '2026-03-25 11:00:00', 1750.00, 'pending'),
-- Maya books the Soccer Field for 3 hours
(4, 8, '2026-03-18 16:00:00', '2026-03-18 19:00:00', 360.00, 'confirmed'),
-- Tom books the Jeep for 4 days
(5, 5, '2026-03-12 08:00:00', '2026-03-16 08:00:00', 600.00, 'confirmed'),
-- Maya books the Old City Loft for 2 days
(4, 3, '2026-04-01 14:00:00', '2026-04-03 11:00:00', 360.00, 'pending'),
-- Tom books the Yoga Studio for 1 hour
(5, 9, '2026-03-22 10:00:00', '2026-03-22 11:00:00', 45.00, 'confirmed'),
-- A cancelled booking (BMW, by Maya)
(4, 6, '2026-03-05 09:00:00', '2026-03-07 09:00:00', 260.00, 'cancelled');

-- ============================================
-- Reviews
-- ============================================
INSERT INTO reviews (user_id, asset_id, rating, comment) VALUES
(4, 1, 5, 'Amazing studio! Super clean, great location, and Sarah was an incredible host. Will definitely book again.'),
(5, 4, 4, 'The Tesla was in perfect condition. Smooth ride and easy pickup. Only minor issue was finding the charger nearby.'),
(4, 7, 5, 'Best basketball court in Tel Aviv! Great floor, clean facilities. We had an awesome game night.'),
(5, 2, 5, 'Absolutely stunning penthouse. The sea view at sunset is unforgettable. Worth every penny.'),
(4, 8, 4, 'Nice soccer field with good turf. Floodlights worked great for our evening game. Changing rooms could be cleaner.'),
(5, 5, 5, 'The Jeep was a beast! Took it to the Negev for a weekend and it handled everything perfectly. David was super helpful.'),
(4, 9, 5, 'Lovely studio space. The mirrors and natural light make it perfect for yoga. Very peaceful atmosphere.'),
(5, 3, 4, 'The Old City Loft has so much character. The stone walls and decor are beautiful. Slightly noisy at night from nearby bars.');

-- ============================================
-- Payments (matching confirmed bookings)
-- ============================================
INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, status) VALUES
(1, 4, 360.00,  'credit_card', 'txn_mock_a1b2c3d4e5f60001', 'paid'),
(2, 5, 190.00,  'credit_card', 'txn_mock_a1b2c3d4e5f60002', 'paid'),
(3, 4, 150.00,  'credit_card', 'txn_mock_a1b2c3d4e5f60003', 'paid'),
(4, 5, 1750.00, 'credit_card', 'txn_mock_a1b2c3d4e5f60004', 'held'),
(5, 4, 360.00,  'credit_card', 'txn_mock_a1b2c3d4e5f60005', 'paid'),
(6, 5, 600.00,  'credit_card', 'txn_mock_a1b2c3d4e5f60006', 'paid'),
(7, 4, 360.00,  'credit_card', 'txn_mock_a1b2c3d4e5f60007', 'held'),
(8, 5, 45.00,   'credit_card', 'txn_mock_a1b2c3d4e5f60008', 'paid'),
(9, 4, 260.00,  'credit_card', 'txn_mock_a1b2c3d4e5f60009', 'refunded');
