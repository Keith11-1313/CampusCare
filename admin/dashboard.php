<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$db = Database::getInstance();
$user = getCurrentUser();

// Dashboard statistics
$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM students WHERE status = 'active'");
$activeNurses = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'nurse' AND status = 'active'");
$totalVisitsMonth = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())");
$activeUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active'");

// Month-over-month comparison
$lastMonthVisits = $db->fetchColumn(
    "SELECT COUNT(*) FROM visits WHERE MONTH(visit_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(visit_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
);
$momChange = ($lastMonthVisits > 0) ? round((($totalVisitsMonth - $lastMonthVisits) / $lastMonthVisits) * 100) : 0;

// Pending requests count
$pendingRequests = $db->fetchColumn("SELECT COUNT(*) FROM current_requests WHERE status = 'pending'");

// Recent visits
$recentVisits = $db->fetchAll(
    "SELECT v.*, s.student_id as sid, s.first_name, s.last_name, 
            u.first_name as nurse_first, u.last_name as nurse_last
     FROM visits v
     JOIN students s ON v.student_id = s.id
     LEFT JOIN users u ON v.attended_by = u.id
     ORDER BY v.visit_date DESC LIMIT 10"
);

// Top complaints this month (for doughnut chart)
$topComplaints = $db->fetchAll(
    "SELECT complaint_category, COUNT(*) as count FROM visits 
     WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())
     GROUP BY complaint_category ORDER BY count DESC LIMIT 6"
);

// Visits per day (last 7 days) for bar chart
$visitsPerDay = $db->fetchAll(
    "SELECT DATE(visit_date) as day, COUNT(*) as count FROM visits 
     WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
     GROUP BY DATE(visit_date) ORDER BY day"
);

// Visit status distribution for doughnut
$visitStatuses = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM visits 
     WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())
     GROUP BY status ORDER BY count DESC"
);

// Recent activity feed (last 5 access logs)
$activityFeed = $db->fetchAll(
    "SELECT al.*, u.first_name, u.last_name, u.username 
     FROM access_logs al 
     LEFT JOIN users u ON al.user_id = u.id 
     ORDER BY al.created_at DESC LIMIT 5"
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
                    <div class="stat-label">Active Nurses</div>
                    <div class="stat-value"><?php echo number_format($activeNurses); ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-heart-pulse-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent animate-fade-in animate-delay-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Visits This Month</div>
                    <div class="stat-value"><?php echo number_format($totalVisitsMonth); ?></div>
                    <?php if ($lastMonthVisits > 0): ?>
                    <div class="stat-extra <?php echo $momChange > 0 ? 'up' : ($momChange < 0 ? 'down' : 'neutral'); ?>">
                        <i class="bi bi-arrow-<?php echo $momChange > 0 ? 'up' : ($momChange < 0 ? 'down' : 'right'); ?>"></i>
                        <?php echo abs($momChange); ?>% vs last month
                    </div>
                    <?php endif; ?>
                </div>
                <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?php if ($pendingRequests > 0): ?>
        <a href="<?php echo BASE_URL; ?>/admin/current_requests.php" class="text-decoration-none">
        <?php endif; ?>
        <div class="stat-card stat-card-danger animate-fade-in animate-delay-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-value"><?php echo number_format($pendingRequests); ?></div>
                    <?php if ($pendingRequests > 0): ?>
                    <div class="stat-extra danger"><i class="bi bi-arrow-right-circle"></i>Review now</div>
                    <?php endif; ?>
                </div>
                <div class="stat-icon"><i class="bi bi-inbox-fill"></i></div>
            </div>
        </div>
        <?php if ($pendingRequests > 0): ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Visits Per Day (Last 7 Days) -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Visits — Last 7 Days</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="dailyVisitsChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card">
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
        <!-- Top Complaints This Month -->
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2"></i>Top Complaints This Month</div>
            <div class="card-body">
                <?php if (empty($topComplaints)): ?>
                <div class="empty-state py-3"><i class="bi bi-bar-chart"></i><p class="small">No data this month.</p></div>
                <?php else: ?>
                <div class="chart-container"><canvas id="complaintsChart"></canvas></div>
                <?php endif; ?>
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

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Visit Status Distribution -->
        <div class="card">
            <div class="card-header"><i class="bi bi-diagram-3-fill me-2"></i>Visit Status This Month</div>
            <div class="card-body">
                <?php if (empty($visitStatuses)): ?>
                <div class="empty-state py-3"><i class="bi bi-diagram-3"></i><p class="small">No data this month.</p></div>
                <?php else: ?>
                <div class="chart-container-sm"><canvas id="statusChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity me-2"></i>Recent Activity</span>
                <a href="<?php echo BASE_URL; ?>/admin/access_logs.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($activityFeed)): ?>
                <div class="empty-state py-3"><i class="bi bi-activity"></i><p class="small">No recent activity.</p></div>
                <?php else: ?>
                <?php foreach ($activityFeed as $act): 
                    $isLogin = stripos($act['action'], 'login') !== false;
                    $isWarning = stripos($act['action'], 'reject') !== false || stripos($act['action'], 'deactivat') !== false;
                    $iconClass = $isWarning ? 'warning' : ($isLogin ? 'login' : 'action');
                    $iconName = $isWarning ? 'bi-exclamation-triangle' : ($isLogin ? 'bi-box-arrow-in-right' : 'bi-lightning');
                ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $iconClass; ?>"><i class="bi <?php echo $iconName; ?>"></i></div>
                    <div class="activity-content">
                        <div class="activity-text">
                            <strong><?php echo e(($act['first_name'] ?? '') . ' ' . ($act['last_name'] ?? '')); ?></strong>
                            — <?php echo e(truncate($act['action'], 30)); ?>
                        </div>
                        <div class="activity-time"><?php echo formatDateTime($act['created_at'], 'M d, h:i A'); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const chartColors = ['#005a9c','#0ea5e9','#27ae60','#f39c12','#c0392b','#8e44ad','#e67e22','#2c3e50'];

    // --- Daily Visits Bar Chart ---
    const dailyData = <?php echo json_encode($visitsPerDay); ?>;
    // Fill in missing days
    const last7 = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date(); d.setDate(d.getDate() - i);
        const key = d.toISOString().slice(0, 10);
        const found = dailyData.find(r => r.day === key);
        last7.push({ day: key, count: found ? parseInt(found.count) : 0 });
    }
    const dayLabels = last7.map(d => {
        const dt = new Date(d.day + 'T00:00:00');
        return dt.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    });
    new Chart(document.getElementById('dailyVisitsChart'), {
        type: 'bar',
        data: {
            labels: dayLabels,
            datasets: [{
                label: 'Visits',
                data: last7.map(d => d.count),
                backgroundColor: 'rgba(0, 90, 156, 0.7)',
                borderColor: '#005a9c',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // --- Complaints Doughnut ---
    <?php if (!empty($topComplaints)): ?>
    const compData = <?php echo json_encode($topComplaints); ?>;
    new Chart(document.getElementById('complaintsChart'), {
        type: 'doughnut',
        data: {
            labels: compData.map(d => d.complaint_category ? d.complaint_category.split(':')[0].substring(0, 25) : 'Other'),
            datasets: [{
                data: compData.map(d => d.count),
                backgroundColor: chartColors.slice(0, compData.length),
                borderWidth: 2, borderColor: '#fff', borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, usePointStyle: true, pointStyle: 'rectRounded' } } },
            cutout: '60%'
        }
    });
    <?php endif; ?>

    // --- Visit Status Doughnut ---
    <?php if (!empty($visitStatuses)): ?>
    const statusData = <?php echo json_encode($visitStatuses); ?>;
    const statusColors = { 'Completed': '#27ae60', 'Follow-up': '#f39c12', 'Referred': '#c0392b' };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(d => d.status),
            datasets: [{
                data: statusData.map(d => d.count),
                backgroundColor: statusData.map(d => statusColors[d.status] || '#6b7c93'),
                borderWidth: 2, borderColor: '#fff', borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, usePointStyle: true, pointStyle: 'rectRounded' } } },
            cutout: '55%'
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
