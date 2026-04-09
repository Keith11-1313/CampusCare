<?php
$pageTitle = 'Class Representative Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireRole('rep');
$db = Database::getInstance();
$user = getCurrentUser();

// Class Representative sees only students from their assigned program/year/section
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

// Total students
$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM students s $whereClause", $params);



// Gender distribution for doughnut
$genderDist = $db->fetchAll(
    "SELECT s.gender, COUNT(*) as count FROM students s $whereClause GROUP BY s.gender ORDER BY count DESC", $params
);

// Blood type distribution for bar chart
$bloodTypes = $db->fetchAll(
    "SELECT s.blood_type, COUNT(*) as count FROM students s 
     $whereClause AND s.blood_type IS NOT NULL AND s.blood_type != '' 
     GROUP BY s.blood_type ORDER BY count DESC", $params
);

// Recent requests by this rep
$myRequests = $db->fetchAll(
    "SELECT cr.*, 
            s.first_name as nominee_fname, s.last_name as nominee_lname
     FROM current_requests cr 
     LEFT JOIN students s ON cr.nominee_student_id=s.id
     WHERE cr.rep_user_id=? 
     ORDER BY cr.created_at DESC LIMIT 3",
[$user['id']]
);

// Recently added students
$recentAdded = $db->fetchAll(
    "SELECT s.*, p.code as program_code, yl.name as year_level_name 
     FROM students s 
     LEFT JOIN programs p ON s.program_id=p.id 
     LEFT JOIN year_levels yl ON s.year_level_id=yl.id 
     $whereClause ORDER BY s.created_at DESC LIMIT 5", $params
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
    <p class="text-muted mb-0">Welcome, <?php echo e($user['first_name']); ?>! You manage students in <?php echo e(($user['program_code'] ?? '') . ' ' . ($user['year_level_name'] ?? '') . ' Sec. ' . ($user['assigned_section'] ?? '')); ?>.</p>
</div>



<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-12 col-xl-12">
        <div class="stat-card stat-card-primary animate-fade-in">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Students</div>
                    <div class="stat-value"><?php echo $totalStudents; ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <!-- Recently Added Students Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-clock-history me-2"></i>Recently Added Students</span>
                <a href="students.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Student ID</th><th>Name</th><th>Gender</th><th>Blood Type</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAdded)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No students found.</td></tr>
                            <?php
else:
    foreach ($recentAdded as $s): ?>
                            <tr>
                                <td><code><?php echo e($s['student_id']); ?></code></td>
                                <td class="fw-semibold"><?php echo e($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                <td><?php echo e($s['gender']); ?></td>
                                <td><?php echo e($s['blood_type'] ?? '—'); ?></td>
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
        <!-- Gender Distribution Doughnut -->
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2"></i>Gender Distribution</div>
            <div class="card-body">
                <?php if (empty($genderDist)): ?>
                <div class="empty-state py-3"><i class="bi bi-pie-chart"></i><p class="small">No data.</p></div>
                <?php
else: ?>
                <div class="chart-container-sm"><canvas id="genderChart"></canvas></div>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Blood Type Bar Chart -->
    <div class="col-lg-8">
        <?php if (!empty($bloodTypes)): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-droplet-fill me-2"></i>Blood Type Distribution</div>
            <div class="card-body">
                <div class="chart-container-sm"><canvas id="bloodTypeChart"></canvas></div>
            </div>
        </div>
        <?php
endif; ?>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- My Requests -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-inbox me-2"></i>My Requests</span>
                <a href="request_change.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($myRequests)): ?>
                <div class="empty-state py-3"><i class="bi bi-inbox"></i><p class="small">No requests submitted.</p></div>
                <?php
else: ?>
                <?php foreach ($myRequests as $r):
        $badgeClass = $r['status'] === 'pending' ? 'bg-warning text-dark' : ($r['status'] === 'approved' ? 'bg-success' : 'bg-danger');
        $typeLabel = $r['request_type'] === 'password_reset' ? 'Password Reset' : 'Replacement';
?>
                <div class="request-status-item">
                    <div>
                        <div class="small fw-semibold"><?php echo e($typeLabel); ?></div>
                        <small class="text-muted"><?php echo formatDateTime($r['created_at'], 'M d, Y'); ?></small>
                    </div>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($r['status']); ?></span>
                </div>
                <?php
    endforeach; ?>
                <?php
endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-lightning-fill me-2"></i>Quick Actions</div>
            <div class="card-body">
                <a href="students.php?action=add" class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-person-plus me-2"></i>Add Student</a>
                <a href="students.php" class="btn btn-outline-primary btn-sm w-100 mb-2 text-start">
                    <i class="bi bi-people me-2"></i>View Students</a>
                <a href="request_change.php" class="btn btn-outline-primary btn-sm w-100 text-start">
                    <i class="bi bi-inbox me-2"></i>Requests</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // --- Gender Doughnut ---
    <?php if (!empty($genderDist)): ?>
    const genderData = <?php echo json_encode($genderDist); ?>;
    const genderColors = { 'Male': '#005a9c', 'Female': '#e91e8c', 'Other': '#6b7c93' };
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: genderData.map(d => d.gender),
            datasets: [{
                data: genderData.map(d => d.count),
                backgroundColor: genderData.map(d => genderColors[d.gender] || '#6b7c93'),
                borderWidth: 2, borderColor: '#fff', borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, usePointStyle: true, pointStyle: 'rectRounded' } } },
            cutout: '55%'
        }
    });
    <?php
endif; ?>

    // --- Blood Type Bar Chart ---
    <?php if (!empty($bloodTypes)): ?>
    const btData = <?php echo json_encode($bloodTypes); ?>;
    const btColors = ['#c0392b','#e74c3c','#27ae60','#2ecc71','#005a9c','#2980b9','#f39c12','#e67e22'];
    new Chart(document.getElementById('bloodTypeChart'), {
        type: 'bar',
        data: {
            labels: btData.map(d => d.blood_type),
            datasets: [{
                label: 'Students',
                data: btData.map(d => d.count),
                backgroundColor: btColors.slice(0, btData.length),
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php
endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
