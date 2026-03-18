-- ============================================================
-- CampusCare: School Clinic Patient Information & Medicine Record System
-- Database Schema
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+08:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `campuscare` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `campuscare`;

-- ============================================================
-- Table: programs (Academic programs/courses)
-- ============================================================
CREATE TABLE IF NOT EXISTS `programs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_program_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: year_levels
-- ============================================================
CREATE TABLE IF NOT EXISTS `year_levels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `order_num` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: users (System accounts: admin, nurse, rep)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `role` ENUM('admin','nurse','rep') NOT NULL,
  `assigned_program_id` INT(11) DEFAULT NULL,
  `assigned_year_level_id` INT(11) DEFAULT NULL,
  `assigned_section` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `deactivation_reason` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `security_question` VARCHAR(255) DEFAULT NULL,
  `security_answer` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `fk_user_program` (`assigned_program_id`),
  KEY `fk_user_year_level` (`assigned_year_level_id`),
  CONSTRAINT `fk_user_program` FOREIGN KEY (`assigned_program_id`) REFERENCES `programs`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_year_level` FOREIGN KEY (`assigned_year_level_id`) REFERENCES `year_levels`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: students
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(30) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') NOT NULL,
  `program_id` INT(11) DEFAULT NULL,
  `year_level_id` INT(11) DEFAULT NULL,
  `section` VARCHAR(20) DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `blood_type` VARCHAR(5) DEFAULT NULL,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_id` (`student_id`),
  KEY `fk_student_program` (`program_id`),
  KEY `fk_student_year` (`year_level_id`),
  KEY `fk_student_creator` (`created_by`),
  CONSTRAINT `fk_student_program` FOREIGN KEY (`program_id`) REFERENCES `programs`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_student_year` FOREIGN KEY (`year_level_id`) REFERENCES `year_levels`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_student_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: allergies
-- ============================================================
CREATE TABLE IF NOT EXISTS `allergies` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `allergen` VARCHAR(150) NOT NULL,
  `reaction` VARCHAR(255) DEFAULT NULL,
  `severity` ENUM('Mild','Moderate','Severe') NOT NULL DEFAULT 'Mild',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_allergy_student` (`student_id`),
  CONSTRAINT `fk_allergy_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: chronic_conditions
-- ============================================================
CREATE TABLE IF NOT EXISTS `chronic_conditions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `condition_name` VARCHAR(150) NOT NULL,
  `diagnosis_date` DATE DEFAULT NULL,
  `status` ENUM('Active','Managed','Resolved') NOT NULL DEFAULT 'Active',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_condition_student` (`student_id`),
  CONSTRAINT `fk_condition_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: medications
-- ============================================================
CREATE TABLE IF NOT EXISTS `medications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `medication_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(100) DEFAULT NULL,
  `frequency` VARCHAR(100) DEFAULT NULL,
  `prescribing_doctor` VARCHAR(150) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_medication_student` (`student_id`),
  CONSTRAINT `fk_medication_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: immunizations
-- ============================================================
CREATE TABLE IF NOT EXISTS `immunizations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `vaccine_name` VARCHAR(150) NOT NULL,
  `date_administered` DATE DEFAULT NULL,
  `dose_number` VARCHAR(20) DEFAULT NULL,
  `administered_by` VARCHAR(150) DEFAULT NULL,
  `next_dose_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_immunization_student` (`student_id`),
  CONSTRAINT `fk_immunization_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: emergency_contacts (per student)
-- ============================================================
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `contact_name` VARCHAR(150) NOT NULL,
  `relationship` VARCHAR(50) NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_emergency_student` (`student_id`),
  CONSTRAINT `fk_emergency_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: visits (Clinic visit log)
-- ============================================================
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `attended_by` INT(11) DEFAULT NULL,
  `visit_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blood_pressure` VARCHAR(20) DEFAULT NULL,
  `temperature` DECIMAL(4,1) DEFAULT NULL,
  `pulse_rate` INT(11) DEFAULT NULL,
  `respiratory_rate` INT(11) DEFAULT NULL,
  `weight` DECIMAL(5,1) DEFAULT NULL,
  `height` DECIMAL(5,1) DEFAULT NULL,
  `complaint_category` VARCHAR(100) NOT NULL,
  `complaint` TEXT DEFAULT NULL,
  `assessment` TEXT DEFAULT NULL,
  `treatment` TEXT DEFAULT NULL,
  `follow_up_notes` TEXT DEFAULT NULL,
  `follow_up_date` DATE DEFAULT NULL,
  `status` ENUM('Completed','Follow-up','Referred') NOT NULL DEFAULT 'Completed',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_visit_student` (`student_id`),
  KEY `fk_visit_attendant` (`attended_by`),
  KEY `idx_visit_date` (`visit_date`),
  CONSTRAINT `fk_visit_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_visit_attendant` FOREIGN KEY (`attended_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: announcements
-- ============================================================
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `posted_by` INT(11) DEFAULT NULL,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_announcement_user` (`posted_by`),
  CONSTRAINT `fk_announcement_user` FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: faqs
-- ============================================================
CREATE TABLE IF NOT EXISTS `faqs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(500) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: first_aid_guidelines
-- ============================================================
CREATE TABLE IF NOT EXISTS `first_aid_guidelines` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: clinic_emergency_contacts
-- ============================================================
CREATE TABLE IF NOT EXISTS `clinic_emergency_contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `role` VARCHAR(100) DEFAULT NULL,
  `phone_number` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: clinic_hours
-- ============================================================
CREATE TABLE IF NOT EXISTS `clinic_hours` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `day_of_week` VARCHAR(15) NOT NULL,
  `opening_time` TIME DEFAULT NULL,
  `closing_time` TIME DEFAULT NULL,
  `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: access_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_log_user` (`user_id`),
  KEY `idx_log_date` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: rep_requests (Replacement/Deactivation requests)
-- ============================================================
CREATE TABLE IF NOT EXISTS `rep_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rep_user_id` INT(11) NOT NULL,
  `request_type` ENUM('replacement','password_reset') NOT NULL DEFAULT 'replacement',
  `nominee_student_id` INT(11) DEFAULT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_request_rep` (`rep_user_id`),
  KEY `fk_request_nominee` (`nominee_student_id`),
  CONSTRAINT `fk_request_rep` FOREIGN KEY (`rep_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_request_nominee` FOREIGN KEY (`nominee_student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


COMMIT;
