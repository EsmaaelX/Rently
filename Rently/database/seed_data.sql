-- ============================================
-- Rently — Sample / Seed Data
-- Run AFTER rently_db.sql has been imported
-- ============================================
USE rently;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE wishlists;
TRUNCATE TABLE reviews;
TRUNCATE TABLE bookings;
TRUNCATE TABLE asset_images;
TRUNCATE TABLE assets;
TRUNCATE TABLE notifications;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Users  (password for all: "password")
-- Hash = password_hash('password', PASSWORD_DEFAULT)
-- dev_password matches the plain text password
-- ============================================
INSERT INTO users (user_id, full_name, email, password_hash, dev_password, phone_number, role, is_verified, bio) VALUES
(1, 'Admin User',    'admin@rently.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0500000000', 'admin',  1, 'Platform administrator.'),
(2, 'Sarah Cohen',   'sarah@rently.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0501111111', 'owner',  1, 'Property owner in Tel Aviv with 5+ years of hosting experience. I love making guests feel at home!'),
(3, 'David Levi',    'david@rently.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0502222222', 'owner',  1, 'Adventure enthusiast and vehicle rental specialist. From city cars to desert jeeps, I have it all!'),
(4, 'Maya Shapira',  'maya@rently.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0503333333', 'renter', 1, 'Digital nomad who loves exploring new cities. Always looking for unique places to stay and things to rent.'),
(5, 'Tom Barker',    'tom@rently.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0504444444', 'renter', 1, 'Sports lover and weekend traveler. Basketball, soccer, yoga — you name it, I play it!'),
(6, 'Noa Mizrahi',   'noa@rently.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0505555555', 'owner',  1, 'Haifa-based entrepreneur renting out premium properties and creative spaces.'),
(7, 'Alex Kovac',    'alex@rently.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0506666666', 'renter', 1, 'Freelance photographer constantly needing new lenses and studio space.'),
(8, 'Yael Bar',      'yael@rently.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0507777777', 'renter', 1, 'Music producer looking for ad-hoc recording studios and equipment.'),
(9, 'Ronit Golan',   'ronit@rently.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0508888888', 'owner',  1, 'Event organizer renting out high-end audio and visual gear.'),
(10, 'Omer Perez',   'omer@rently.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', '0509999999', 'renter', 1, 'Enjoys driving cool cars on the weekend and finding cozy spots in Jerusalem.');

-- ============================================
-- Assets (owner_id references users above)
-- Owners: 2 (Sarah), 3 (David), 6 (Noa), 9 (Ronit)
-- ============================================
INSERT INTO assets (asset_id, owner_id, title, description, category, address, city, latitude, longitude, price_per_hour, price_per_day, image_url, status, is_approved, extra_fields) VALUES
-- Apartments
(1, 2, 'Cozy Studio in Downtown', 'Modern studio apartment with full kitchen, fast WiFi, and city views. Perfect for solo travelers or couples.', 'apartment', '14 Rothschild Blvd, Tel Aviv', 'Tel Aviv', 32.0636, 34.7736, 0.00, 120.00, 'https://loremflickr.com/600/400/apartment,interior?lock=1', 'active', 1, '{"rooms": 1, "size_sqm": 35, "floor": 3, "amenities": ["WiFi", "AC", "Kitchen", "Washer"]}'),
(2, 3, 'Seaside Penthouse Suite', 'Luxurious 3-bedroom penthouse with panoramic sea views, private balcony, and rooftop pool access. Sleeps up to 6 guests comfortably.', 'apartment', '88 HaYarkon St, Tel Aviv', 'Tel Aviv', 32.0804, 34.7677, 0.00, 350.00, 'https://loremflickr.com/600/400/penthouse,luxury?lock=2', 'active', 1, '{"rooms": 3, "size_sqm": 120, "floor": 12, "amenities": ["WiFi", "AC", "Kitchen", "Pool", "Gym", "Parking"]}'),
(3, 6, 'Charming Old City Loft', 'Beautifully restored loft in the historic quarter. Stone walls, high ceilings, and authentic Middle Eastern decor.', 'apartment', '22 David St, Jerusalem', 'Jerusalem', 31.7767, 35.2345, 0.00, 180.00, 'https://loremflickr.com/600/400/loft,architecture?lock=3', 'active', 1, '{"rooms": 2, "size_sqm": 75, "floor": 2, "amenities": ["WiFi", "AC", "Kitchen", "Balcony"]}'),
(4, 9, 'Eilat Desert View Villa', 'Spacious 4-bedroom villa with a private pool overlooking the stunning desert mountains of Eilat.', 'apartment', '4 Coral St, Eilat', 'Eilat', 29.5581, 34.9482, 0.00, 450.00, 'https://loremflickr.com/600/400/villa,pool?lock=4', 'active', 1, '{"rooms": 4, "size_sqm": 250, "floor": 1, "amenities": ["WiFi", "Pool", "BBQ", "Parking"]}'),

-- Cars
(5, 2, 'Tesla Model 3 - White', '2024 Tesla Model 3 Long Range in pearl white. Autopilot included, full charge covers 350+ miles. Pickup from central Tel Aviv.', 'car', 'Dizengoff Center Parking, Tel Aviv', 'Tel Aviv', 32.0753, 34.7748, 25.00, 95.00, 'https://loremflickr.com/600/400/tesla,car?lock=5', 'active', 1, '{"year": 2024, "make": "Tesla", "model": "Model 3", "fuel": "Electric", "seats": 5, "transmission": "Automatic"}'),
(6, 3, 'Jeep Wrangler - Desert Ready', 'Rugged 4x4 Jeep Wrangler, perfect for trips and off-road adventures. Comes with GPS, cooler, and camping gear.', 'car', '5 HaMasger St, Beer Sheva', 'Beer Sheva', 31.2530, 34.7915, 30.00, 150.00, 'https://loremflickr.com/600/400/jeep,offroad?lock=6', 'active', 1, '{"year": 2023, "make": "Jeep", "model": "Wrangler", "fuel": "Gasoline", "seats": 5, "transmission": "Manual"}'),
(7, 6, 'BMW 3-Series Convertible', 'Sleek BMW 320i convertible in metallic blue. Leather seats, premium sound, and retractable hardtop.', 'car', '33 Hanassi Ave, Haifa', 'Haifa', 32.8021, 34.9871, 35.00, 130.00, 'https://loremflickr.com/600/400/bmw,car?lock=7', 'active', 1, '{"year": 2024, "make": "BMW", "model": "320i", "fuel": "Gasoline", "seats": 4, "transmission": "Automatic"}'),
(8, 9, 'Ford Transit Van', 'Large moving van. Perfect for relocation or moving large equipment. Automatic, AC, very clean.', 'car', '10 Industrial Zone, Petah Tikva', 'Petah Tikva', 32.0850, 34.8870, 20.00, 80.00, 'https://loremflickr.com/600/400/van,truck?lock=8', 'active', 1, '{"year": 2019, "make": "Ford", "model": "Transit", "fuel": "Diesel", "seats": 3, "transmission": "Automatic"}'),

-- Sport Venues
(9, 2, 'Indoor Basketball Court', 'Full-size indoor basketball court with sprung hardwood floor, electronic scoreboard, and bleacher seating.', 'sport_venue', '10 Sportek, Tel Aviv', 'Tel Aviv', 32.0980, 34.7870, 75.00, 0.00, 'https://loremflickr.com/600/400/basketball,court?lock=9', 'active', 1, '{"sport_type": "Basketball", "indoor": true, "capacity": 50, "has_lights": true}'),
(10, 3, 'Professional Soccer Field', 'FIFA-standard artificial turf soccer field with floodlights, player benches, and changing rooms.', 'sport_venue', '3 Wingate Institute, Netanya', 'Netanya', 32.2713, 34.8516, 120.00, 0.00, 'https://loremflickr.com/600/400/soccer,stadium?lock=10', 'active', 1, '{"sport_type": "Soccer", "indoor": false, "capacity": 200, "has_lights": true}'),
(11, 6, 'Yoga & Dance Studio', 'Beautiful 80sqm studio with mirrored walls, bamboo flooring, sound system, and natural lighting.', 'sport_venue', '45 Ben Yehuda St, Tel Aviv', 'Tel Aviv', 32.0789, 34.7701, 45.00, 0.00, 'https://loremflickr.com/600/400/yoga,studio?lock=11', 'active', 1, '{"sport_type": "Yoga", "indoor": true, "capacity": 20, "has_lights": true}'),

-- Equipment
(12, 2, 'Canon R5 Camera Kit', 'Canon EOS R5 mirrorless camera with 24-70mm lens, tripod, extra batteries, and carrying case.', 'equipment', 'Dizengoff Center, Tel Aviv', 'Tel Aviv', 32.0753, 34.7748, 15.00, 85.00, 'https://loremflickr.com/600/400/camera,canon?lock=12', 'active', 1, '{"type": "Camera", "brand": "Canon", "condition": "Excellent"}'),
(13, 3, 'DJ Equipment Set', 'Complete DJ setup: Pioneer DDJ-1000, two QSC K12.2 speakers, subwoofer, cables, and lighting rig.', 'equipment', '5 HaMasger St, Beer Sheva', 'Beer Sheva', 31.2530, 34.7915, 25.00, 180.00, 'https://loremflickr.com/600/400/dj,equipment?lock=13', 'active', 1, '{"type": "Audio", "brand": "Pioneer", "condition": "Good"}'),
(14, 9, 'DJI Mavic 3 Drone', 'Professional drone with 4K recording capabilities and 3 extra batteries.', 'equipment', '9 Herzl St, Rishon LeZion', 'Rishon LeZion', 31.9702, 34.7925, 30.00, 100.00, 'https://loremflickr.com/600/400/drone,dji?lock=14', 'active', 1, '{"type": "Drone", "brand": "DJI", "condition": "Like New"}'),

-- Studio
(15, 6, 'Photography Studio with Lighting', 'Professional 60sqm photography studio with backdrop system, 3-point lighting, reflectors, and makeup area.', 'studio', '12 Nahalat Binyamin, Tel Aviv', 'Tel Aviv', 32.0645, 34.7710, 60.00, 400.00, 'https://loremflickr.com/600/400/photography,studio?lock=15', 'active', 1, '{"studio_type": "Photography", "size_sqm": 60, "has_equipment": true}'),
(16, 9, 'Music Recording Studio', 'Acoustically treated tracking room and control room. Includes Neumann mics and an SSL console.', 'studio', 'Ramat Gan Diamond Exchange', 'Ramat Gan', 32.0833, 34.8000, 100.00, 800.00, 'https://loremflickr.com/600/400/recording,studio?lock=16', 'active', 1, '{"studio_type": "Recording", "size_sqm": 40, "has_equipment": true}'),

-- Parking
(17, 2, 'Secure Underground Parking - Rothschild', 'Secure underground parking spot in the heart of Rothschild Boulevard. 24/7 access, CCTV monitored.', 'parking', '14 Rothschild Blvd Parking, Tel Aviv', 'Tel Aviv', 32.0636, 34.7736, 5.00, 35.00, 'https://loremflickr.com/600/400/parking,garage?lock=17', 'active', 1, '{"covered": true, "ev_charging": false, "size": "Standard"}'),
(18, 3, 'Driveway Parking next to Train', 'Open driveway parking spot. Extremely close to the train station.', 'parking', '21 Station St, Haifa', 'Haifa', 32.8211, 34.9810, 2.00, 15.00, 'https://loremflickr.com/600/400/parking,driveway?lock=18', 'active', 1, '{"covered": false, "ev_charging": false, "size": "Large"}');

-- ============================================
-- Bookings
-- ============================================
INSERT INTO bookings (booking_id, user_id, asset_id, start_time, end_time, total_price, status) VALUES
(1, 4, 1, '2026-03-10 14:00:00', '2026-03-13 11:00:00', 360.00, 'confirmed'),
(2, 5, 4, '2026-03-08 09:00:00', '2026-03-10 09:00:00', 900.00, 'confirmed'),
(3, 7, 7, '2026-03-15 18:00:00', '2026-03-15 20:00:00', 70.00, 'confirmed'),
(4, 5, 2, '2026-03-20 15:00:00', '2026-03-25 11:00:00', 1750.00, 'pending'),
(5, 8, 8, '2026-03-18 16:00:00', '2026-03-18 19:00:00', 60.00, 'confirmed'),
(6, 4, 5, '2026-03-12 08:00:00', '2026-03-16 08:00:00', 380.00, 'confirmed'),
(7, 10, 3, '2026-04-01 14:00:00', '2026-04-03 11:00:00', 360.00, 'pending'),
(8, 7, 12, '2026-03-22 10:00:00', '2026-03-23 11:00:00', 85.00, 'confirmed'),
(9, 4, 6, '2026-03-05 09:00:00', '2026-03-07 09:00:00', 300.00, 'cancelled'),
(10, 10, 15, '2026-04-10 10:00:00', '2026-04-10 16:00:00', 360.00, 'confirmed'),
(11, 8, 16, '2026-04-15 09:00:00', '2026-04-16 09:00:00', 800.00, 'pending');

-- ============================================
-- Reviews
-- ============================================
INSERT INTO reviews (user_id, asset_id, rating, comment) VALUES
(4, 1, 5, 'Amazing studio! Extremely clean, the wifi was fast and it felt like home.'),
(5, 4, 4, 'Very spacious and the pool was great, but took a while to heat up in the evening.'),
(7, 7, 5, 'The BMW was a blast to drive along the coast. Easy pickup and drop off.'),
(4, 5, 5, 'Tesla was fully charged and clean. Owner was very polite and explained everything clearly.'),
(7, 12, 5, 'Camera worked absolutely perfectly. Lenses were scratch-free. Will rent again next week.'),
(10, 15, 4, 'Great space with excellent natural light. A bit hard to find parking outside though.');

-- ============================================
-- Wishlists
-- ============================================
INSERT INTO wishlists (user_id, asset_id) VALUES
(4, 2), (4, 4), (4, 14),
(5, 1), (5, 6), (5, 9),
(7, 12), (7, 13), (7, 15),
(8, 16), (8, 3),
(10, 5), (10, 7), (10, 11);
