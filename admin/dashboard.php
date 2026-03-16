<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$db = Database::getInstance();
$user = getCurrentUser();

// Dashboard statistics
$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM students WHERE status = 'active'");
$totalVisitsToday = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = CURDATE()");
$totalVisitsMonth = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())");
$activeUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active'");

// Recent visits
$recentVisits = $db->fetchAll(
    "SELECT v.*, s.student_id as sid, s.first_name, s.last_name, 
            u.first_name as nurse_first, u.last_name as nurse_last
     FROM visits v
     JOIN students s ON v.student_id = s.id
     LEFT JOIN users u ON v.attended_by = u.id
     ORDER BY v.visit_date DESC LIMIT 10"
);

// Top complaints this month
$topComplaints = $db->fetchAll(
    "SELECT complaint, COUNT(*) as count FROM visits 
     WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())
     GROUP BY complaint ORDER BY count DESC LIMIT 5"
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div>
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p class="text-muted mb-0">Welcome back, <?php echo e($user['first_name']); ?>! Here's an overview of clinic activity.</p>
    </div>
    <div class="text-muted small mt-2"><?php echo date('l, F d, Y'); ?></div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-primary animate-fade-in">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Registered Students</div>
                    <div class="stat-value"><?php echo number_format($totalStudents); ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-secondary animate-fade-in animate-delay-1">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Visits Today</div>
                    <div class="stat-value"><?php echo number_format($totalVisitsToday); ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent animate-fade-in animate-delay-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Visits This Month</div>
                    <div class="stat-value"><?php echo number_format($totalVisitsMonth); ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-danger animate-fade-in animate-delay-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Active Users</div>
                    <div class="stat-value"><?php echo number_format($activeUsers); ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Visits -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Recent Visits</span>
                <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-sm btn-outline-primary">View Reports</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Complaint</th>
                                <th>Date</th>
                                <th>Attended By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentVisits)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No visits recorded yet.</td></tr>
                            <?php
else: ?>
                            <?php foreach ($recentVisits as $visit): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo e($visit['first_name'] . ' ' . $visit['last_name']); ?></div>
                                    <small class="text-muted"><?php echo e($visit['sid']); ?></small>
                                </td>
                                <td><?php echo truncate($visit['complaint'], 40); ?></td>
                                <td><small><?php echo formatDateTime($visit['visit_date'], 'M d, h:i A'); ?></small></td>
                                <td><small><?php echo e($visit['nurse_first'] . ' ' . $visit['nurse_last']); ?></small></td>
                                <td><?php echo statusBadge($visit['status']); ?></td>
                            </tr>
                            <?php
    endforeach; ?>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Complaints -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart-fill me-2"></i>Top Complaints This Month</div>
            <div class="card-body">
                <?php if (empty($topComplaints)): ?>
                <div class="empty-state py-3"><i class="bi bi-bar-chart"></i><p class="small">No data this month.</p></div>
                <?php
else: ?>
                <?php foreach ($topComplaints as $c): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="small fw-500"><?php echo truncate($c['complaint'], 30); ?></div>
                    <span class="badge bg-primary rounded-pill"><?php echo $c['count']; ?></span>
                </div>
                <?php
    endforeach; ?>
                <?php
endif; ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-lightning-fill me-2"></i>Quick Actions</div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-people me-2"></i>Manage Users
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-graph-up me-2"></i>View Reports
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/access_logs.php" class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-shield-check me-2"></i>Access Logs
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/archive.php" class="btn btn-outline-primary btn-sm w-100 text-start">
                    <i class="bi bi-archive me-2"></i>Archived Records
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
