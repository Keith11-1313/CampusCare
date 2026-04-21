<?php
/**
 * CampusCare - Session Management
 * Handles session initialization, CSRF tokens, and flash messages
 */

require_once __DIR__ . '/../config/config.php';

// Configure session settings before starting
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
<<<<<<< HEAD
// Auto-detect HTTPS for secure cookies
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
=======
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Session timeout check — only for logged-in users
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['flash_error'] = 'Your session has expired. Please log in again.';
}

// Only track activity for logged-in users
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

<<<<<<< HEAD

=======
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
/**
 * Generate or retrieve CSRF token
 */
function getCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from form submission
 */
function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output hidden CSRF token input field for forms
 */
function csrfField()
{
    echo '<input type="hidden" name="csrf_token" value="' . getCSRFToken() . '">';
}

/**
 * Set a flash message (displayed once on next page load)
 */
function setFlashMessage($type, $message)
{
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a flash message
 */
function getFlashMessage($type)
{
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Check if a flash message exists
 */
function hasFlashMessage($type)
{
    return isset($_SESSION['flash_' . $type]);
}
