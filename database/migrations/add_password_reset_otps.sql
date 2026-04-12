-- ============================================================
-- Migration: Add password_reset_otps table for OTP-based password reset
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_reset_otps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `otp_code` VARCHAR(255) NOT NULL COMMENT 'Bcrypt-hashed OTP code',
    `attempts` INT NOT NULL DEFAULT 0 COMMENT 'Number of failed verification attempts',
    `used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if code has been used or invalidated',
    `expires_at` DATETIME NOT NULL COMMENT 'When the OTP expires',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_otp_lookup` (`user_id`, `used`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Migration: Remove 'password_reset' from current_requests ENUM
-- ============================================================

-- Step 1: Delete any existing password_reset requests
DELETE FROM `current_requests` WHERE `request_type` = 'password_reset';

-- Step 2: Alter the ENUM to remove 'password_reset'
ALTER TABLE `current_requests` 
    MODIFY COLUMN `request_type` ENUM('replacement', 'student_deletion') NOT NULL;
