<?php
/**
 * CampusCare - Header Template
 * Common HTML head, CSS imports, and top navbar
 * 
 * Usage: Set $pageTitle before including this file
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME;
$currentUser = isLoggedIn() ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CampusCare - School Clinic Patient Information & Medicine Record System">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo-main-w.png">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body class="<?php echo isLoggedIn() ? 'has-sidebar' : ''; ?>">

<?php if (isLoggedIn()): ?>
<!-- Top Navbar for authenticated users -->
<nav class="navbar navbar-expand-lg navbar-dark cc-navbar fixed-top">
    <div class="container-fluid">
        <!-- Sidebar Toggle -->
        <button class="btn btn-link text-white me-2 sidebar-toggle d-lg-none" type="button" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo getDashboardUrl($currentUser['role']); ?>">
            <img src="<?php echo BASE_URL; ?>/assets/logo-main-w.png" alt="<?php echo APP_NAME; ?>" style="width:28px;height:28px;object-fit:contain;" class="me-2">
            <span class="fw-bold"><?php echo APP_NAME; ?></span>
        </a>

        <!-- Right Side -->
        <div class="d-flex align-items-center ms-auto">
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center" 
                        type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar me-2">
                        <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <div class="fw-semibold small"><?php echo e($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                        <div class="text-white-50" style="font-size: 0.7rem;"><?php echo getRoleDisplayName($currentUser['role']); ?></div>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                    <li><h6 class="dropdown-header"><?php echo e($currentUser['username']); ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/change_password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php" id="logoutLink"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<?php
endif; ?>
