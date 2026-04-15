<?php
$pageTitle = 'Nurse Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();
$user = getCurrentUser();

// Base stats
$todayVisits = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE DATE(visit_date)=CURDATE()");
$monthVisits = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE MONTH(visit_date)=MONTH(CURDATE()) AND YEAR(visit_date)=YEAR(CURDATE())");
$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM students WHERE status='active'");
$followUps = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE status='Follow-up' AND follow_up_date >= CURDATE()");

// Today vs yesterday comparison
$yesterdayVisits = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$todayDiff = $todayVisits - $yesterdayVisits;

// Referred cases this month
$referredMonth = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE status='Referred' AND MONTH(visit_date)=MONTH(CURDATE()) AND YEAR(visit_date)=YEAR(CURDATE())");

// Overdue follow-ups
$overdueFollowUps = $db->fetchAll(
    "SELECT v.*, s.student_id as sid, s.first_name, s.last_name 
     FROM visits v JOIN students s ON v.student_id=s.id 
     WHERE v.status='Follow-up' AND v.follow_up_date < CURDATE() 
     ORDER BY v.follow_up_date ASC LIMIT 5"
);
$overdueCount = count($overdueFollowUps);

// Today's visits table
$recentVisits = $db->fetchAll(
    "SELECT v.*, s.student_id as sid, s.first_name, s.last_name 
     FROM visits v JOIN students s ON v.student_id=s.id 
     WHERE DATE(v.visit_date)=CURDATE() ORDER BY v.visit_date DESC LIMIT 2"
);

// Upcoming follow-ups
$upcomingFollowUps = $db->fetchAll(
    "SELECT v.*, s.student_id as sid, s.first_name, s.last_name 
     FROM visits v JOIN students s ON v.student_id=s.id 
     WHERE v.status='Follow-up' AND v.follow_up_date >= CURDATE() 
     ORDER BY v.follow_up_date ASC LIMIT 5"
);

// Visits per day (this week Mon-Sun) for bar chart
$weeklyVisits = $db->fetchAll(
    "SELECT DATE(visit_date) as day, COUNT(*) as count FROM visits 
     WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(visit_date) ORDER BY day"
);

// Top 5 complaint categories this month for doughnut
$monthComplaints = $db->fetchAll(
    "SELECT complaint_category, COUNT(*) as count FROM visits 
     WHERE MONTH(visit_date)=MONTH(CURDATE()) AND YEAR(visit_date)=YEAR(CURDATE())
     GROUP BY complaint_category ORDER BY count DESC LIMIT 5"
);

// Visit status distribution this month
$visitStatuses = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM visits 
     WHERE MONTH(visit_date)=MONTH(CURDATE()) AND YEAR(visit_date)=YEAR(CURDATE())
     GROUP BY status ORDER BY count DESC"
);

// Frequent visitors (3+ visits this month)
$frequentVisitors = $db->fetchAll(
    "SELECT s.student_id as sid, s.first_name, s.last_name, COUNT(v.id) as visit_count
     FROM visits v JOIN students s ON v.student_id=s.id
     WHERE MONTH(v.visit_date)=MONTH(CURDATE()) AND YEAR(v.visit_date)=YEAR(CURDATE())
     GROUP BY s.id HAVING visit_count >= 3 ORDER BY visit_count DESC LIMIT 5"
);

// ── Health Records Overview charts ──
// Top 5 allergens across all students
$topAllergens = $db->fetchAll(
    "SELECT allergen, COUNT(*) as count FROM allergies
     GROUP BY allergen ORDER BY count DESC LIMIT 5"
);

// Top chronic conditions across all students
$topConditions = $db->fetchAll(
    "SELECT condition_name, COUNT(*) as count FROM chronic_conditions
     GROUP BY condition_name ORDER BY count DESC LIMIT 5"
);

// Top 5 vaccines administered
$topVaccines = $db->fetchAll(
    "SELECT vaccine_name, COUNT(*) as count FROM immunizations
     GROUP BY vaccine_name ORDER BY count DESC LIMIT 5"
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div>
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p class="text-muted mb-0">Welcome, <?php echo e($user['first_name']); ?>! Here's today's clinic overview.</p>
    </div>
    <div class="text-muted small mt-2"><?php echo date('l, F d, Y'); ?></div>
</div>


<!-- Stat Cards -->
<style>
    .stat-card-link {
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .stat-card-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .12) !important;
    }
</style>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <a href="<?php echo BASE_URL; ?>/nurse/visits.php" class="text-decoration-none h-100 d-flex flex-column">
            <div class="stat-card stat-card-primary animate-fade-in stat-card-link">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Today's Visits</div>
                        <div class="stat-value"><?php echo $todayVisits; ?></div>
                        <div
                            class="stat-extra <?php echo $todayDiff > 0 ? 'up' : ($todayDiff < 0 ? 'down' : 'neutral'); ?>">
                            <i
                                class="bi bi-arrow-<?php echo $todayDiff > 0 ? 'up' : ($todayDiff < 0 ? 'down' : 'right'); ?>"></i>
                            <?php echo abs($todayDiff); ?> vs yesterday
                        </div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="<?php echo BASE_URL; ?>/nurse/visits.php" class="text-decoration-none h-100 d-flex flex-column">
            <div class="stat-card stat-card-secondary animate-fade-in animate-delay-1 stat-card-link">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Monthly Visits</div>
                        <div class="stat-value"><?php echo $monthVisits; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="<?php echo BASE_URL; ?>/nurse/visits.php?status=Referred"
            class="text-decoration-none h-100 d-flex flex-column">
            <div class="stat-card stat-card-accent animate-fade-in animate-delay-2 stat-card-link">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Referred Cases</div>
                        <div class="stat-value"><?php echo $referredMonth; ?></div>
                        <div class="stat-extra neutral"><i class="bi bi-calendar3"></i> This month</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-hospital-fill"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="<?php echo BASE_URL; ?>/nurse/visits.php?status=Follow-up"
            class="text-decoration-none h-100 d-flex flex-column">
            <div class="stat-card stat-card-danger animate-fade-in animate-delay-3 stat-card-link">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Pending Follow-ups</div>
                        <div class="stat-value"><?php echo $followUps; ?></div>
                        <?php if ($overdueCount > 0): ?>
                            <div class="stat-extra danger"><i
                                    class="bi bi-exclamation-circle"></i><?php echo $overdueCount; ?>
                                overdue</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Today's Visits</span>
                <a href="<?php echo BASE_URL; ?>/nurse/new_visit.php" class="btn btn-sm btn-primary"><i
                        class="bi bi-plus-lg me-1"></i>New Visit</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Complaint</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentVisits)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No visits today yet.</td>
                                </tr>
                            <?php else:
                                foreach ($recentVisits as $v): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($v['first_name'] . ' ' . $v['last_name']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo e($v['sid']); ?></small>
                                        </td>
                                        <td><?php echo truncate($v['complaint'], 35); ?></td>
                                        <td><small><?php echo formatDateTime($v['visit_date'], 'h:i A'); ?></small></td>
                                        <td><?php echo statusBadge($v['status']); ?></td>
                                    </tr>
                                <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning-fill me-2"></i>Quick Actions</div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>/nurse/new_visit.php"
                    class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-plus-circle me-2"></i>Record New Visit</a>
                <a href="<?php echo BASE_URL; ?>/nurse/students.php"
                    class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-search me-2"></i>Search Students</a>
                <a href="<?php echo BASE_URL; ?>/nurse/visits.php"
                    class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-clipboard2-pulse me-2"></i>Visit History</a>
                <a href="<?php echo BASE_URL; ?>/nurse/reports.php"
                    class="btn btn-outline-primary btn-sm w-100 text-start">
                    <i class="bi bi-graph-up me-2"></i>Reports &amp; Analytics</a>
            </div>
        </div>

    </div>
</div>

<!-- Today's Visits + Complaints Row -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Visits — This Week</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="weeklyChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 d-flex">
        <div class="card flex-fill">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2"></i>Complaints This Month</div>
            <div class="card-body">
                <?php if (empty($monthComplaints)): ?>
                    <div class="empty-state py-3"><i class="bi bi-bar-chart"></i>
                        <p class="small">No data this month.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container"><canvas id="complaintsChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Health Records Overview Row -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-exclamation-triangle me-2"></i>Top Allergens</div>
            <div class="card-body">
                <?php if (empty($topAllergens)): ?>
                    <div class="empty-state py-3"><i class="bi bi-bar-chart"></i>
                        <p class="small">No allergy data.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container"><canvas id="allergensChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-shield-plus me-2"></i>Top Vaccines</div>
            <div class="card-body">
                <?php if (empty($topVaccines)): ?>
                    <div class="empty-state py-3"><i class="bi bi-bar-chart"></i>
                        <p class="small">No immunization data.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container"><canvas id="vaccinesChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-diagram-3-fill me-2"></i>Visit Status This Month</div>
            <div class="card-body">
                <?php if (empty($visitStatuses)): ?>
                    <div class="empty-state py-3"><i class="bi bi-diagram-3"></i>
                        <p class="small">No data.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container-sm"><canvas id="statusChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-heart-pulse me-2"></i>Top Conditions</div>
            <div class="card-body">
                <?php if (empty($topConditions)): ?>
                    <div class="empty-state py-3"><i class="bi bi-diagram-3"></i>
                        <p class="small">No condition data.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($topConditions as $tc): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div class="fw-semibold small"><?php echo e($tc['condition_name']); ?></div>
                                <span class="badge bg-primary rounded-pill"><?php echo $tc['count']; ?>
                                    student<?php echo $tc['count'] > 1 ? 's' : ''; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Follow-ups -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-event me-2"></i>Upcoming Follow-ups</div>
            <div class="card-body py-2">
                <?php if (empty($upcomingFollowUps)): ?>
                    <div class="empty-state py-3"><i class="bi bi-calendar-check"></i>
                        <p class="small">No upcoming follow-ups.</p>
                    </div>
                <?php else:
                    foreach ($upcomingFollowUps as $f): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <div>
                                <div class="fw-semibold small"><?php echo e($f['first_name'] . ' ' . $f['last_name']); ?></div>
                                <small class="text-muted"><?php echo truncate($f['complaint'], 25); ?></small>
                            </div>
                            <span
                                class="badge bg-warning text-dark"><?php echo formatDate($f['follow_up_date'], 'M d'); ?></span>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Frequent Visitors -->
<?php if (!empty($frequentVisitors)): ?>
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-person-exclamation me-2"></i>Frequent Visitors This Month</div>
                <div class="card-body py-2">
                    <?php foreach ($frequentVisitors as $fv): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <div>
                                <div class="fw-semibold small"><?php echo e($fv['first_name'] . ' ' . $fv['last_name']); ?>
                                </div>
                                <small class="text-muted"><?php echo e($fv['sid']); ?></small>
                            </div>
                            <span class="badge bg-danger rounded-pill"><?php echo $fv['visit_count']; ?> visits</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartColors = ['#005a9c', '#0ea5e9', '#27ae60', '#f39c12', '#c0392b', '#8e44ad'];

        // --- Weekly Visits Bar Chart ---
        const weekData = <?php echo json_encode($weeklyVisits); ?>;
        const last7 = [];
        for (let i = 6; i >= 0; i--) {
            const d = new Date(); d.setDate(d.getDate() - i);
            const key = d.toISOString().slice(0, 10);
            const found = weekData.find(r => r.day === key);
            last7.push({ day: key, count: found ? parseInt(found.count) : 0 });
        }
        const dayLabels = last7.map(d => {
            const dt = new Date(d.day + 'T00:00:00');
            return dt.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        });
        new Chart(document.getElementById('weeklyChart'), {
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
        <?php if (!empty($monthComplaints)): ?>
            const compData = <?php echo json_encode($monthComplaints); ?>;
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

        // --- Top Allergens Horizontal Bar ---
        <?php if (!empty($topAllergens)): ?>
            const allergenData = <?php echo json_encode($topAllergens); ?>;
            new Chart(document.getElementById('allergensChart'), {
                type: 'bar',
                data: {
                    labels: allergenData.map(d => d.allergen.length > 20 ? d.allergen.substring(0, 20) + '…' : d.allergen),
                    datasets: [{
                        label: 'Students',
                        data: allergenData.map(d => d.count),
                        backgroundColor: 'rgba(231, 76, 60, 0.7)',
                        borderColor: '#c0392b',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        <?php endif; ?>


        // --- Top Vaccines Horizontal Bar ---
        <?php if (!empty($topVaccines)): ?>
            const vaccineData = <?php echo json_encode($topVaccines); ?>;
            new Chart(document.getElementById('vaccinesChart'), {
                type: 'bar',
                data: {
                    labels: vaccineData.map(d => d.vaccine_name.length > 20 ? d.vaccine_name.substring(0, 20) + '…' : d.vaccine_name),
                    datasets: [{
                        label: 'Doses',
                        data: vaccineData.map(d => d.count),
                        backgroundColor: 'rgba(39, 174, 96, 0.7)',
                        borderColor: '#27ae60',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        <?php endif; ?>
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>