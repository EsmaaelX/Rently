-- upgrade.sql
-- Database Upgrade Script for Rently
-- Run this script to update your existing database structure without losing seed data

-- 1. Add start_time and end_time to bookings for hourly rentals support
ALTER TABLE bookings ADD COLUMN start_time TIME DEFAULT NULL AFTER start_date;
ALTER TABLE bookings ADD COLUMN end_time TIME DEFAULT NULL AFTER end_date;

-- 2. Modify listings status ENUM to include 'rejected' and add rejection_reason, admin_note columns
ALTER TABLE listings MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';
ALTER TABLE listings ADD COLUMN rejection_reason VARCHAR(255) DEFAULT NULL AFTER status;
ALTER TABLE listings ADD COLUMN admin_note TEXT DEFAULT NULL AFTER rejection_reason;

-- 3. Create listing_images table for supporting multiple images
CREATE TABLE IF NOT EXISTS listing_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- 4. Create reports table if not exists (with status and admin_note)
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- In case reports table already existed but with a different status ENUM or missing admin_note:
ALTER TABLE reports MODIFY COLUMN status ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending';
ALTER TABLE reports ADD COLUMN admin_note TEXT DEFAULT NULL AFTER status;

-- 5. Modify tickets status to support 'answered'
ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'answered', 'closed') DEFAULT 'open';
