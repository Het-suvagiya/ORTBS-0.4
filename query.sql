-- QuickTable Database Schema
-- DATA SAFE: This script will NOT delete your existing data.
-- FAIL SAFE: Creating tables in the CURRENTLY SELECTED database.

SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 1. CREATE ALL TABLES FIRST
-- ==========================================

-- Admin table
CREATE TABLE IF NOT EXISTS `tbl_admin` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_firstname` varchar(50) NOT NULL,
  `a_lastname` varchar(50) NOT NULL,
  `a_email` varchar(100) NOT NULL,
  `a_password` varchar(255) NOT NULL,
  `a_gender` enum('Male','Female','Other') DEFAULT NULL,
  `a_dob` date DEFAULT NULL,
  `a_image` varchar(255) DEFAULT NULL,
  `a_bio` text DEFAULT NULL,
  `a_created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`a_id`),
  UNIQUE KEY `a_email` (`a_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Manager table
CREATE TABLE IF NOT EXISTS `tbl_manager` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT,
  `m_firstname` varchar(50) NOT NULL,
  `m_lastname` varchar(50) NOT NULL,
  `m_email` varchar(100) NOT NULL,
  `m_password` varchar(255) NOT NULL,
  `m_phone` varchar(20) DEFAULT NULL,
  `m_gender` enum('Male','Female','Other') DEFAULT NULL,
  `m_dob` date DEFAULT NULL,
  `m_image` varchar(255) DEFAULT NULL,
  `m_bio` text DEFAULT NULL,
  `m_created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`m_id`),
  UNIQUE KEY `m_email` (`m_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User table
CREATE TABLE IF NOT EXISTS `tbl_users` (
  `u_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_firstname` varchar(50) NOT NULL,
  `u_lastname` varchar(50) NOT NULL,
  `u_email` varchar(100) NOT NULL,
  `u_password` varchar(255) NOT NULL,
  `u_phone` varchar(20) DEFAULT NULL,
  `u_gender` enum('Male','Female','Other') DEFAULT NULL,
  `u_dob` date DEFAULT NULL,
  `u_image` varchar(255) DEFAULT NULL,
  `u_bio` text DEFAULT NULL,
  `u_created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`u_id`),
  UNIQUE KEY `u_email` (`u_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Restaurants table
CREATE TABLE IF NOT EXISTS `restaurants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `manager_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `cuisine` VARCHAR(100),
    `location` VARCHAR(255),
    `primary_image` VARCHAR(255),
    `phone` VARCHAR(20),
    `email` VARCHAR(100),
    `seating_type` ENUM('inside', 'outside', 'both'),
    `avg_price` DECIMAL(10, 2),
    `max_guests` INT DEFAULT 20,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `is_published` BOOLEAN DEFAULT FALSE,
    `description` TEXT DEFAULT NULL,
    `menu_file` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `restaurant_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `restaurant_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `restaurant_menu` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    item_discount DECIMAL(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `restaurant_schedule` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `restaurant_id` INT NOT NULL,
    `day_of_week` ENUM('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat') NOT NULL,
    `is_closed` BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `restaurant_id` INT NOT NULL,
    `booking_date` DATE NOT NULL,
    `booking_time` TIME NOT NULL,
    `guests` INT NOT NULL,
    `status` ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `restaurant_id` INT NOT NULL,
    UNIQUE KEY `unique_fav` (`user_id`, `restaurant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 2. ADD FOREIGN KEYS
-- ==========================================
-- Adding these separately at the end. 
-- If you see #1061 (Existing Key) or #1060 (Duplicate Column), ignore them.

ALTER TABLE `restaurants` ADD CONSTRAINT `fk_res_manager` FOREIGN KEY (`manager_id`) REFERENCES `tbl_manager`(`m_id`) ON DELETE CASCADE;
ALTER TABLE `restaurant_images` ADD CONSTRAINT `fk_img_res` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE;
ALTER TABLE `restaurant_schedule` ADD CONSTRAINT `fk_sch_res` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE;
ALTER TABLE `bookings` ADD CONSTRAINT `fk_book_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`u_id`) ON DELETE CASCADE;
ALTER TABLE `bookings` ADD CONSTRAINT `fk_book_res` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE;
ALTER TABLE `favorites` ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`u_id`) ON DELETE CASCADE;
ALTER TABLE `favorites` ADD CONSTRAINT `fk_fav_res` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

