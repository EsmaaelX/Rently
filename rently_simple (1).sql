-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 24, 2026 at 05:19 PM
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
CREATE DATABASE IF NOT EXISTS `rently_simple` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rently_simple`;

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
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `listing_id`, `user_id`, `start_date`, `end_date`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 3, '2026-05-25', '2026-05-27', 90.00, 'approved', '2026-05-24 17:13:40'),
(2, 2, 2, '2026-05-29', '2026-05-31', 240.00, 'pending_admin', '2026-05-24 17:13:40'),
(3, 3, 4, '2026-05-26', '2026-05-26', 30.00, 'approved', '2026-05-24 17:13:40'),
(4, 4, 5, '2026-06-03', '2026-06-05', 170.00, 'pending_owner', '2026-05-24 17:13:40'),
(5, 5, 6, '2026-05-28', '2026-05-30', 700.00, 'approved', '2026-05-24 17:13:40'),
(6, 7, 8, '2026-05-25', '2026-05-29', 380.00, 'rejected', '2026-05-24 17:13:40'),
(7, 10, 9, '2026-05-26', '2026-05-28', 100.00, 'waitlist', '2026-05-24 17:13:40'),
(8, 11, 10, '2026-05-31', '2026-06-07', 1750.00, 'approved', '2026-05-24 17:13:40'),
(9, 15, 12, '2026-05-25', '2026-05-27', 1200.00, 'pending_owner', '2026-05-24 17:13:40'),
(10, 16, 13, '2026-05-27', '2026-05-27', 20.00, 'approved', '2026-05-24 17:13:40'),
(11, 22, 14, '2026-05-29', '2026-05-30', 120.00, 'pending_admin', '2026-05-24 17:13:40'),
(12, 26, 15, '2026-06-03', '2026-06-08', 100.00, 'waitlist', '2026-05-24 17:13:40'),
(13, 1, 4, '2026-05-14', '2026-05-16', 90.00, 'approved', '2026-05-24 17:13:40'),
(14, 1, 5, '2026-05-19', '2026-05-22', 135.00, 'approved', '2026-05-24 17:13:40'),
(15, 2, 6, '2026-05-09', '2026-05-14', 600.00, 'approved', '2026-05-24 17:13:40'),
(16, 3, 7, '2026-05-21', '2026-05-23', 30.00, 'approved', '2026-05-24 17:13:40'),
(17, 4, 8, '2026-05-25', '2026-05-27', 255.00, 'approved', '2026-05-24 17:13:40'),
(18, 5, 9, '2026-05-04', '2026-05-06', 700.00, 'approved', '2026-05-24 17:13:40'),
(19, 5, 10, '2026-05-14', '2026-05-19', 1750.00, 'approved', '2026-05-24 17:13:40'),
(20, 8, 11, '2026-05-19', '2026-05-22', 180.00, 'approved', '2026-05-24 17:13:40'),
(21, 8, 12, '2026-05-29', '2026-06-01', 180.00, 'pending_admin', '2026-05-24 17:13:40'),
(22, 11, 13, '2026-04-24', '2026-04-29', 1250.00, 'approved', '2026-05-24 17:13:40'),
(23, 11, 14, '2026-05-09', '2026-05-14', 1250.00, 'approved', '2026-05-24 17:13:40'),
(24, 12, 15, '2026-05-22', '2026-05-26', 120.00, 'approved', '2026-05-24 17:13:40'),
(25, 14, 16, '2026-05-23', '2026-05-29', 450.00, 'approved', '2026-05-24 17:13:40'),
(26, 15, 17, '2026-05-14', '2026-05-16', 1200.00, 'approved', '2026-05-24 17:13:40'),
(27, 15, 18, '2026-05-19', '2026-05-21', 1200.00, 'rejected', '2026-05-24 17:13:40'),
(28, 18, 2, '2026-05-17', '2026-05-18', 100.00, 'approved', '2026-05-24 17:13:40'),
(29, 18, 3, '2026-05-22', '2026-05-23', 100.00, 'approved', '2026-05-24 17:13:40'),
(30, 19, 4, '2026-05-12', '2026-05-16', 360.00, 'approved', '2026-05-24 17:13:40'),
(31, 21, 5, '2026-05-16', '2026-05-18', 80.00, 'approved', '2026-05-24 17:13:40'),
(32, 24, 6, '2026-05-20', '2026-05-22', 120.00, 'approved', '2026-05-24 17:13:40');

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
(1, 2, 5, '2026-05-24 17:13:40'),
(2, 3, 7, '2026-05-24 17:13:40');

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
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`id`, `user_id`, `title`, `description`, `category`, `price`, `price_type`, `city`, `image`, `attributes`, `status`, `created_at`) VALUES
(1, 2, '2022 Honda Civic', 'A reliable and comfortable car for city trips. Excellent gas mileage.', 'Cars', 45.00, 'day', 'Tel Aviv', 'https://images.pexels.com/photos/1149137/pexels-photo-1149137.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Honda\", \"year\": \"2022\", \"seats\": 5}', 'approved', '2026-05-24 17:13:40'),
(2, 3, 'Cozy Studio Downtown', 'Modern studio apartment right in the heart of the city.', 'Apartments', 120.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/439227/pexels-photo-439227.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 1, \"bathrooms\": 1}', 'approved', '2026-05-24 17:13:40'),
(3, 2, 'Canon EOS R5 Camera', 'Professional camera for your next photoshoot. Comes with 2 lenses.', 'Equipment', 15.00, 'hour', 'Haifa', 'https://images.pexels.com/photos/51383/photo-camera-subject-photographer-51383.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(4, 4, 'Jeep Wrangler 4x4', 'Perfect for off-road adventures!', 'Cars', 85.00, 'day', 'Eilat', 'https://images.pexels.com/photos/119435/pexels-photo-119435.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Jeep\", \"year\": \"2021\", \"seats\": 4}', 'approved', '2026-05-24 17:13:40'),
(5, 3, 'Beachfront Villa', 'Wake up to the sound of waves. 3 bedrooms and private pool.', 'Apartments', 350.00, 'day', 'Netanya', 'https://images.pexels.com/photos/258159/pexels-photo-258159.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 3, \"bathrooms\": 2}', 'approved', '2026-05-24 17:13:40'),
(6, 4, 'Electric Scooter Pro', 'Fast and fun way to get around the city.', 'Equipment', 5.00, 'hour', 'Tel Aviv', 'https://images.pexels.com/photos/3671151/pexels-photo-3671151.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(7, 2, 'Tesla Model 3', 'Experience the future of driving with this fully electric sleek car.', 'Cars', 95.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/11194510/pexels-photo-11194510.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Tesla\", \"year\": \"2023\", \"seats\": 5}', 'approved', '2026-05-24 17:13:40'),
(8, 4, 'High-End Gaming PC', 'Rent a massive rig for the weekend! RTX 4090 included.', 'Electronics', 60.00, 'day', 'Eilat', 'https://images.pexels.com/photos/777001/pexels-photo-777001.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(9, 2, 'Indoor Basketball Court', 'Full sized pristine indoor court. 2 hour slots only.', 'Sports field', 25.00, 'hour', 'Tel Aviv', 'https://images.pexels.com/photos/358042/pexels-photo-358042.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(10, 5, '2023 Toyota Corolla', 'Very reliable car for family trips.', 'Cars', 50.00, 'day', 'Haifa', 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Toyota\", \"year\": \"2023\", \"seats\": 5}', 'approved', '2026-05-24 17:13:40'),
(11, 6, 'Traditional Villa', 'Spacious villa with authentic design.', 'Apartments', 250.00, 'day', 'Nazareth', 'https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 4, \"bathrooms\": 3}', 'approved', '2026-05-24 17:13:40'),
(12, 7, 'DJ Pioneer Setup', 'Full professional DJ setup.', 'Equipment', 30.00, 'hour', 'Ramallah', 'https://images.pexels.com/photos/164745/pexels-photo-164745.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(13, 8, 'Sea View Studio', 'Beautiful studio right next to the sea.', 'Apartments', 150.00, 'day', 'Jaffa', 'https://images.pexels.com/photos/2082087/pexels-photo-2082087.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 1, \"bathrooms\": 1}', 'approved', '2026-05-24 17:13:40'),
(14, 9, '2021 Hyundai Tucson', 'Great SUV for the whole family.', 'Cars', 75.00, 'day', 'Tel Aviv', 'https://images.pexels.com/photos/112460/pexels-photo-112460.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Hyundai\", \"year\": \"2021\", \"seats\": 5}', 'approved', '2026-05-24 17:13:40'),
(15, 10, 'Luxury Penthouse', 'High-end penthouse with a pool.', 'Apartments', 600.00, 'day', 'Herzliya', 'https://images.pexels.com/photos/3150591/pexels-photo-3150591.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 5, \"bathrooms\": 4}', 'approved', '2026-05-24 17:13:40'),
(16, 11, 'E-Bike Commuter', 'Fast electric bike for city transport.', 'Equipment', 10.00, 'hour', 'Jerusalem', 'assets/img/ebike.png', NULL, 'approved', '2026-05-24 17:13:40'),
(17, 12, 'Mountain Bike', 'Professional mountain bike.', 'Equipment', 25.00, 'day', 'Eilat', 'https://images.pexels.com/photos/100582/pexels-photo-100582.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(18, 5, 'Vintage Rolex Watch', 'Beautiful vintage watch for special events.', 'Equipment', 100.00, 'day', 'Haifa', 'https://images.pexels.com/photos/277390/pexels-photo-277390.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(19, 10, '2024 Kia Sportage', 'Brand new SUV.', 'Cars', 90.00, 'day', 'Herzliya', 'https://images.pexels.com/photos/116675/pexels-photo-116675.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Kia\", \"year\": \"2024\", \"seats\": 5}', 'approved', '2026-05-24 17:13:40'),
(20, 13, 'GoPro Hero 11', 'Capture your adventures.', 'Equipment', 15.00, 'day', 'Eilat', 'assets/img/gopro.png', NULL, 'approved', '2026-05-24 17:13:40'),
(21, 14, 'Professional Studio Lights', 'Great for photoshoots.', 'Equipment', 40.00, 'day', 'Tel Aviv', 'assets/img/studiolights.png', NULL, 'approved', '2026-05-24 17:13:40'),
(22, 15, 'Mercedes-Benz C-Class', 'Luxury sedan.', 'Cars', 120.00, 'day', 'Haifa', 'https://images.pexels.com/photos/112452/pexels-photo-112452.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"make\": \"Mercedes-Benz\", \"year\": \"2022\", \"seats\": 5}', 'approved', '2026-05-24 17:13:40'),
(23, 16, 'Cozy Room near University', 'Perfect for students or short stays.', 'Apartments', 50.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=600', '{\"rooms\": 1, \"bathrooms\": 1}', 'approved', '2026-05-24 17:13:40'),
(24, 17, 'Spacious Family Car - Mazda 5', '7 seater minivan.', 'Cars', 60.00, 'day', 'Nazareth', 'assets/img/mazda5.png', '{\"make\": \"Mazda\", \"year\": \"2019\", \"seats\": 7}', 'approved', '2026-05-24 17:13:40'),
(25, 18, 'Acoustic Guitar - Yamaha', 'Beautiful sounding acoustic guitar.', 'Equipment', 12.00, 'day', 'Tel Aviv', 'https://images.pexels.com/photos/164861/pexels-photo-164861.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(26, 13, 'Camping Tent 4-Person', 'Waterproof camping tent.', 'Equipment', 20.00, 'day', 'Eilat', 'https://images.pexels.com/photos/2398220/pexels-photo-2398220.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(27, 14, 'Drone DJI Mavic Air 2', '4K drone with extra batteries.', 'Equipment', 70.00, 'day', 'Herzliya', 'https://images.pexels.com/photos/1087180/pexels-photo-1087180.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(28, 15, 'Office Space Desk', 'Quiet desk in a shared office.', 'Apartments', 25.00, 'day', 'Haifa', 'https://images.pexels.com/photos/1170412/pexels-photo-1170412.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40'),
(29, 16, 'Electric Scooter Xiaomi', 'Great for commuting.', 'Equipment', 15.00, 'day', 'Jerusalem', 'https://images.pexels.com/photos/3671151/pexels-photo-3671151.jpeg?auto=compress&cs=tinysrgb&w=600', NULL, 'approved', '2026-05-24 17:13:40');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 1, 3, 5, 'Amazing car! Super clean and ran perfectly.', '2026-05-24 17:13:40'),
(2, 1, 4, 4, 'Great car, pickup was a bit difficult though.', '2026-05-24 17:13:40'),
(3, 1, 5, 5, 'Very smooth ride.', '2026-05-24 17:13:40'),
(4, 2, 4, 5, 'Absolutely loved the studio! Very highly recommended.', '2026-05-24 17:13:40'),
(5, 2, 6, 4, 'Good location, a bit noisy.', '2026-05-24 17:13:40'),
(6, 3, 7, 5, 'Perfect camera for my shoot.', '2026-05-24 17:13:40'),
(7, 4, 8, 5, 'Off-roading was fun.', '2026-05-24 17:13:40'),
(8, 5, 2, 5, 'The view is unbelievable. Will rent again next summer.', '2026-05-24 17:13:40'),
(9, 5, 9, 5, 'Amazing villa.', '2026-05-24 17:13:40'),
(10, 5, 10, 4, 'Great but expensive.', '2026-05-24 17:13:40'),
(11, 7, 3, 5, 'Driving a Tesla is an experience. Owner was very nice!', '2026-05-24 17:13:40'),
(12, 8, 11, 5, 'Played games all weekend at ultra settings.', '2026-05-24 17:13:40'),
(13, 11, 13, 5, 'Beautiful villa in Nazareth.', '2026-05-24 17:13:40'),
(14, 11, 14, 5, 'Highly recommended.', '2026-05-24 17:13:40'),
(15, 12, 15, 4, 'Good DJ set.', '2026-05-24 17:13:40'),
(16, 14, 16, 5, 'Great family car.', '2026-05-24 17:13:40'),
(17, 15, 17, 5, 'Luxury at its best.', '2026-05-24 17:13:40'),
(18, 18, 2, 5, 'Looked great for the wedding.', '2026-05-24 17:13:40'),
(19, 18, 3, 5, 'Classic watch.', '2026-05-24 17:13:40'),
(20, 19, 4, 5, 'Brand new car, loved it.', '2026-05-24 17:13:40'),
(21, 21, 5, 5, 'Very bright lights.', '2026-05-24 17:13:40'),
(22, 24, 6, 4, 'Spacious and comfortable.', '2026-05-24 17:13:40');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `profile_picture`, `bio`, `is_blocked`, `role`, `verified`, `verification_code`, `created_at`) VALUES
(1, 'Admin', 'admin@rently.test', '$2y$10$jd3vT/oJxLw/ffAaveI3FehpSzQKwxgmuFyQDcXIiRf7Khw5a8Hm2', '000000000', 'https://randomuser.me/api/portraits/lego/1.jpg', 'System Administrator.', 0, 'admin', 1, NULL, '2026-05-24 17:13:40'),
(2, 'Amir Goldstein', 'amir@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '123456789', 'https://randomuser.me/api/portraits/men/10.jpg', 'I love renting cars.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(3, 'Nour Al-Fayed', 'nour@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '987654321', 'https://randomuser.me/api/portraits/women/10.jpg', 'Apartment owner in SF.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(4, 'Eitan Cohen', 'eitan@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '111222333', 'https://randomuser.me/api/portraits/men/20.jpg', 'Tech enthusiast and gadget renter.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(5, 'Ahmed Youssef', 'ahmed@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0501234567', 'https://randomuser.me/api/portraits/men/30.jpg', 'Car enthusiast and mechanic.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(6, 'Fatima Ali', 'fatima@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0547654321', 'https://randomuser.me/api/portraits/women/20.jpg', 'Love renting out my beautiful villa.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(7, 'Omar Hassan', 'omar@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0529876543', 'https://randomuser.me/api/portraits/men/40.jpg', 'Professional DJ.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(8, 'Layla Mahmoud', 'layla@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0534567890', 'https://randomuser.me/api/portraits/women/30.jpg', 'Art lover and host in Jaffa.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(9, 'Yossi Cohen', 'yossi@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0501112233', 'https://randomuser.me/api/portraits/men/50.jpg', 'Daily commuter looking to share rides.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(10, 'Noa Levi', 'noa@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0543332211', 'https://randomuser.me/api/portraits/women/40.jpg', 'Real estate investor.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(11, 'Itzhak Mizrahi', 'itzhak@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0524445566', 'https://randomuser.me/api/portraits/men/60.jpg', 'Cycling enthusiastic.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(12, 'Maya Avraham', 'maya@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0536667788', 'https://randomuser.me/api/portraits/women/50.jpg', 'Love nature and hiking.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(13, 'Hassan Nasr', 'hassan@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0541112222', 'https://randomuser.me/api/portraits/men/70.jpg', 'Traveler.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(14, 'Shira Ben-David', 'shira@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0523334444', 'https://randomuser.me/api/portraits/women/60.jpg', 'Photographer.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(15, 'Tariq Mansour', 'tariq@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0505556666', 'https://randomuser.me/api/portraits/men/80.jpg', 'Business owner.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(16, 'Yael Rabin', 'yael@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0537778888', 'https://randomuser.me/api/portraits/women/70.jpg', 'Student.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(17, 'Zainab Qasim', 'zainab@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0549990000', 'https://randomuser.me/api/portraits/women/80.jpg', 'Teacher.', 0, 'user', 1, NULL, '2026-05-24 17:13:40'),
(18, 'Avi Katz', 'avi@rently.test', '$2y$10$6Gbxhs32IWKyZ0a/tcUFZeTwXlCGLy/B7oUiHXmouQKMO0JlSqjJa', '0521239876', 'https://randomuser.me/api/portraits/men/90.jpg', 'Musician.', 0, 'user', 1, NULL, '2026-05-24 17:13:40');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
