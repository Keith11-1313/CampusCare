<?php
/**
 * CampusCare - Class Representative Dashboard
 */
$pageTitle = 'Rep Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireRole('rep');
$db = Database::getInstance();
$user = getCurrentUser();

// Rep sees only students from their assigned program/year/section
$programId = $user['assigned_program_id'] ?? null;
$yearLevelId = $user['assigned_year_level_id'] ?? null;
$section = $user['assigned_section'] ?? null;

$whereClause = "WHERE s.status='active'";
$params = [];
if ($programId) {
    $whereClause .= " AND s.program_id=?";
    $params[] = $programId;
}
if ($yearLevelId) {
    $whereClause .= " AND s.year_level_id=?";
    $params[] = $yearLevelId;
}
if ($section) {
    $whereClause .= " AND s.section=?";
    $params[] = $section;
}

$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM students s $whereClause", $params);
$recentAdded = $db->fetchAll("SELECT s.*, p.code as program_code, yl.name as year_level_name FROM students s LEFT JOIN programs p ON s.program_id=p.id LEFT JOIN year_levels yl ON s.year_level_id=yl.id $whereClause ORDER BY s.created_at DESC LIMIT 5", $params);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
<p class="text-muted mb-0">Welcome, <?php echo e($user['first_name']); ?>! You manage students in <?php echo e(($user['program_code'] ?? '') . ' ' . ($user['year_level_name'] ?? '') . ' Sec. ' . ($user['assigned_section'] ?? '')); ?>.</p></div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-6"><div class="stat-card stat-card-primary animate-fade-in"><div class="d-flex justify-content-between"><div><div class="stat-label">Students</div><div class="stat-value"><?php echo $totalStudents; ?></div></div><div class="stat-icon"><i class="bi bi-people-fill"></i></div></div></div></div>
    <div class="col-sm-6 col-xl-6"><div class="stat-card stat-card-secondary animate-fade-in animate-delay-2"><div class="d-flex justify-content-between"><div><div class="stat-label">Assigned</div><div class="stat-value fs-sm"><?php echo e(($user['program_code'] ?? 'N/A') . ' ' . ($user['year_level_name'] ?? '')); ?></div></div><div class="stat-icon"><i class="bi bi-mortarboard-fill"></i></div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-clock-history me-2"></i>Recently Added Students</span><a href="students.php" class="btn btn-sm btn-outline-primary">View All</a></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Student ID</th><th>Name</th><th>Gender</th><th>Blood Type</th></tr></thead>
        <tbody>
        <?php if (empty($recentAdded)): ?><tr><td colspan="4" class="text-center text-muted py-4">No students found.</td></tr>
        <?php
else:
    foreach ($recentAdded as $s): ?>
        <tr><td><code><?php echo e($s['student_id']); ?></code></td><td class="fw-semibold"><?php echo e($s['first_name'] . ' ' . $s['last_name']); ?></td><td><?php echo e($s['gender']); ?></td><td><?php echo e($s['blood_type'] ?? '—'); ?></td></tr>
        <?php
    endforeach;
endif; ?>
        </tbody></table></div></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><i class="bi bi-lightning-fill me-2"></i>Quick Actions</div>
        <div class="card-body">
            <a href="students.php?action=add" class="btn btn-outline-primary btn-sm w-100 mb-2 text-start"><i class="bi bi-person-plus me-2"></i>Add Student</a>
            <a href="students.php" class="btn btn-outline-primary btn-sm w-100 text-start"><i class="bi bi-people me-2"></i>View My Students</a>
        </div></div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
