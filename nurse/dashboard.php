<?php
$pageTitle = 'Nurse Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();
$user = getCurrentUser();

//declares data to be displayed in the dashboard
$todayVisits = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE DATE(visit_date)=CURDATE()");
$monthVisits = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE MONTH(visit_date)=MONTH(CURDATE()) AND YEAR(visit_date)=YEAR(CURDATE())");
$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM students WHERE status='active'");
$followUps = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE status='Follow-up' AND follow_up_date >= CURDATE()");

// Nurse Availability Ratio (ratio of nurses available today out of total active nurses)
$totalNurses = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='nurse' AND status='active'");
$availableToday = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='nurse' AND status='active' AND DATE(last_login) = CURDATE()");
$availabilityRatio = ($totalNurses > 0) ? round(($availableToday / $totalNurses) * 100) : 0;

$recentVisits = $db->fetchAll(
    "SELECT v.*, s.student_id as sid, s.first_name, s.last_name 
     FROM visits v JOIN students s ON v.student_id=s.id 
     WHERE DATE(v.visit_date)=CURDATE() ORDER BY v.visit_date DESC LIMIT 10"
);

$upcomingFollowUps = $db->fetchAll(
    "SELECT v.*, s.student_id as sid, s.first_name, s.last_name 
     FROM visits v JOIN students s ON v.student_id=s.id 
     WHERE v.status='Follow-up' AND v.follow_up_date >= CURDATE() 
     ORDER BY v.follow_up_date ASC LIMIT 5"
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
    <p class="text-muted mb-0">Welcome, <?php echo e($user['first_name']); ?>! Here's today's clinic overview.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-primary animate-fade-in">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Today's Visits</div>
                    <div class="stat-value"><?php echo $todayVisits; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-secondary animate-fade-in animate-delay-1">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">This Month</div>
                    <div class="stat-value"><?php echo $monthVisits; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent animate-fade-in animate-delay-2">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Nurse Staffing</div>
                    <div class="stat-value"><?php echo $availabilityRatio; ?>%</div>
                    <div class="small mt-1 opacity-75">
                        <span class="fw-bold"><?php echo $availableToday; ?></span> / <?php echo $totalNurses; ?> Available
                    </div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-danger animate-fade-in animate-delay-3">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Pending Follow-ups</div>
                    <div class="stat-value"><?php echo $followUps; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center"><span><i
                        class="bi bi-clock-history me-2"></i>Today's Visits</span><a
                    href="<?php echo BASE_URL; ?>/nurse/new_visit.php" class="btn btn-sm btn-primary"><i
                        class="bi bi-plus-lg me-1"></i>New Visit</a></div>
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
                                <?php
                            else:
                                foreach ($recentVisits as $v): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($v['first_name'] . ' ' . $v['last_name']); ?>
                                            </div><small class="text-muted"><?php echo e($v['sid']); ?></small>
                                        </td>
                                        <td><?php echo truncate($v['complaint'], 35); ?></td>
                                        <td><small><?php echo formatDateTime($v['visit_date'], 'h:i A'); ?></small></td>
                                        <td><?php echo statusBadge($v['status']); ?></td>
                                    </tr>
                                    <?php
                                endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-event me-2"></i>Upcoming Follow-ups</div>
            <div class="card-body">
                <?php if (empty($upcomingFollowUps)): ?>
                    <div class="empty-state py-3"><i class="bi bi-calendar-check"></i>
                        <p class="small">No upcoming follow-ups.</p>
                    </div>
                    <?php
                else:
                    foreach ($upcomingFollowUps as $f): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <div>
                                <div class="fw-semibold small"><?php echo e($f['first_name'] . ' ' . $f['last_name']); ?></div>
                                <small class="text-muted"><?php echo truncate($f['complaint'], 25); ?></small>
                            </div>
                            <span
                                class="badge bg-warning text-dark"><?php echo formatDate($f['follow_up_date'], 'M d'); ?></span>
                        </div>
                        <?php
                    endforeach;
                endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-lightning-fill me-2"></i>Quick Actions</div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>/nurse/new_visit.php"
                    class="btn btn-outline-primary btn-sm w-100 mb-2 text-start"><i
                        class="bi bi-plus-circle me-2"></i>Record New Visit</a>
                <a href="<?php echo BASE_URL; ?>/nurse/students.php"
                    class="btn btn-outline-primary btn-sm w-100 mb-2 text-start"><i class="bi bi-search me-2"></i>Search
                    Students</a>
                <a href="<?php echo BASE_URL; ?>/nurse/visits.php"
                    class="btn btn-outline-primary btn-sm w-100 mb-2 text-start"><i
                        class="bi bi-clipboard2-pulse me-2"></i>Visit History</a>
                <a href="<?php echo BASE_URL; ?>/nurse/reports.php"
                    class="btn btn-outline-primary btn-sm w-100 text-start"><i class="bi bi-graph-up me-2"></i>Reports &
                    Analytics</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>