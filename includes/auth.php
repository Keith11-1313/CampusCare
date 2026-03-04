<?php
/**
 * CampusCare - Authentication Helpers
 * Functions for login status, role checking, and user retrieval
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require user to be logged in; redirect to login if not
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        //setFlashMessage('error', 'Please log in to access this page.');
        //removed access denied message
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Require specific role(s); redirect if unauthorized
 * @param string|array $roles Single role or array of allowed roles
 */
function requireRole($roles)
{
    requireLogin();

    if (is_string($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['user_role'], $roles)) {
        //setFlashMessage('error', 'You do not have permission to access this page.');
        // Redirect to appropriate dashboard based on role
        $redirect = getDashboardUrl($_SESSION['user_role']);
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Get the current logged-in user's data
 */
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    // Cache user data in session to avoid repeated DB queries
    if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['id'] != $_SESSION['user_id']) {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT u.*, p.name as program_name, p.code as program_code, yl.name as year_level_name 
             FROM users u 
             LEFT JOIN programs p ON u.assigned_program_id = p.id
             LEFT JOIN year_levels yl ON u.assigned_year_level_id = yl.id
             WHERE u.id = ?",
        [$_SESSION['user_id']]
        );
        $_SESSION['user_data'] = $user;
    }

    return $_SESSION['user_data'];
}

/**
 * Get dashboard URL based on user role
 */
function getDashboardUrl($role)
{
    switch ($role) {
        case 'admin':
            return BASE_URL . '/admin/dashboard.php';
        case 'nurse':
            return BASE_URL . '/nurse/dashboard.php';
        case 'rep':
            return BASE_URL . '/rep/dashboard.php';
        default:
            return BASE_URL . '/login.php';
    }
}

/**
 * Log user action for audit trail
 */
function logAccess($userId, $action, $description = '')
{
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO access_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
    [$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']
    );
}

/**
 * Get user's display name
 */
function getUserDisplayName()
{
    if (isLoggedIn() && isset($_SESSION['user_data'])) {
        return $_SESSION['user_data']['first_name'] . ' ' . $_SESSION['user_data']['last_name'];
    }
    return 'Guest';
}

/**
 * Get user's role display name
 */
function getRoleDisplayName($role)
{
    $roles = [
        'admin' => 'Administrator',
        'nurse' => 'School Nurse/Staff',
        'rep' => 'Class Representative'
    ];
    return $roles[$role] ?? ucfirst($role);
}
