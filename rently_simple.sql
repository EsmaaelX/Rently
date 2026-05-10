-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 10, 2026 at 07:13 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rently_simple`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `user_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending_admin','pending_owner','approved','rejected','waitlist') COLLATE utf8mb4_unicode_ci DEFAULT 'pending_admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `listing_id`, `user_id`, `start_date`, `end_date`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 3, '2026-04-13', '2026-04-15', 90.00, 'approved', '2026-04-12 12:42:50'),
(2, 2, 2, '2026-04-17', '2026-04-19', 240.00, 'pending_admin', '2026-04-12 12:42:50'),
(3, 8, 2, '2026-04-22', '2026-04-30', 480.00, 'approved', '2026-04-19 11:37:41'),
(4, 4, 3, '2026-05-12', '2026-05-22', 850.00, 'rejected', '2026-04-19 11:51:06'),
(6, 4, 2, '2026-05-03', '2026-05-07', 340.00, 'pending_owner', '2026-04-19 12:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `listing_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_listing` (`user_id`,`listing_id`),
  KEY `listing_id` (`listing_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `listing_id`, `created_at`) VALUES
(1, 2, 5, '2026-04-12 12:42:50'),
(2, 3, 7, '2026-04-12 12:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

DROP TABLE IF EXISTS `listings`;
CREATE TABLE IF NOT EXISTS `listings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `price_type` enum('day','hour') COLLATE utf8mb4_unicode_ci DEFAULT 'day',
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` json DEFAULT NULL,
  `status` enum('pending','approved') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`id`, `user_id`, `title`, `description`, `category`, `price`, `price_type`, `city`, `image`, `attributes`, `status`, `created_at`) VALUES
(1, 2, '2022 Honda Civic', 'A reliable and comfortable car for city trips. Excellent gas mileage.', 'Cars', 45.00, 'day', 'Tel Aviv', 'https://images.pexels.com/photos/1149137/pexels-photo-1149137.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Honda\", \"year\": \"2022\", \"seats\": 5}', 'approved', '2026-04-12 12:42:50'),
(2, 3, 'Cozy Studio Downtown', 'Modern studio apartment right in the heart of the city.', 'Apartments', 120.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/439227/pexels-photo-439227.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 1, \"bathrooms\": 1}', 'approved', '2026-04-12 12:42:50'),
(3, 2, 'Canon EOS R5 Camera', 'Professional camera for your next photoshoot. Comes with 2 lenses.', 'Equipment', 15.00, 'hour', 'Haifa', 'https://images.pexels.com/photos/51383/photo-camera-subject-photographer-51383.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-04-12 12:42:50'),
(4, 4, 'Jeep Wrangler 4x4', 'Perfect for off-road adventures!', 'Cars', 85.00, 'day', 'Eilat', 'https://images.pexels.com/photos/119435/pexels-photo-119435.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Jeep\", \"year\": \"2021\", \"seats\": 4}', 'approved', '2026-04-12 12:42:50'),
(5, 3, 'Beachfront Villa', 'Wake up to the sound of waves. 3 bedrooms and private pool.', 'Apartments', 350.00, 'day', 'Netanya', 'https://images.pexels.com/photos/258159/pexels-photo-258159.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 3, \"bathrooms\": 2}', 'approved', '2026-04-12 12:42:50'),
(6, 4, 'Electric Scooter Pro', 'Fast and fun way to get around the city.', 'Equipment', 5.00, 'hour', 'Tel Aviv', 'https://images.pexels.com/photos/3671151/pexels-photo-3671151.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-04-12 12:42:50'),
(7, 2, 'Tesla Model 3', 'Experience the future of driving with this fully electric sleek car.', 'Cars', 95.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/11194510/pexels-photo-11194510.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Tesla\", \"year\": \"2023\", \"seats\": 5}', 'approved', '2026-04-12 12:42:50'),
(8, 4, 'High-End Gaming PC', 'Rent a massive rig for the weekend! RTX 4090 included.', 'Electronics', 60.00, 'day', 'Eilat', 'https://images.pexels.com/photos/777001/pexels-photo-777001.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-04-12 12:42:50'),
(9, 2, 'Indoor Basketball Court', 'Full sized pristine indoor court. 2 hour slots only.', 'Sports field', 25.00, 'hour', 'Tel Aviv', 'https://images.pexels.com/photos/358042/pexels-photo-358042.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-04-12 12:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '#',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'Admin replied to your support ticket #1', 'view_ticket.php?id=1', 1, '2026-04-12 13:00:49'),
(2, 1, 'User replied to support ticket #1', 'view_ticket.php?id=1', 1, '2026-04-12 13:02:21'),
(3, 4, 'A new booking for your listing \"High-End Gaming PC\" was submitted and is pending admin approval.', 'profile.php', 0, '2026-04-19 11:37:42'),
(4, 4, 'Admin approved the booking request for your listing \"High-End Gaming PC\". It is now awaiting your final approval.', 'profile.php', 1, '2026-04-19 11:38:37'),
(5, 2, 'Your booking for \"High-End Gaming PC\" was approved!', 'profile.php', 0, '2026-04-19 11:39:15'),
(6, 4, 'A new booking for your listing \"Jeep Wrangler 4x4\" was submitted and is pending admin approval.', 'profile.php', 0, '2026-04-19 11:51:06'),
(7, 3, 'Your booking request for \"Jeep Wrangler 4x4\" was rejected or cancelled by the Admin.', 'profile.php', 0, '2026-04-19 11:52:07'),
(8, 4, 'A new booking for your listing \"Jeep Wrangler 4x4\" was submitted and is pending admin approval.', 'profile.php', 0, '2026-04-19 11:53:21'),
(9, 2, 'You have joined the waitlist for \"Jeep Wrangler 4x4\". If the dates become available, your request will be promoted automatically.', 'profile.php', 0, '2026-04-19 12:12:38'),
(10, 2, 'Great news! Your waitlisted booking is now pending admin approval.', 'profile.php', 0, '2026-04-19 12:13:24'),
(11, 1, 'A waitlisted booking for \"Jeep Wrangler 4x4\" was automatically promoted.', 'admin.php?tab=bookings', 0, '2026-04-19 12:13:24'),
(12, 4, 'Admin approved the booking request for your listing \"Jeep Wrangler 4x4\". It is now awaiting your final approval.', 'profile.php', 0, '2026-04-19 12:14:50'),
(13, 4, 'Your listing \'Jeep Wrangler 4x4\' has been reported.', 'view_listing.php?id=4', 0, '2026-05-03 10:11:38'),
(14, 1, 'Listing \'Jeep Wrangler 4x4\' has been reported.', 'view_listing.php?id=4', 0, '2026-05-03 10:11:38'),
(15, 4, 'Your listing \'Jeep Wrangler 4x4\' has been reported.', 'view_listing.php?id=4', 0, '2026-05-03 10:11:43'),
(16, 1, 'Listing \'Jeep Wrangler 4x4\' has been reported.', 'view_listing.php?id=4', 0, '2026-05-03 10:11:43'),
(17, 1, 'New appeal ticket #2 opened for listing #4.', 'view_ticket.php?id=2', 0, '2026-05-04 05:55:38'),
(18, 1, 'Report Appeal: The owner of \'Jeep Wrangler 4x4\' has appealed the reports against their listing.', 'view_ticket.php?id=3', 0, '2026-05-04 06:04:48'),
(19, 4, 'Admin has reviewed and deleted the reports on your listing \'Jeep Wrangler 4x4\'.', 'view_listing.php?id=4', 0, '2026-05-04 06:05:55');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','reviewed','dismissed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `user_id` (`user_id`)
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `listing_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 3, 5, 'Amazing car! Super clean and ran perfectly.', '2026-04-12 12:42:50'),
(2, 1, 4, 4, 'Great car, pickup was a bit difficult though.', '2026-04-12 12:42:50'),
(3, 2, 4, 5, 'Absolutely loved the studio! Very highly recommended.', '2026-04-12 12:42:50'),
(4, 5, 2, 5, 'The view is unbelievable. Will rent again next summer.', '2026-04-12 12:42:50'),
(5, 7, 3, 5, 'Driving a Tesla is an experience. Owner was very nice!', '2026-04-12 12:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `subject`, `status`, `created_at`) VALUES
(1, 2, 'scam', 'open', '2026-04-12 12:50:05'),
(2, 4, 'Appeal for Report on Listing #4', 'open', '2026-05-04 05:55:37'),
(3, 4, 'Appeal for Report on Listing #4', 'open', '2026-05-04 06:04:48');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `sender_id`, `message`, `created_at`) VALUES
(1, 1, 2, 'i want refund', '2026-04-12 12:50:05'),
(2, 1, 2, 'please', '2026-04-12 12:50:16'),
(3, 1, 1, 'how i  can help you?', '2026-04-12 13:00:49'),
(4, 1, 2, 'i want refund', '2026-04-12 13:02:21'),
(5, 2, 4, 'Listing: Jeep Wrangler 4x4\nAppeal Reason: i fix it', '2026-05-04 05:55:38'),
(6, 3, 4, 'Listing: Jeep Wrangler 4x4\nAppeal Reason: i fix it', '2026-05-04 06:04:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'assets/img/default_avatar.png',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `is_blocked` tinyint(1) DEFAULT '0',
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `verified` tinyint(1) DEFAULT '0',
  `verification_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `profile_picture`, `bio`, `is_blocked`, `role`, `verified`, `verification_code`, `created_at`) VALUES
(1, 'Admin', 'admin@rently.test', '$2y$10$Zcoogy2Uz2Hq6KZs6XyGqOBpjw2mDoUAdy/DHX0K864xez3eCZg0S', '000000000', 'assets/img/default_avatar.png', 'System Administrator.', 0, 'admin', 1, NULL, '2026-04-12 12:42:50'),
(2, 'John Doe', 'john@rently.test', '$2y$10$exlsC8A72JX53kTZB6X3x.COrzvKl0YtHD6JJjDiOE7vcgazaSeim', '123456789', 'uploads/avatars/1777800590_user_profile.jpg', 'I love renting cars.', 0, 'user', 1, NULL, '2026-04-12 12:42:50'),
(3, 'Jane Smith', 'jane@rently.test', '$2y$10$exlsC8A72JX53kTZB6X3x.COrzvKl0YtHD6JJjDiOE7vcgazaSeim', '987654321', 'uploads/avatars/1777801068_female_user.webp', 'Apartment owner in SF.', 0, 'user', 1, NULL, '2026-04-12 12:42:50'),
(4, 'Mike Ross', 'mike@rently.test', '$2y$10$exlsC8A72JX53kTZB6X3x.COrzvKl0YtHD6JJjDiOE7vcgazaSeim', '111222333', 'uploads/avatars/1777801155_user_avatar.png', 'Tech enthusiast and gadget renter.', 0, 'user', 1, NULL, '2026-04-12 12:42:50');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
