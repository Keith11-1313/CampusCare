<?php
/**
 * CampusCare - Logout
 * Destroys session and logs the action
 */

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logAccess($_SESSION['user_id'], 'logout', getUserDisplayName() . ' logged out');
}

// Destroy session
session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/index.php');
exit;
