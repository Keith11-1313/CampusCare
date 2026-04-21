<?php
/**
 * CampusCare — Application Configuration
 * 
 * Copy this file to config.php and fill in your deployment settings.
 * IMPORTANT: Never commit config.php — it contains database credentials.
 * 
 * For InfinityFree hosting:
 *   - DB_HOST: usually sql306.infinityfree.com (check your panel)
 *   - DB_NAME: your assigned database name (e.g. if_12345678_campuscare)
 *   - DB_USER: your assigned database username
 *   - DB_PASS: your database password
 *   - BASE_URL: '' (empty string if app is in root) or '/subfolder' if in a subfolder
 */

// Database Configuration
define('DB_HOST', 'localhost');          // ← Replace with your host
define('DB_NAME', 'campuscare');         // ← Replace with your database name
define('DB_USER', 'root');              // ← Replace with your database username
define('DB_PASS', '');                  // ← Replace with your database password
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'CampusCare');
define('APP_TAGLINE', 'School Clinic Patient Information Record System');
define('APP_VERSION', '1.0.0');

// Base URL - adjust for deployment
// Local (XAMPP):     '/CampusCare'
// InfinityFree root: ''
// Subfolder:         '/subfolder'
define('BASE_URL', '');

// Absolute URL (used in emails where relative paths don't work)
$_cc_scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$_cc_host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('APP_URL', $_cc_scheme . '://' . $_cc_host . BASE_URL);

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
