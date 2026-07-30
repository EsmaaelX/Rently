-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 30, 2026 at 04:40 PM
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
-- Database: `rently`
--
CREATE DATABASE IF NOT EXISTS `rently` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rently`;

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
  `start_time` time DEFAULT NULL,
  `end_date` date NOT NULL,
  `end_time` time DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending_admin','pending_owner','approved','rejected','waitlist') COLLATE utf8mb4_unicode_ci DEFAULT 'pending_admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `listing_id`, `user_id`, `start_date`, `start_time`, `end_date`, `end_time`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 3, '2026-07-31', NULL, '2026-08-02', NULL, 90.00, 'approved', '2026-07-30 11:21:40'),
(2, 2, 2, '2026-08-04', NULL, '2026-08-06', NULL, 240.00, 'pending_admin', '2026-07-30 11:21:40'),
(3, 3, 4, '2026-08-01', '10:00:00', '2026-08-01', '12:00:00', 30.00, 'approved', '2026-07-30 11:21:40'),
(4, 4, 5, '2026-08-09', NULL, '2026-08-11', NULL, 170.00, 'pending_owner', '2026-07-30 11:21:40'),
(5, 5, 6, '2026-08-03', NULL, '2026-08-05', NULL, 700.00, 'approved', '2026-07-30 11:21:40'),
(6, 7, 8, '2026-07-31', NULL, '2026-08-04', NULL, 380.00, 'rejected', '2026-07-30 11:21:40'),
(7, 10, 9, '2026-08-01', NULL, '2026-08-03', NULL, 100.00, 'waitlist', '2026-07-30 11:21:40'),
(8, 11, 10, '2026-08-06', NULL, '2026-08-13', NULL, 1750.00, 'approved', '2026-07-30 11:21:40'),
(9, 15, 12, '2026-07-31', NULL, '2026-08-02', NULL, 1200.00, 'pending_owner', '2026-07-30 11:21:40'),
(10, 16, 13, '2026-08-02', '14:00:00', '2026-08-02', '16:00:00', 20.00, 'approved', '2026-07-30 11:21:40'),
(11, 22, 14, '2026-08-04', NULL, '2026-08-05', NULL, 120.00, 'pending_admin', '2026-07-30 11:21:40'),
(12, 26, 15, '2026-08-09', NULL, '2026-08-14', NULL, 100.00, 'waitlist', '2026-07-30 11:21:40'),
(13, 1, 4, '2026-07-20', NULL, '2026-07-22', NULL, 90.00, 'approved', '2026-07-30 11:21:40'),
(14, 1, 5, '2026-07-25', NULL, '2026-07-28', NULL, 135.00, 'approved', '2026-07-30 11:21:40'),
(15, 2, 6, '2026-07-15', NULL, '2026-07-20', NULL, 600.00, 'approved', '2026-07-30 11:21:40'),
(16, 3, 7, '2026-07-27', '18:00:00', '2026-07-27', '20:00:00', 30.00, 'approved', '2026-07-30 11:21:40'),
(17, 4, 8, '2026-07-31', NULL, '2026-08-02', NULL, 255.00, 'approved', '2026-07-30 11:21:40'),
(18, 5, 9, '2026-07-10', NULL, '2026-07-12', NULL, 700.00, 'approved', '2026-07-30 11:21:40'),
(19, 5, 10, '2026-07-20', NULL, '2026-07-25', NULL, 1750.00, 'approved', '2026-07-30 11:21:40'),
(20, 8, 11, '2026-07-25', NULL, '2026-07-28', NULL, 180.00, 'approved', '2026-07-30 11:21:40'),
(21, 8, 12, '2026-08-04', NULL, '2026-08-07', NULL, 180.00, 'pending_admin', '2026-07-30 11:21:40'),
(22, 11, 13, '2026-06-30', NULL, '2026-07-05', NULL, 1250.00, 'approved', '2026-07-30 11:21:40'),
(23, 11, 14, '2026-07-15', NULL, '2026-07-20', NULL, 1250.00, 'approved', '2026-07-30 11:21:40'),
(24, 12, 15, '2026-07-28', '13:00:00', '2026-08-01', '17:00:00', 120.00, 'approved', '2026-07-30 11:21:40'),
(25, 14, 16, '2026-07-29', NULL, '2026-08-04', NULL, 450.00, 'approved', '2026-07-30 11:21:40'),
(26, 15, 17, '2026-07-20', NULL, '2026-07-22', NULL, 1200.00, 'approved', '2026-07-30 11:21:40'),
(27, 18, 2, '2026-07-23', NULL, '2026-07-24', NULL, 100.00, 'approved', '2026-07-30 11:21:40'),
(28, 18, 3, '2026-07-28', NULL, '2026-07-29', NULL, 100.00, 'approved', '2026-07-30 11:21:40'),
(29, 19, 4, '2026-07-18', NULL, '2026-07-22', NULL, 360.00, 'approved', '2026-07-30 11:21:40'),
(30, 21, 5, '2026-07-22', NULL, '2026-07-24', NULL, 80.00, 'approved', '2026-07-30 11:21:40'),
(31, 24, 6, '2026-07-26', NULL, '2026-07-28', NULL, 120.00, 'approved', '2026-07-30 11:21:40'),
(32, 6, 2, '2026-08-09', '10:00:00', '2026-08-09', '12:00:00', 10.00, 'approved', '2026-07-30 11:28:25'),
(33, 1, 7, '2026-08-11', NULL, '2026-08-19', NULL, 360.00, 'pending_admin', '2026-07-30 12:53:54'),
(34, 4, 7, '2026-08-06', NULL, '2026-08-07', NULL, 85.00, 'rejected', '2026-07-30 12:55:03'),
(35, 4, 7, '2026-09-22', NULL, '2026-09-24', NULL, 170.00, 'rejected', '2026-07-30 12:56:27'),
(36, 4, 2, '2026-08-19', NULL, '2026-08-21', NULL, 170.00, 'pending_admin', '2026-07-30 13:03:00'),
(37, 4, 2, '2026-08-24', NULL, '2026-08-26', NULL, 170.00, 'pending_admin', '2026-07-30 13:03:32'),
(38, 4, 7, '2026-08-19', NULL, '2026-08-26', NULL, 595.00, 'waitlist', '2026-07-30 13:07:03');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `listing_id`, `created_at`) VALUES
(1, 2, 5, '2026-07-30 11:21:40'),
(2, 3, 7, '2026-07-30 11:21:40'),
(3, 2, 1, '2026-07-30 11:31:03');

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
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`id`, `user_id`, `title`, `description`, `category`, `price`, `price_type`, `city`, `image`, `attributes`, `status`, `rejection_reason`, `admin_note`, `created_at`) VALUES
(1, 2, '2022 Honda Civic', 'A reliable and comfortable car for city trips. Excellent gas mileage.', 'Cars', 45.00, 'day', 'Tel Aviv', 'assets/img/honda_civic.png', '{\"make\": \"Honda\", \"year\": \"2022\", \"seats\": 5}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(2, 3, 'Cozy Studio Downtown', 'Modern studio apartment right in the heart of the city.', 'Apartments', 120.00, 'day', 'Jerusalem', 'assets/img/studio_apt.png', '{\"rooms\": 1, \"bathrooms\": 1}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(3, 2, 'Canon EOS R5 Camera', 'Professional camera for your next photoshoot. Comes with 2 lenses.', 'Equipment', 15.00, 'hour', 'Haifa', 'assets/img/canon_r5.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(4, 4, 'Jeep Wrangler 4x4', 'Perfect for off-road adventures!', 'Cars', 85.00, 'day', 'Eilat', 'assets/img/jeep_wrangler.png', '{\"make\": \"Jeep\", \"year\": \"2021\", \"seats\": 4}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(5, 3, 'Beachfront Villa', 'Wake up to the sound of waves. 3 bedrooms and private pool.', 'Apartments', 350.00, 'day', 'Netanya', 'assets/img/beach_villa.png', '{\"rooms\": 3, \"bathrooms\": 2}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(6, 4, 'Electric Scooter Pro', 'Fast and fun way to get around the city.', 'Equipment', 5.00, 'hour', 'Tel Aviv', 'assets/img/scooter_pro.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(7, 2, 'Tesla Model 3', 'Experience the future of driving with this fully electric sleek car.', 'Cars', 95.00, 'day', 'Jerusalem', 'assets/img/tesla_model3.png', '{\"make\": \"Tesla\", \"year\": \"2023\", \"seats\": 5}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(8, 4, 'High-End Gaming PC', 'Rent a massive rig for the weekend! RTX 4090 included.', 'Equipment', 60.00, 'day', 'Eilat', 'assets/img/gaming_pc.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(9, 2, 'Indoor Basketball Court', 'Full sized pristine indoor court. 2 hour slots only.', 'Sports field', 25.00, 'hour', 'Tel Aviv', 'assets/img/bball_court.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(10, 5, '2023 Toyota Corolla', 'Very reliable car for family trips.', 'Cars', 50.00, 'day', 'Haifa', 'assets/img/toyota_corolla.png', '{\"make\": \"Toyota\", \"year\": \"2023\", \"seats\": 5}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(11, 6, 'Traditional Villa', 'Spacious villa with authentic design.', 'Apartments', 250.00, 'day', 'Nazareth', 'assets/img/traditional_villa.png', '{\"rooms\": 4, \"bathrooms\": 3}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(12, 7, 'DJ Pioneer Setup', 'Full professional DJ setup.', 'Equipment', 30.00, 'hour', 'Ramallah', 'assets/img/dj_setup.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(13, 8, 'Sea View Studio', 'Beautiful studio right next to the sea.', 'Apartments', 150.00, 'day', 'Jaffa', 'assets/img/seaview_studio.png', '{\"rooms\": 1, \"bathrooms\": 1}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(14, 9, '2021 Hyundai Tucson', 'Great SUV for the whole family.', 'Cars', 75.00, 'day', 'Tel Aviv', 'assets/img/hyundai_tucson.png', '{\"make\": \"Hyundai\", \"year\": \"2021\", \"seats\": 5}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(15, 10, 'Luxury Penthouse', 'High-end penthouse with a pool.', 'Apartments', 600.00, 'day', 'Herzliya', 'assets/img/luxury_penthouse.png', '{\"rooms\": 5, \"bathrooms\": 4}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(16, 11, 'E-Bike Commuter', 'Fast electric bike for city transport.', 'Equipment', 10.00, 'hour', 'Jerusalem', 'assets/img/ebike.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(17, 12, 'Mountain Bike', 'Professional mountain bike.', 'Equipment', 25.00, 'day', 'Eilat', 'assets/img/mountain_bike.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(18, 5, 'Vintage Rolex Watch', 'Beautiful vintage watch for special events.', 'Equipment', 100.00, 'day', 'Haifa', 'assets/img/rolex_watch.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(19, 10, '2024 Kia Sportage', 'Brand new SUV.', 'Cars', 90.00, 'day', 'Herzliya', 'assets/img/scooter_pro.png', '{\"make\": \"Kia\", \"year\": \"2024\", \"seats\": 5}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(20, 13, 'GoPro Hero 11', 'Capture your adventures.', 'Equipment', 15.00, 'day', 'Eilat', 'assets/img/gopro.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(21, 14, 'Professional Studio Lights', 'Great for photoshoots.', 'Equipment', 40.00, 'day', 'Tel Aviv', 'assets/img/studiolights.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(22, 15, 'Mercedes-Benz C-Class', 'Luxury sedan.', 'Cars', 120.00, 'day', 'Haifa', 'assets/img/scooter_pro.png', '{\"make\": \"Mercedes-Benz\", \"year\": \"2022\", \"seats\": 5}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(23, 16, 'Cozy Room near University', 'Perfect for students or short stays.', 'Apartments', 50.00, 'day', 'Jerusalem', 'assets/img/studio_apt.png', '{\"rooms\": 1, \"bathrooms\": 1}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(24, 17, 'Spacious Family Car - Mazda 5', '7 seater minivan.', 'Cars', 60.00, 'day', 'Nazareth', 'assets/img/mazda5.png', '{\"make\": \"Mazda\", \"year\": \"2019\", \"seats\": 7}', 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(25, 18, 'Acoustic Guitar - Yamaha', 'Beautiful sounding acoustic guitar.', 'Equipment', 12.00, 'day', 'Tel Aviv', 'assets/img/canon_r5.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(26, 13, 'Camping Tent 4-Person', 'Waterproof camping tent.', 'Equipment', 20.00, 'day', 'Eilat', 'assets/img/scooter_pro.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(27, 14, 'Drone DJI Mavic Air 2', '4K drone with extra batteries.', 'Equipment', 70.00, 'day', 'Herzliya', 'assets/img/scooter_pro.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(28, 15, 'Office Space Desk', 'Quiet desk in a shared office.', 'Apartments', 25.00, 'day', 'Haifa', 'assets/img/studio_apt.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40'),
(29, 16, 'Electric Scooter Xiaomi', 'Great for commuting.', 'Equipment', 15.00, 'day', 'Jerusalem', 'assets/img/scooter_pro.png', NULL, 'approved', NULL, NULL, '2026-07-30 11:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `listing_images`
--

DROP TABLE IF EXISTS `listing_images`;
CREATE TABLE IF NOT EXISTS `listing_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 4, 'A new booking for your listing \"Electric Scooter Pro\" was submitted and awaits admin pre-approval.', 'user/profile.php', 0, '2026-07-30 11:28:25'),
(2, 1, 'New pending booking for listing \"Electric Scooter Pro\" awaits your approval.', 'admin/admin.php?tab=bookings', 0, '2026-07-30 11:28:25'),
(3, 4, 'Admin approved the booking request for your listing \"Electric Scooter Pro\". It is now awaiting your final approval.', 'user/profile.php', 0, '2026-07-30 11:29:21'),
(4, 2, 'Your booking request for \"Electric Scooter Pro\" has been approved by the owner! Check your profile to see contact details.', 'user/profile.php', 0, '2026-07-30 11:30:15'),
(5, 4, 'Your listing \'Electric Scooter Pro\' has been reported.', 'listings/view_listing.php?id=6', 0, '2026-07-30 11:32:13'),
(6, 1, 'Listing \'Electric Scooter Pro\' has been reported.', 'admin/admin.php?tab=reports', 0, '2026-07-30 11:32:13'),
(7, 1, 'New support ticket #1 opened.', 'support/view_ticket.php?id=1', 0, '2026-07-30 11:33:38'),
(8, 2, 'Admin replied to your support ticket #1', 'support/view_ticket.php?id=1', 0, '2026-07-30 11:33:57'),
(9, 2, 'A new booking for your listing \"2022 Honda Civic\" was submitted and awaits admin pre-approval.', 'user/profile.php', 0, '2026-07-30 12:53:54'),
(10, 1, 'New pending booking for listing \"2022 Honda Civic\" awaits your approval.', 'admin/admin.php?tab=bookings', 0, '2026-07-30 12:53:54'),
(11, 4, 'A new booking for your listing \"Jeep Wrangler 4x4\" was submitted and awaits admin pre-approval.', 'user/profile.php', 0, '2026-07-30 12:55:03'),
(12, 1, 'New pending booking for listing \"Jeep Wrangler 4x4\" awaits your approval.', 'admin/admin.php?tab=bookings', 0, '2026-07-30 12:55:03'),
(13, 4, 'A booking request for your listing \"Jeep Wrangler 4x4\" was cancelled by the renter.', 'user/profile.php', 0, '2026-07-30 12:55:27'),
(14, 4, 'A new booking for your listing \"Jeep Wrangler 4x4\" was submitted and awaits admin pre-approval.', 'user/profile.php', 0, '2026-07-30 12:56:27'),
(15, 1, 'New pending booking for listing \"Jeep Wrangler 4x4\" awaits your approval.', 'admin/admin.php?tab=bookings', 0, '2026-07-30 12:56:27'),
(16, 4, 'A booking request for your listing \"Jeep Wrangler 4x4\" was cancelled by the renter.', 'user/profile.php', 0, '2026-07-30 12:56:37'),
(17, 4, 'A new booking for your listing \"Jeep Wrangler 4x4\" was submitted and awaits admin pre-approval.', 'user/profile.php', 0, '2026-07-30 13:03:00'),
(18, 1, 'New pending booking for listing \"Jeep Wrangler 4x4\" awaits your approval.', 'admin/admin.php?tab=bookings', 0, '2026-07-30 13:03:00'),
(19, 4, 'A new booking for your listing \"Jeep Wrangler 4x4\" was submitted and awaits admin pre-approval.', 'user/profile.php', 0, '2026-07-30 13:03:32'),
(20, 1, 'New pending booking for listing \"Jeep Wrangler 4x4\" awaits your approval.', 'admin/admin.php?tab=bookings', 0, '2026-07-30 13:03:32'),
(21, 7, 'You joined the waitlist for \"Jeep Wrangler 4x4\". Your request will automatically promote if pending requests resolve.', 'user/profile.php', 0, '2026-07-30 13:07:03');

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
  `status` enum('pending','resolved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `listing_id`, `user_id`, `reason`, `status`, `admin_note`, `created_at`) VALUES
(1, 6, 2, 'This looks like a fake listing.', 'resolved', NULL, '2026-07-30 11:32:13');

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
(1, 1, 3, 5, 'Amazing car! Super clean and ran perfectly.', '2026-07-30 11:21:40'),
(2, 1, 4, 4, 'Great car, pickup was a bit difficult though.', '2026-07-30 11:21:40'),
(3, 1, 5, 5, 'Very smooth ride.', '2026-07-30 11:21:40'),
(4, 2, 4, 5, 'Absolutely loved the studio! Very highly recommended.', '2026-07-30 11:21:40'),
(5, 2, 6, 4, 'Good location, a bit noisy.', '2026-07-30 11:21:40'),
(6, 3, 7, 5, 'Perfect camera for my shoot.', '2026-07-30 11:21:40'),
(7, 4, 8, 5, 'Off-roading was fun.', '2026-07-30 11:21:40'),
(8, 5, 2, 5, 'The view is unbelievable. Will rent again next summer.', '2026-07-30 11:21:40'),
(9, 5, 9, 5, 'Amazing villa.', '2026-07-30 11:21:40'),
(10, 5, 10, 4, 'Great but expensive.', '2026-07-30 11:21:40'),
(11, 7, 3, 5, 'Driving a Tesla is an experience. Owner was very nice!', '2026-07-30 11:21:40'),
(12, 8, 11, 5, 'Played games all weekend at ultra settings.', '2026-07-30 11:21:40'),
(13, 11, 13, 5, 'Beautiful villa in Nazareth.', '2026-07-30 11:21:40'),
(14, 11, 14, 5, 'Highly recommended.', '2026-07-30 11:21:40'),
(15, 12, 15, 4, 'Good DJ set.', '2026-07-30 11:21:40'),
(16, 14, 16, 5, 'Great family car.', '2026-07-30 11:21:40'),
(17, 15, 17, 5, 'Luxury at its best.', '2026-07-30 11:21:40'),
(18, 18, 2, 5, 'Looked great for the wedding.', '2026-07-30 11:21:40'),
(19, 18, 3, 5, 'Classic watch.', '2026-07-30 11:21:40'),
(20, 19, 4, 5, 'Brand new car, loved it.', '2026-07-30 11:21:40'),
(21, 21, 5, 5, 'Very bright lights.', '2026-07-30 11:21:40'),
(22, 24, 6, 4, 'Spacious and comfortable.', '2026-07-30 11:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','answered','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `subject`, `status`, `created_at`) VALUES
(1, 2, 'Test Ticket Subject', 'answered', '2026-07-30 11:33:38');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `sender_id`, `message`, `created_at`) VALUES
(1, 1, 2, 'I have a question about my booking.', '2026-07-30 11:33:38'),
(2, 1, 1, 'Thanks for reaching out, we are looking into it.', '2026-07-30 11:33:57');

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `profile_picture`, `bio`, `is_blocked`, `role`, `verified`, `verification_code`, `created_at`) VALUES
(1, 'Admin', 'esmaael.esmaael@gmail.com', '$2y$10$KQdG2RoRUAfxUu4MAWrFrOIBp/cCDgxlTUNKypgVq.49VG9ds6g5y', '000000000', 'assets/img/default_avatar.png', 'System Administrator.', 0, 'admin', 1, NULL, '2026-07-30 11:21:40'),
(2, 'Amir Goldstein', 'amir@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '123456789', 'assets/img/default_avatar.png', 'I love renting cars.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(3, 'Nour Al-Fayed', 'nour@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '987654321', 'assets/img/default_avatar.png', 'Apartment owner in SF.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(4, 'Eitan Cohen', 'eitan@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '111222333', 'assets/img/default_avatar.png', 'Tech enthusiast and gadget renter.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(5, 'Ahmed Youssef', 'ahmed@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0501234567', 'assets/img/default_avatar.png', 'Car enthusiast and mechanic.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(6, 'Fatima Ali', 'fatima@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0547654321', 'assets/img/default_avatar.png', 'Love renting out my beautiful villa.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(7, 'Omar Hassan', 'omar@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0529876543', 'assets/img/default_avatar.png', 'Professional DJ.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(8, 'Layla Mahmoud', 'layla@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0534567890', 'assets/img/default_avatar.png', 'Art lover and host in Jaffa.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(9, 'Yossi Cohen', 'yossi@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0501112233', 'assets/img/default_avatar.png', 'Daily commuter looking to share rides.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(10, 'Noa Levi', 'noa@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0543332211', 'assets/img/default_avatar.png', 'Real estate investor.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(11, 'Itzhak Mizrahi', 'itzhak@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0524445566', 'assets/img/default_avatar.png', 'Cycling enthusiastic.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(12, 'Maya Avraham', 'maya@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0536667788', 'assets/img/default_avatar.png', 'Love nature and hiking.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(13, 'Hassan Nasr', 'hassan@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0541112222', 'assets/img/default_avatar.png', 'Traveler.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(14, 'Shira Ben-David', 'shira@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0523334444', 'assets/img/default_avatar.png', 'Photographer.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(15, 'Tariq Mansour', 'tariq@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0505556666', 'assets/img/default_avatar.png', 'Business owner.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(16, 'Yael Rabin', 'yael@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0537778888', 'assets/img/default_avatar.png', 'Student.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(17, 'Zainab Qasim', 'zainab@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0549990000', 'assets/img/default_avatar.png', 'Teacher.', 0, 'user', 1, NULL, '2026-07-30 11:21:40'),
(18, 'Avi Katz', 'avi@rently.test', '$2y$10$iLR4f6gMvekUgnHNq4YAgeN9UHGsoN.QdebWq4POa5ZynO/iotA6e', '0521239876', 'assets/img/default_avatar.png', 'Musician.', 1, 'user', 1, NULL, '2026-07-30 11:21:40');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `listing_images`
--
ALTER TABLE `listing_images`
  ADD CONSTRAINT `listing_images_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
