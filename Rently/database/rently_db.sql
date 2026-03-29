-- ============================================
-- Rently Database Schema  —  Full Platform
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
    user_id             INT AUTO_INCREMENT PRIMARY KEY,
    full_name           VARCHAR(100)    NOT NULL,
    email               VARCHAR(255)    NOT NULL UNIQUE,
    password_hash       VARCHAR(255)    NOT NULL,
    dev_password        VARCHAR(255)    DEFAULT NULL,
    phone_number        VARCHAR(20)     DEFAULT NULL,
    profile_image       VARCHAR(500)    DEFAULT NULL,
    bio                 TEXT            DEFAULT NULL,
    role                ENUM('renter','owner','admin') NOT NULL DEFAULT 'renter',
    is_blocked          TINYINT(1)      NOT NULL DEFAULT 0,
    is_verified         TINYINT(1)      NOT NULL DEFAULT 0,
    verification_code   VARCHAR(10)     DEFAULT NULL,
    verification_expires DATETIME       DEFAULT NULL,
    two_fa_enabled      TINYINT(1)      NOT NULL DEFAULT 0,
    two_fa_code         VARCHAR(10)     DEFAULT NULL,
    two_fa_expires      DATETIME        DEFAULT NULL,
    preferred_lang      VARCHAR(5)      NOT NULL DEFAULT 'en',
    preferred_theme     VARCHAR(10)     NOT NULL DEFAULT 'light',
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

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
    category        ENUM('apartment','car','sport_venue','equipment','studio','parking') NOT NULL,
    address         VARCHAR(300)    DEFAULT NULL,
    city            VARCHAR(100)    DEFAULT NULL,
    latitude        DECIMAL(10,7)   DEFAULT NULL,
    longitude       DECIMAL(10,7)   DEFAULT NULL,
    price_per_hour  DECIMAL(10,2)   DEFAULT 0.00,
    price_per_day   DECIMAL(10,2)   DEFAULT 0.00,
    image_url       VARCHAR(500)    DEFAULT NULL,
    is_approved     TINYINT(1)      NOT NULL DEFAULT 0,
    status          ENUM('active','maintenance','pending') NOT NULL DEFAULT 'pending',
    -- Category-specific fields (JSON for flexibility)
    extra_fields    JSON            DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_assets_category (category),
    INDEX idx_assets_status   (status),
    INDEX idx_assets_owner    (owner_id),
    INDEX idx_assets_approved (is_approved),
    INDEX idx_assets_city     (city),
    FULLTEXT idx_assets_search (title, description),

    CONSTRAINT fk_assets_owner
        FOREIGN KEY (owner_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Asset Images (Multi-Image Gallery)
-- ============================================
CREATE TABLE asset_images (
    image_id    INT AUTO_INCREMENT PRIMARY KEY,
    asset_id    INT             NOT NULL,
    image_url   VARCHAR(500)    NOT NULL,
    sort_order  INT             NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_asset_images_asset (asset_id),

    CONSTRAINT fk_asset_images_asset
        FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Bookings Table
-- ============================================
CREATE TABLE bookings (
    booking_id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT             NOT NULL,
    asset_id            INT             NOT NULL,
    start_time          DATETIME        NOT NULL,
    end_time            DATETIME        NOT NULL,
    total_price         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    status              ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    cancellation_reason TEXT            DEFAULT NULL,
    cancelled_at        DATETIME        DEFAULT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_bookings_asset  (asset_id),
    INDEX idx_bookings_user   (user_id),
    INDEX idx_bookings_status (status),
    INDEX idx_bookings_dates  (start_time, end_time),

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
-- Wishlists
-- ============================================
CREATE TABLE wishlists (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    asset_id    INT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_wishlist (user_id, asset_id),
    INDEX idx_wishlists_user (user_id),

    CONSTRAINT fk_wishlists_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_wishlists_asset
        FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Notifications
-- ============================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    title           VARCHAR(200)    NOT NULL,
    message         TEXT            DEFAULT NULL,
    type            ENUM('booking','review','system','report','approval') NOT NULL DEFAULT 'system',
    is_read         TINYINT(1)      NOT NULL DEFAULT 0,
    link            VARCHAR(500)    DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (user_id, is_read),

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Reports
-- ============================================
CREATE TABLE reports (
    report_id       INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id     INT             NOT NULL,
    asset_id        INT             DEFAULT NULL,
    reported_user_id INT            DEFAULT NULL,
    reason          TEXT            NOT NULL,
    status          ENUM('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
    admin_notes     TEXT            DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_reports_status (status),

    CONSTRAINT fk_reports_reporter
        FOREIGN KEY (reporter_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Search History (for smart suggestions)
-- ============================================
CREATE TABLE search_history (
    search_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT             DEFAULT NULL,
    query       VARCHAR(300)    NOT NULL,
    category    VARCHAR(50)     DEFAULT NULL,
    city        VARCHAR(100)    DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_search_user (user_id),
    INDEX idx_search_date (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Seed: Default Admin Account
-- Password: admin123 (hashed with password_hash)
-- ============================================
INSERT INTO users (full_name, email, password_hash, phone_number, role, is_verified)
VALUES ('Admin', 'admin@rently.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0500000000', 'admin', 1);
