<?php
/**
 * CampusCare - Sidebar Navigation
 * Role-aware sidebar with navigation links
 */

$userRole = $_SESSION['user_role'] ?? '';
?>

<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="cc-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center px-3 py-3">
            <img src="<?php echo BASE_URL; ?>/assets/logo-main-w.png" alt="<?php echo APP_NAME; ?>" style="width:28px;height:28px;object-fit:contain;" class="me-2">
            <div>
                <h6 class="text-white mb-0 fw-bold"><?php echo APP_NAME; ?></h6>
                <small class="text-white-50" style="font-size: 0.65rem;">Clinic Management</small>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($userRole === 'admin'): ?>
        <!-- Admin Navigation -->
        <div class="sidebar-section-title">Main</div>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="sidebar-link <?php echo isActivePage('dashboard.php'); ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        <div class="sidebar-section-title">Management</div>
        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="sidebar-link <?php echo isActivePage('users.php'); ?>">
            <i class="bi bi-people"></i><span>User Management</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/programs.php" class="sidebar-link <?php echo isActivePage('programs.php'); ?>">
            <i class="bi bi-mortarboard"></i><span>Programs</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/year_levels.php" class="sidebar-link <?php echo isActivePage('year_levels.php'); ?>">
            <i class="bi bi-layers"></i><span>Year Levels</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/students.php" class="sidebar-link <?php echo isActivePage('students.php'); ?>">
            <i class="bi bi-person-badge"></i><span>Student Records</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/archive.php" class="sidebar-link <?php echo isActivePage('archive.php'); ?>">
            <i class="bi bi-archive"></i><span>Archived Records</span>
        </a>

        <div class="sidebar-section-title">Analytics</div>
        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="sidebar-link <?php echo isActivePage('reports.php'); ?>">
            <i class="bi bi-graph-up"></i><span>Reports</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/access_logs.php" class="sidebar-link <?php echo isActivePage('access_logs.php'); ?>">
            <i class="bi bi-shield-check"></i><span>Access Logs</span>
        </a>

        <?php
elseif ($userRole === 'nurse'): ?>
        <!-- Nurse/Staff Navigation -->
        <div class="sidebar-section-title">Main</div>
        <a href="<?php echo BASE_URL; ?>/nurse/dashboard.php" class="sidebar-link <?php echo isActivePage('dashboard.php'); ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        <div class="sidebar-section-title">Patient Care</div>
        <a href="<?php echo BASE_URL; ?>/nurse/new_visit.php" class="sidebar-link <?php echo isActivePage('new_visit.php'); ?>">
            <i class="bi bi-plus-circle"></i><span>New Visit</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/nurse/visits.php" class="sidebar-link <?php echo isActivePage('visits.php'); ?>">
            <i class="bi bi-clipboard2-pulse"></i><span>Visit History</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/nurse/students.php" class="sidebar-link <?php echo isActivePage(['students.php', 'student_profile.php']); ?>">
            <i class="bi bi-person-badge"></i><span>Student Records</span>
        </a>

        <div class="sidebar-section-title">Analytics</div>
        <a href="<?php echo BASE_URL; ?>/nurse/reports.php" class="sidebar-link <?php echo isActivePage('reports.php'); ?>">
            <i class="bi bi-graph-up"></i><span>Reports & Analytics</span>
        </a>

        <div class="sidebar-section-title">Public Information</div>
        <a href="<?php echo BASE_URL; ?>/nurse/content.php?tab=announcements" class="sidebar-link <?php echo isActivePage('content.php'); ?>">
            <i class="bi bi-megaphone"></i><span>Manage Content</span>
        </a>

        <?php
elseif ($userRole === 'rep'): ?>
        <!-- Class Representative Navigation -->
        <div class="sidebar-section-title">Main</div>
        <a href="<?php echo BASE_URL; ?>/rep/dashboard.php" class="sidebar-link <?php echo isActivePage('dashboard.php'); ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        <div class="sidebar-section-title">Students</div>
        <a href="<?php echo BASE_URL; ?>/rep/students.php" class="sidebar-link <?php echo isActivePage('students.php'); ?>">
            <i class="bi bi-person-lines-fill"></i><span>Manage Students</span>
        </a>
        <?php
endif; ?>
    </nav>
</aside>

<!-- Main Content Wrapper -->
<main class="cc-main-content">
