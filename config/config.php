<?php
/**
 * CampusCare - Configuration File
 * Contains database credentials and application settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'campuscare');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'CampusCare');
define('APP_TAGLINE', 'School Clinic Patient Information Record System');
define('APP_VERSION', '1.0.0');

// Base URL - adjust if project is in a subdirectory
define('BASE_URL', '');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('SESSION_NAME', 'CAMPUSCARE_SESSION');

// File paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('LIBS_PATH', ROOT_PATH . '/libs');

// Timezone
date_default_timezone_set('Asia/Manila');
