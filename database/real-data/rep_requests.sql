-- ============================================================
-- Table: rep_requests (Replacement/Deactivation requests)
-- ============================================================
CREATE TABLE IF NOT EXISTS `rep_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rep_user_id` INT(11) NOT NULL,
  `nominee_student_id` INT(11) NOT NULL,
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
