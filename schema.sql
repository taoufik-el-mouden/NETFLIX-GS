-- ============================================================
-- Netflix GS - Digital Accounts Sales System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS netflix_gs
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE netflix_gs;

-- ---------------------------------------------------------------
-- Table: users
-- Admin users who can log in to the management panel
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(60)  NOT NULL UNIQUE,               -- Login username
    `email`      VARCHAR(255) NOT NULL UNIQUE,               -- Optional email
    `password`   VARCHAR(255) NOT NULL,                      -- bcrypt hashed password
    `full_name`  VARCHAR(150) NOT NULL DEFAULT '',           -- Display name in navbar
    `role`       ENUM('admin','manager') NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account: username=admin | password=admin123
-- !! CHANGE THE PASSWORD IMMEDIATELY after first login !!
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `full_name`, `role`)
VALUES (
    'admin',
    'admin@netflix-gs.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- bcrypt of "admin123"
    'المدير العام',
    'admin'
);

-- ---------------------------------------------------------------
-- Table: main_accounts
-- Stores the main Netflix accounts purchased (each has 5 profiles)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `main_accounts` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_name`   VARCHAR(100) NOT NULL DEFAULT 'Netflix',
    `email`          VARCHAR(255) NOT NULL,
    `password`       VARCHAR(255) NOT NULL,
    `purchase_date`  DATE NOT NULL,
    `expiry_date`    DATE NOT NULL,
    `purchase_price` DECIMAL(8,2) NOT NULL DEFAULT 35.00,  -- Fixed cost per main account
    `status`         ENUM('active','expired','full') NOT NULL DEFAULT 'active',
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Table: profiles
-- Each main account has exactly 5 profiles (auto-created on insert)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profiles` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `account_id`     INT UNSIGNED NOT NULL,                 -- FK to main_accounts
    `profile_number` TINYINT UNSIGNED NOT NULL,             -- 1 to 5
    `profile_name`   VARCHAR(100) DEFAULT NULL,             -- Custom name (e.g. Kids)
    `pin_code`       VARCHAR(10) DEFAULT NULL,
    `is_sold`        TINYINT(1) NOT NULL DEFAULT 0,         -- 0=available, 1=sold
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_profile_account`
        FOREIGN KEY (`account_id`) REFERENCES `main_accounts`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uq_account_profile` (`account_id`, `profile_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Table: sales
-- Records every sold profile with customer info and dates
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `profile_id`     INT UNSIGNED NOT NULL,                 -- FK to profiles
    `customer_name`  VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(30)  NOT NULL,                 -- WhatsApp number
    `selling_price`  DECIMAL(8,2) NOT NULL,                 -- 30, 40, or 50 DH
    `sale_date`      DATE NOT NULL,
    `expiry_date`    DATE NOT NULL,                         -- sale_date + 30 days
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sale_profile`
        FOREIGN KEY (`profile_id`) REFERENCES `profiles`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Table: expenses
-- Tracks ADS spending and other business costs
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category`     ENUM('ads','other') NOT NULL DEFAULT 'ads',   -- ads = إعلانات, other = مصاريف أخرى
    `description`  VARCHAR(500) NOT NULL,                         -- وصف المصروف
    `amount`       DECIMAL(10,2) NOT NULL,                        -- المبلغ بالدرهم
    `expense_date` DATE NOT NULL,                                 -- تاريخ المصروف
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
