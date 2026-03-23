<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

// Handle export requests
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require_once __DIR__ . '/../includes/export_pdf.php';
    exit;
}

// --- Filter parameters ---
$filterStartDate   = $_GET['start_date'] ?? '';
$filterEndDate     = $_GET['end_date'] ?? '';
$filterProgramId   = $_GET['program_id'] ?? '';
$filterYearLevelId = $_GET['year_level_id'] ?? '';
$filterSection     = trim($_GET['section'] ?? '');

// Dropdown data
$programs   = $db->fetchAll("SELECT id, code, name FROM programs WHERE status='active' ORDER BY code");
$yearLevels = $db->fetchAll("SELECT id, name FROM year_levels WHERE status='active' ORDER BY order_num");
$sections   = $db->fetchAll("SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section != '' ORDER BY section");

// Build dynamic WHERE clause for visits (joined with students)
$where = "1=1";
$params = [];
if ($filterStartDate) { $where .= " AND v.visit_date >= ?"; $params[] = $filterStartDate . ' 00:00:00'; }
if ($filterEndDate)   { $where .= " AND v.visit_date <= ?"; $params[] = $filterEndDate . ' 23:59:59'; }
if ($filterProgramId) { $where .= " AND s.program_id = ?";  $params[] = $filterProgramId; }
if ($filterYearLevelId) { $where .= " AND s.year_level_id = ?"; $params[] = $filterYearLevelId; }
if ($filterSection)   { $where .= " AND s.section = ?";     $params[] = $filterSection; }

// Simpler WHERE for visits-only queries (no student join yet)
$whereVisit = "1=1";
$paramsVisit = [];
if ($filterStartDate) { $whereVisit .= " AND v.visit_date >= ?"; $paramsVisit[] = $filterStartDate . ' 00:00:00'; }
if ($filterEndDate)   { $whereVisit .= " AND v.visit_date <= ?"; $paramsVisit[] = $filterEndDate . ' 23:59:59'; }

// For queries that need student join for program/year/section filters
$needsStudentFilter = $filterProgramId || $filterYearLevelId || $filterSection;

// Chart data: visits by month (last 12 months or filtered range)
$monthWhere = $where;
$monthParams = $params;
if (!$filterStartDate && !$filterEndDate) {
    $monthWhere .= " AND v.visit_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
}
$visitsByMonth = $db->fetchAll(
    "SELECT DATE_FORMAT(v.visit_date,'%Y-%m') as month, COUNT(*) as count 
     FROM visits v JOIN students s ON v.student_id=s.id
     WHERE $monthWhere
     GROUP BY month ORDER BY month",
    $monthParams
);

// Chart data: top 10 complaints
$topComplaints = $db->fetchAll(
    "SELECT v.complaint_category, COUNT(*) as count FROM visits v 
     JOIN students s ON v.student_id=s.id
     WHERE $where
     GROUP BY v.complaint_category ORDER BY count DESC LIMIT 10",
    $params
);

// Chart data: visits by program
$visitsByProgram = $db->fetchAll(
    "SELECT p.code, COUNT(v.id) as count FROM visits v 
     JOIN students s ON v.student_id=s.id 
     LEFT JOIN programs p ON s.program_id=p.id 
     WHERE $where
     GROUP BY p.code ORDER BY count DESC LIMIT 8",
    $params
);

// Visit status distribution (filtered)
$visitStatuses = $db->fetchAll(
    "SELECT v.status, COUNT(*) as count FROM visits v
     JOIN students s ON v.student_id=s.id
     WHERE $where
     GROUP BY v.status ORDER BY count DESC",
    $params
);


// Summary stats
$totalVisits = $db->fetchColumn(
    "SELECT COUNT(*) FROM visits v JOIN students s ON v.student_id=s.id WHERE $where", $params
);
$totalStudentsWithVisits = $db->fetchColumn(
    "SELECT COUNT(DISTINCT v.student_id) FROM visits v JOIN students s ON v.student_id=s.id WHERE $where", $params
);
$avgVisitsPerDay = $db->fetchColumn(
    "SELECT ROUND(COUNT(*)/GREATEST(DATEDIFF(MAX(v.visit_date),MIN(v.visit_date)),1),1) FROM visits v JOIN students s ON v.student_id=s.id WHERE $where", $params
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div><h1><i class="bi bi-graph-up me-2"></i>Reports & Analytics</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Reports</li></ol></nav></div>
    <div>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-filetype-pdf me-1"></i>Export PDF
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Start Date</label>
                <input type="date" class="form-control form-control-sm" name="start_date" value="<?php echo e($filterStartDate); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">End Date</label>
                <input type="date" class="form-control form-control-sm" name="end_date" value="<?php echo e($filterEndDate); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Program</label>
                <select class="form-select form-select-sm" name="program_id">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $filterProgramId == $p['id'] ? 'selected' : ''; ?>><?php echo e($p['code']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Year Level</label>
                <select class="form-select form-select-sm" name="year_level_id">
                    <option value="">All Year Levels</option>
                    <?php foreach ($yearLevels as $yl): ?>
                    <option value="<?php echo $yl['id']; ?>" <?php echo $filterYearLevelId == $yl['id'] ? 'selected' : ''; ?>><?php echo e($yl['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Section</label>
                <select class="form-select form-select-sm" name="section">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                    <option value="<?php echo e($sec['section']); ?>" <?php echo $filterSection == $sec['section'] ? 'selected' : ''; ?>><?php echo e($sec['section']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="reports.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel"><i class="bi bi-printer me-2 text-danger"></i>Export Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="GET">
                <input type="hidden" name="export" value="pdf">
                <!-- Carry current filters -->
                <input type="hidden" name="start_date" value="<?php echo e($filterStartDate); ?>">
                <input type="hidden" name="end_date" value="<?php echo e($filterEndDate); ?>">
                <input type="hidden" name="program_id" value="<?php echo e($filterProgramId); ?>">
                <input type="hidden" name="year_level_id" value="<?php echo e($filterYearLevelId); ?>">
                <input type="hidden" name="section" value="<?php echo e($filterSection); ?>">
                <div class="modal-body">
                    <p class="text-muted mb-3">Select the sections you want to include in the exported report.</p>
                    
                    <?php if ($filterStartDate || $filterEndDate || $filterProgramId || $filterYearLevelId || $filterSection): ?>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-funnel-fill me-1"></i><strong>Active Filters:</strong>
                        <?php if ($filterStartDate || $filterEndDate) echo ($filterStartDate ?: '…') . ' to ' . ($filterEndDate ?: '…') . ' '; ?>
                        <?php if ($filterProgramId) { $pName = array_filter($programs, fn($p) => $p['id'] == $filterProgramId); echo '• ' . e(reset($pName)['code'] ?? '') . ' '; } ?>
                        <?php if ($filterYearLevelId) { $ylName = array_filter($yearLevels, fn($y) => $y['id'] == $filterYearLevelId); echo '• ' . e(reset($ylName)['name'] ?? '') . ' '; } ?>
                        <?php if ($filterSection) echo '• Section ' . e($filterSection); ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="sections[]" value="summary" id="secSummary" checked>
                        <label class="form-check-label" for="secSummary">Summary Statistics</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="sections[]" value="visits_month" id="secVisitsMonth" checked>
                        <label class="form-check-label" for="secVisitsMonth">Visits by Month Chart</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="sections[]" value="visits_program" id="secVisitsProgram" checked>
                        <label class="form-check-label" for="secVisitsProgram">Visits by Program Chart</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="sections[]" value="top_complaints" id="secComplaints" checked>
                        <label class="form-check-label" for="secComplaints">Top Health Complaints (Chart &amp; Table)</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="sections[]" value="visit_records" id="secRecords" checked>
                        <label class="form-check-label" for="secRecords">Visit Records Table</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="sections[]" value="visit_status" id="secStatus" checked>
                        <label class="form-check-label" for="secStatus">Visit Status Distribution</label>
                    </div>


                    <hr class="my-3">
                    <label class="form-label small fw-semibold mb-1"><i class="bi bi-sort-down me-1"></i>Sort Visit Records by</label>
                    <select class="form-select form-select-sm" name="sort_by" id="exportSortBy">
                        <option value="date_desc">Date (Newest First)</option>
                        <option value="date_asc">Date (Oldest First)</option>
                        <option value="name_asc">Student Name (A–Z)</option>
                        <option value="name_desc">Student Name (Z–A)</option>
                        <option value="program_asc">Program (A–Z)</option>
                        <option value="program_desc">Program (Z–A)</option>
                        <option value="complaint_asc">Complaint (A–Z)</option>
                        <option value="complaint_desc">Complaint (Z–A)</option>
                        <option value="status_asc">Status (A–Z)</option>
                        <option value="status_desc">Status (Z–A)</option>
                        <option value="nurse_asc">Nurse (A–Z)</option>
                        <option value="nurse_desc">Nurse (Z–A)</option>
                    </select>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('input[name=\'sections[]\']').forEach(cb => cb.checked = true)">Select All</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card stat-card-primary"><div class="d-flex justify-content-between"><div><div class="stat-label">Total Visits</div><div class="stat-value"><?php echo number_format($totalVisits); ?></div></div><div class="stat-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div></div></div></div>
    <div class="col-md-4"><div class="stat-card stat-card-secondary"><div class="d-flex justify-content-between"><div><div class="stat-label">Unique Patients</div><div class="stat-value"><?php echo number_format($totalStudentsWithVisits); ?></div></div><div class="stat-icon"><i class="bi bi-people-fill"></i></div></div></div></div>
    <div class="col-md-4"><div class="stat-card stat-card-accent"><div class="d-flex justify-content-between"><div><div class="stat-label">Avg Visits/Day</div><div class="stat-value"><?php echo $avgVisitsPerDay; ?></div></div><div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div></div></div></div>
</div>

<div class="row g-4">
    <!-- Monthly Visits Chart -->
    <div class="col-lg-8">
        <div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Visits by Month</div>
        <div class="card-body"><div class="chart-container"><canvas id="monthlyChart"></canvas></div></div></div>
    </div>
    <!-- Visits by Program -->
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Visits by Program</div>
        <div class="card-body"><div class="chart-container"><canvas id="programChart"></canvas></div></div></div>
    </div>
    <!-- Top Complaints -->
    <div class="col-12">
        <div class="card"><div class="card-header"><i class="bi bi-list-ol me-2"></i>Top Health Complaints</div>
        <div class="card-body"><div class="chart-container" style="height:400px;"><canvas id="complaintsChart"></canvas></div></div></div>
    </div>
</div>

<!-- Health Records Overview -->
<div class="row g-4 mt-2">
    <!-- Visit Status Distribution -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-diagram-3-fill me-2"></i>Visit Status Distribution</div>
            <div class="card-body">
                <?php if (empty($visitStatuses)): ?>
                <div class="empty-state py-3"><i class="bi bi-diagram-3"></i><p class="small">No data.</p></div>
                <?php else: ?>
                <div class="chart-container"><canvas id="statusChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Monthly visits
    const monthData = <?php echo json_encode($visitsByMonth); ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type:'bar', data:{
            labels: monthData.map(d=>d.month),
            datasets:[{label:'Visits',data:monthData.map(d=>d.count),backgroundColor:'rgba(0, 90, 156, 0.7)',borderColor:'#005a9c',borderWidth:1,borderRadius:6}]
        }, options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
    });

    // Program visits
    const progData = <?php echo json_encode($visitsByProgram); ?>;
    const colors = ['#0d6e3f','#1a73a7','#e8910c','#c0392b','#8e44ad','#27ae60','#f39c12','#2c3e50'];
    new Chart(document.getElementById('programChart'), {
        type:'doughnut', data:{
            labels: progData.map(d=>d.code||'Unknown'),
            datasets:[{data:progData.map(d=>d.count),backgroundColor:colors.slice(0,progData.length)}]
        }, options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}
    });

    // Top complaints — podium layout
    const rawCompData = <?php echo json_encode($topComplaints); ?>;
    
    // Podium order: 9th, 7th, 5th, 3rd, 1st, 2nd, 4th, 6th, 8th
    // Indices from sorted desc data: [8, 6, 4, 2, 0, 1, 3, 5, 7]
    const podiumOrder = [8, 6, 4, 2, 0, 1, 3, 5, 7];
    let podiumData = [];
    let podiumRanks = [];
    podiumOrder.forEach(idx => {
        if (idx < rawCompData.length) {
            podiumData.push(rawCompData[idx]);
            podiumRanks.push(idx + 1); // rank = index + 1
        }
    });
    
    // Extract category name
    const formatLabel = (label) => label.split(':')[0].substring(0, 20);
    
    // Colors based on rank
    const getRankColor = (rank) => {
        if (rank === 1) return 'rgba(241, 196, 15, 0.85)';  // Gold
        if (rank === 2) return 'rgba(189, 195, 199, 0.85)';  // Silver
        if (rank === 3) return 'rgba(205, 127, 50, 0.85)';   // Bronze
        return 'rgba(26, 115, 167, 0.55)';                    // Regular
    };
    const getRankBorder = (rank) => {
        if (rank === 1) return 'rgba(241, 196, 15, 1)';
        if (rank === 2) return 'rgba(189, 195, 199, 1)';
        if (rank === 3) return 'rgba(205, 127, 50, 1)';
        return 'rgba(26, 115, 167, 1)';
    };

    new Chart(document.getElementById('complaintsChart'), {
        type: 'bar',
        data: {
            labels: podiumData.map(d => formatLabel(d.complaint_category)),
            datasets: [{
                label: 'Occurrences',
                data: podiumData.map(d => d.count),
                backgroundColor: podiumRanks.map(r => getRankColor(r)),
                borderColor: podiumRanks.map(r => getRankBorder(r)),
                borderWidth: 1,
                borderRadius: {topLeft: 8, topRight: 8}
            }]
        }, 
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.raw + ' visits';
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { stepSize: 1 },
                    grid: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });

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
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } },
            cutout: '55%'
        }
    });
    <?php endif; ?>


});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
