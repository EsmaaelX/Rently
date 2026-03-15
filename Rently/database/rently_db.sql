-- ============================================
-- Rently Database Schema
-- P2P Sharing Economy Platform
-- ============================================

DROP DATABASE IF EXISTS rently;

CREATE DATABASE rently
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rently;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100)    NOT NULL,
    email       VARCHAR(255)    NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    phone_number VARCHAR(20)    DEFAULT NULL,
    role        ENUM('renter','owner','admin') NOT NULL DEFAULT 'renter',
    is_blocked  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
) ENGINE=InnoDB;

-- ============================================
-- Assets Table
-- ============================================
CREATE TABLE assets (
    asset_id        INT AUTO_INCREMENT PRIMARY KEY,
    owner_id        INT             NOT NULL,
    title           VARCHAR(200)    NOT NULL,
    description     TEXT            DEFAULT NULL,
    category        ENUM('apartment','car','sport_venue') NOT NULL,
    address         VARCHAR(300)    DEFAULT NULL,
    latitude        DECIMAL(10,7)   DEFAULT NULL,
    longitude       DECIMAL(10,7)   DEFAULT NULL,
    price_per_hour  DECIMAL(10,2)   DEFAULT 0.00,
    price_per_day   DECIMAL(10,2)   DEFAULT 0.00,
    image_url       VARCHAR(500)    DEFAULT NULL,
    status          ENUM('active','maintenance') NOT NULL DEFAULT 'active',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_assets_category (category),
    INDEX idx_assets_status   (status),
    INDEX idx_assets_owner    (owner_id),

    CONSTRAINT fk_assets_owner
        FOREIGN KEY (owner_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Bookings Table
-- ============================================
CREATE TABLE bookings (
    booking_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT             NOT NULL,
    asset_id    INT             NOT NULL,
    start_time  DATETIME        NOT NULL,
    end_time    DATETIME        NOT NULL,
    total_price DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    status      ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_bookings_asset  (asset_id),
    INDEX idx_bookings_user   (user_id),
    INDEX idx_bookings_status (status),

    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_asset
        FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Reviews Table
-- ============================================
CREATE TABLE reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT         NOT NULL,
    asset_id    INT         NOT NULL,
    rating      INT         NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment     TEXT        DEFAULT NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_reviews_asset (asset_id),

    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_asset
        FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Payments Table
-- ============================================
CREATE TABLE payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT             NOT NULL,
    user_id         INT             NOT NULL,
    amount          DECIMAL(10,2)   NOT NULL,
    payment_method  VARCHAR(50)     DEFAULT 'credit_card',
    transaction_id  VARCHAR(100)    DEFAULT NULL,
    status          ENUM('paid','refunded','held') NOT NULL DEFAULT 'held',
    payment_date    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_payments_booking (booking_id),

    CONSTRAINT fk_payments_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Seed: Default Admin Account
-- Password: admin123 (hashed with password_hash)
-- ============================================
INSERT INTO users (full_name, email, password_hash, phone_number, role)
VALUES ('Admin', 'admin@rently.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0500000000', 'admin');
