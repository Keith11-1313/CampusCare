<?php

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

// Base URL - adjust for deployment
define('BASE_URL', '/CampusCare');

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
