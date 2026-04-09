<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'nurse']);

$db = Database::getInstance();

// --- Filter parameters ---
$filterStartDate   = $_GET['start_date'] ?? '';
$filterEndDate     = $_GET['end_date'] ?? '';
$filterProgramId   = $_GET['program_id'] ?? '';
$filterYearLevelId = $_GET['year_level_id'] ?? '';
$filterSection     = trim($_GET['section'] ?? '');

// Lookup names for display
$filterProgramName = '';
$filterYearLevelName = '';
if ($filterProgramId) {
    $filterProgramName = $db->fetchColumn("SELECT code FROM programs WHERE id=?", [$filterProgramId]) ?: '';
}
if ($filterYearLevelId) {
    $filterYearLevelName = $db->fetchColumn("SELECT name FROM year_levels WHERE id=?", [$filterYearLevelId]) ?: '';
}

// Build dynamic WHERE clause
$where = "1=1";
$params = [];
if ($filterStartDate) { $where .= " AND v.visit_date >= ?"; $params[] = $filterStartDate . ' 00:00:00'; }
if ($filterEndDate)   { $where .= " AND v.visit_date <= ?"; $params[] = $filterEndDate . ' 23:59:59'; }
if ($filterProgramId) { $where .= " AND s.program_id = ?";  $params[] = $filterProgramId; }
if ($filterYearLevelId) { $where .= " AND s.year_level_id = ?"; $params[] = $filterYearLevelId; }
if ($filterSection)   { $where .= " AND s.section = ?";     $params[] = $filterSection; }

$hasFilters = $filterStartDate || $filterEndDate || $filterProgramId || $filterYearLevelId || $filterSection;

// --- Sort parameters for Visit Records ---
$allowedSortColumns = [
    'date_desc' => 'v.visit_date DESC',
    'date_asc'  => 'v.visit_date ASC',
    'name_asc'  => 's.last_name ASC, s.first_name ASC',
    'name_desc' => 's.last_name DESC, s.first_name DESC',
    'program_asc'  => 'p.code ASC, v.visit_date DESC',
    'program_desc' => 'p.code DESC, v.visit_date DESC',
    'complaint_asc'  => 'v.complaint ASC',
    'complaint_desc' => 'v.complaint DESC',
    'status_asc'  => 'v.status ASC, v.visit_date DESC',
    'status_desc' => 'v.status DESC, v.visit_date DESC',
    'nurse_asc'  => 'attended_by ASC',
    'nurse_desc' => 'attended_by DESC',
];
$sortBy = $_GET['sort_by'] ?? 'date_desc';
if (!array_key_exists($sortBy, $allowedSortColumns)) {
    $sortBy = 'date_desc';
}
$orderBySql = $allowedSortColumns[$sortBy];

// Fetch report data
$visits = $db->fetchAll(
    "SELECT v.visit_date, s.student_id, CONCAT(s.first_name,' ',s.last_name) as student_name,
            p.code as program, v.complaint, v.assessment, v.treatment, v.status,
            CONCAT(u.first_name,' ',u.last_name) as attended_by
     FROM visits v
     JOIN students s ON v.student_id = s.id
     LEFT JOIN programs p ON s.program_id = p.id
     LEFT JOIN users u ON v.attended_by = u.id
     WHERE $where
     ORDER BY $orderBySql",
    $params
);

$totalVisits = count($visits);
$topComplaints = $db->fetchAll(
    "SELECT v.complaint_category, COUNT(*) as cnt FROM visits v
     JOIN students s ON v.student_id=s.id
     WHERE $where
     GROUP BY v.complaint_category ORDER BY cnt DESC LIMIT 10",
    $params
);

// Chart data: visits by month
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

// Chart data: visits by program
$visitsByProgram = $db->fetchAll(
    "SELECT p.code, COUNT(v.id) as count FROM visits v 
     JOIN students s ON v.student_id=s.id 
     LEFT JOIN programs p ON s.program_id=p.id 
     WHERE $where
     GROUP BY p.code ORDER BY count DESC LIMIT 8",
    $params
);

// Visit status distribution
$visitStatuses = $db->fetchAll(
    "SELECT v.status, COUNT(*) as count FROM visits v
     JOIN students s ON v.student_id=s.id
     WHERE $where
     GROUP BY v.status ORDER BY count DESC",
    $params
);

// Top 5 allergens
$topAllergens = $db->fetchAll(
    "SELECT allergen, COUNT(*) as count FROM allergies
     GROUP BY allergen ORDER BY count DESC LIMIT 5"
);

// Top 5 vaccines
$topVaccines = $db->fetchAll(
    "SELECT vaccine_name, COUNT(*) as count FROM immunizations
     GROUP BY vaccine_name ORDER BY count DESC LIMIT 5"
);

// Top chronic conditions
$topConditions = $db->fetchAll(
    "SELECT condition_name, COUNT(*) as count FROM chronic_conditions
     GROUP BY condition_name ORDER BY count DESC LIMIT 4"
);

// Summary stats
$totalStudentsWithVisits = $db->fetchColumn(
    "SELECT COUNT(DISTINCT v.student_id) FROM visits v JOIN students s ON v.student_id=s.id WHERE $where", $params
);
$avgVisitsPerDay = $db->fetchColumn(
    "SELECT ROUND(COUNT(*)/GREATEST(DATEDIFF(MAX(v.visit_date),MIN(v.visit_date)),1),1) FROM visits v JOIN students s ON v.student_id=s.id WHERE $where", $params
);

logAccess($_SESSION['user_id'], 'export_pdf', 'Generated PDF visits report with charts');

// Clear any buffered output before rendering PDF view
while (ob_get_level()) {
    ob_end_clean();
}
?>
<?php
$sections = isset($_GET['sections']) && is_array($_GET['sections']) ? $_GET['sections'] : ['summary', 'visits_month', 'visits_program', 'top_complaints', 'visit_records', 'visit_status', 'top_allergens', 'top_vaccines', 'top_conditions'];
$usePageBreaks = isset($_GET['page_breaks']) && $_GET['page_breaks'] == '1';
$useLandscape = isset($_GET['landscape']) && $_GET['landscape'] == '1';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CampusCare - Visits Report</title>
    <script src="<?php echo BASE_URL; ?>/node_modules/chart.js/dist/chart.umd.js"></script>
<style>
        /* General */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; background-color: #fff; }

        /* Header */
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #005a9c; padding-bottom: 10px; }
        .header h1 { color: #005a9c; font-size: 20px; margin-bottom: 3px; }
        .header p { font-size: 12px; color: #666; }
        .header .filters-badge { display: inline-block; background: #e8f4fd; color: #005a9c; padding: 4px 12px; border-radius: 20px; font-size: 11px; margin-top: 6px; }

        /* Section Headings */
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 14px; color: #005a9c; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px; }

        /* Table Styles */
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #005a9c; color: white; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
        tr:nth-child(even) { background: #f8fafc; }

        /* Stats — use inline-block for print safety */
        .stats { margin-bottom: 20px; text-align: center; }
        .stat-box { 
            display: inline-block;
            width: 30%;
            background: #f0f7ff;
            padding: 10px 15px; 
            border-radius: 5px; 
            text-align: center; 
            border: 1px solid #d1e3f8;
            margin: 0 1%;
            vertical-align: top;
        }
        .stat-box .value { font-size: 22px; font-weight: bold; color: #005a9c; }
        .stat-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Chart Containers — inline-block for print safety */
        .charts-row { margin-bottom: 20px; text-align: center; }
        .chart-box { 
            display: inline-block;
            vertical-align: top;
            background: #fff; 
            border: 1px solid #e2e8f0; 
            border-radius: 6px; 
            padding: 15px;
            text-align: left;
        }
        .chart-box.wide { width: 60%; margin-right: 2%; }
        .chart-box.narrow { width: 35%; }
        .chart-box.half { width: 47%; margin-right: 2%; }
        .chart-box.full { width: 97%; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600; color: #fff; }
        .status-badge.active { background: #c0392b; }
        .status-badge.resolved { background: #27ae60; }
        .status-badge.other { background: #6b7c93; }
        .chart-box h3 { font-size: 12px; color: #003d6b; margin-bottom: 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 5px; }
        .chart-box canvas { width: 100% !important; height: 220px !important; }

        /* Footer */
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        
        .no-print { margin-bottom: 15px; text-align: center; }

        <?php if ($usePageBreaks): ?>
        .page-break { page-break-before: always; }
        <?php else: ?>
        .page-break { margin-top: 30px; }
        <?php endif; ?>

        <?php if ($useLandscape): ?>
        @page { size: landscape; }
        <?php endif; ?>

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .charts-row { break-inside: avoid; }
            .section { break-inside: avoid; }
            .stat-box { border: 1px solid #ddd; background: #f9f9f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { background: #005a9c !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            canvas { display: none !important; }
            .chart-img { display: block !important; }
        }

        /* Chart images (hidden on screen, shown on print) */
        .chart-img { display: none; max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <div class="no-print" style="padding:15px 0;">
        <button onclick="preparePrint()" style="padding:10px 30px;background:#005a9c;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px;">
            Print / Save as PDF
        </button>
        <button onclick="goBack()" style="padding:10px 20px;background:#6c757d;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px;margin-left:10px;">← Back to Reports</button>
    </div>

    <div class="header">
        <h1>CampusCare — Clinic Visits Report</h1>
        <p>Generated on <?php echo date('F d, Y h:i A'); ?> | Total Records: <?php echo $totalVisits; ?></p>
        <?php if ($hasFilters): ?>
        <div class="filters-badge">
            <strong>Filtered:</strong>
            <?php if ($filterStartDate || $filterEndDate) echo ($filterStartDate ?: '…') . ' to ' . ($filterEndDate ?: '…') . ' '; ?>
            <?php if ($filterProgramName) echo '• ' . e($filterProgramName) . ' '; ?>
            <?php if ($filterYearLevelName) echo '• ' . e($filterYearLevelName) . ' '; ?>
            <?php if ($filterSection) echo '• Section ' . e($filterSection); ?>
        </div>
        <?php endif; ?>
    </div>

    <?php
$hasPreviousSection = false;
?>

    <!-- Summary Stats -->
    <?php if (in_array('summary', $sections)): ?>
    <div class="stats <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <div class="stat-box"><div class="value"><?php echo number_format($totalVisits); ?></div><div class="label">Total Visits</div></div>
        <div class="stat-box"><div class="value"><?php echo number_format($totalStudentsWithVisits); ?></div><div class="label">Unique Patients</div></div>
        <div class="stat-box"><div class="value"><?php echo $avgVisitsPerDay; ?></div><div class="label">Avg Visits/Day</div></div>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Charts Row -->
    <?php if (in_array('visits_month', $sections) || in_array('visits_program', $sections)): ?>
    <div class="charts-row <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <?php if (in_array('visits_month', $sections)): ?>
        <div class="chart-box <?php echo in_array('visits_program', $sections) ? 'wide' : 'full'; ?>">
            <h3>Visits by Month (Last 12 Months)</h3>
            <canvas id="monthlyChart"></canvas>
            <img class="chart-img" id="monthlyChartImg" alt="Monthly visits chart">
        </div>
        <?php
    endif; ?>
        
        <?php if (in_array('visits_program', $sections)): ?>
        <div class="chart-box <?php echo in_array('visits_month', $sections) ? 'narrow' : 'full'; ?>">
            <h3>Visits by Program</h3>
            <canvas id="programChart"></canvas>
            <img class="chart-img" id="programChartImg" alt="Program visits chart">
        </div>
        <?php
    endif; ?>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Top Complaints -->
    <?php if (in_array('top_complaints', $sections)): ?>
    <div class="section <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <div class="chart-box full" style="border:1px solid #eee; border-radius:6px; padding:15px; margin-bottom:20px;">
            <h3>Top Health Complaints</h3>
            <canvas id="complaintsChart" style="height:250px !important;"></canvas>
            <img class="chart-img" id="complaintsChartImg" alt="Top complaints chart">
        </div>
        <h2>Top Health Complaints (Data)</h2>
        <table>
            <thead><tr><th>#</th><th>Complaint</th><th>Occurrences</th></tr></thead>
            <tbody>
            <?php foreach ($topComplaints as $i => $c): ?>
            <tr><td><?php echo $i + 1; ?></td><td><?php echo e($c['complaint_category']); ?></td><td><?php echo $c['cnt']; ?></td></tr>
            <?php
    endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Visit Status Distribution -->
    <?php if (in_array('visit_status', $sections)): ?>
    <div class="charts-row <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <div class="chart-box half">
            <h3>Visit Status Distribution</h3>
            <?php if (!empty($visitStatuses)): ?>
            <canvas id="statusChart"></canvas>
            <img class="chart-img" id="statusChartImg" alt="Visit status chart">
            <?php else: ?>
            <p style="color:#999;text-align:center;padding:30px 0;">No visit status data available.</p>
            <?php endif; ?>
        </div>
        <div class="chart-box narrow">
            <h3>Status Summary</h3>
            <table>
                <thead><tr><th>Status</th><th>Count</th></tr></thead>
                <tbody>
                <?php foreach ($visitStatuses as $vs): ?>
                <tr><td><?php echo e($vs['status']); ?></td><td><?php echo $vs['count']; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Top Allergens -->
    <?php if (in_array('top_allergens', $sections)): ?>
    <div class="charts-row <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <div class="chart-box half">
            <h3>Top Allergens</h3>
            <?php if (!empty($topAllergens)): ?>
            <canvas id="allergensChart"></canvas>
            <img class="chart-img" id="allergensChartImg" alt="Top allergens chart">
            <?php else: ?>
            <p style="color:#999;text-align:center;padding:30px 0;">No allergy data available.</p>
            <?php endif; ?>
        </div>
        <div class="chart-box narrow">
            <h3>Allergen Data</h3>
            <table>
                <thead><tr><th>#</th><th>Allergen</th><th>Students</th></tr></thead>
                <tbody>
                <?php foreach ($topAllergens as $i => $a): ?>
                <tr><td><?php echo $i + 1; ?></td><td><?php echo e($a['allergen']); ?></td><td><?php echo $a['count']; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Top Vaccines -->
    <?php if (in_array('top_vaccines', $sections)): ?>
    <div class="charts-row <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <div class="chart-box half">
            <h3>Top Vaccines</h3>
            <?php if (!empty($topVaccines)): ?>
            <canvas id="vaccinesChart"></canvas>
            <img class="chart-img" id="vaccinesChartImg" alt="Top vaccines chart">
            <?php else: ?>
            <p style="color:#999;text-align:center;padding:30px 0;">No immunization data available.</p>
            <?php endif; ?>
        </div>
        <div class="chart-box narrow">
            <h3>Vaccine Data</h3>
            <table>
                <thead><tr><th>#</th><th>Vaccine</th><th>Doses</th></tr></thead>
                <tbody>
                <?php foreach ($topVaccines as $i => $vac): ?>
                <tr><td><?php echo $i + 1; ?></td><td><?php echo e($vac['vaccine_name']); ?></td><td><?php echo $vac['count']; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Top Chronic Conditions -->
    <?php if (in_array('top_conditions', $sections)): ?>
    <div class="section <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <h2>Top Chronic Conditions</h2>
        <?php if (!empty($topConditions)): ?>
        <table>
            <thead><tr><th>#</th><th>Condition</th><th>Students</th></tr></thead>
            <tbody>
            <?php foreach ($topConditions as $i => $tc): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo e($tc['condition_name']); ?></td>
                <td><?php echo $tc['count']; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#999;text-align:center;padding:20px 0;">No chronic condition data available.</p>
        <?php endif; ?>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Visit Records Table -->
    <?php if (in_array('visit_records', $sections)): ?>
    <div class="section <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <h2>Visit Records
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:10px;">
                Sorted by: <?php
                    $sortLabels = [
                        'date_desc' => 'Date (Newest First)',
                        'date_asc'  => 'Date (Oldest First)',
                        'name_asc'  => 'Student Name (A–Z)',
                        'name_desc' => 'Student Name (Z–A)',
                        'program_asc'  => 'Program (A–Z)',
                        'program_desc' => 'Program (Z–A)',
                        'complaint_asc'  => 'Complaint (A–Z)',
                        'complaint_desc' => 'Complaint (Z–A)',
                        'status_asc'  => 'Status (A–Z)',
                        'status_desc' => 'Status (Z–A)',
                        'nurse_asc'  => 'Nurse (A–Z)',
                        'nurse_desc' => 'Nurse (Z–A)',
                    ];
                    echo e($sortLabels[$sortBy] ?? 'Date (Newest First)');
                ?>
            </span>
        </h2>
        <table>
            <thead><tr><th>Date</th><th>Student ID</th><th>Name</th><th>Program</th><th>Complaint</th><th>Status</th><th>Nurse</th></tr></thead>
            <tbody>
            <?php foreach ($visits as $v): ?>
            <tr>
                <td><?php echo formatDateTime($v['visit_date'], 'M d, Y'); ?></td>
                <td><?php echo e($v['student_id']); ?></td>
                <td><?php echo e($v['student_name']); ?></td>
                <td><?php echo e($v['program'] ?? '—'); ?></td>
                <td><?php echo e(substr($v['complaint'], 0, 40)); ?></td>
                <td><?php echo e($v['status']); ?></td>
                <td><?php echo e($v['attended_by']); ?></td>
            </tr>
            <?php
    endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
endif; ?>

    <div class="footer">CampusCare — School Clinic Patient Information Record System</div>

    <script>
    // Monthly visits bar chart
    <?php if (in_array('visits_month', $sections)): ?>
    const monthData = <?php echo json_encode($visitsByMonth); ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type:'bar', data:{
            labels: monthData.map(d=>d.month),
            datasets:[{label:'Visits',data:monthData.map(d=>d.count),backgroundColor:'rgba(0, 90, 156, 0.7)',borderColor:'#005a9c',borderWidth:1,borderRadius:6}]
        }, options:{responsive:true,maintainAspectRatio:false,animation:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
    });
    <?php
endif; ?>

    // Program visits doughnut chart
    <?php if (in_array('visits_program', $sections)): ?>
    const progData = <?php echo json_encode($visitsByProgram); ?>;
    const colors = ['#0d6e3f','#1a73a7','#e8910c','#c0392b','#8e44ad','#27ae60','#f39c12','#2c3e50'];
    new Chart(document.getElementById('programChart'), {
        type:'doughnut', data:{
            labels: progData.map(d=>d.code||'Unknown'),
            datasets:[{data:progData.map(d=>d.count),backgroundColor:colors.slice(0,progData.length)}]
        }, options:{responsive:true,maintainAspectRatio:false,animation:false,plugins:{legend:{position:'bottom',labels:{font:{size:10}}}}}
    });
    <?php
endif; ?>

    // Top complaints — podium bar chart (matches reports page)
    <?php if (in_array('top_complaints', $sections)): ?>
    const rawCompData = <?php echo json_encode($topComplaints); ?>;
    
    // Podium order: 9th, 7th, 5th, 3rd, 1st, 2nd, 4th, 6th, 8th
    const podiumOrder = [8, 6, 4, 2, 0, 1, 3, 5, 7];
    let podiumData = [];
    let podiumRanks = [];
    podiumOrder.forEach(idx => {
        if (idx < rawCompData.length) {
            podiumData.push(rawCompData[idx]);
            podiumRanks.push(idx + 1);
        }
    });
    
    const formatLabel = (label) => label.split(':')[0].substring(0, 20);
    
    const getRankColor = (rank) => {
        if (rank === 1) return 'rgba(241, 196, 15, 0.85)';
        if (rank === 2) return 'rgba(189, 195, 199, 0.85)';
        if (rank === 3) return 'rgba(205, 127, 50, 0.85)';
        return 'rgba(26, 115, 167, 0.55)';
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
                data: podiumData.map(d => d.cnt),
                backgroundColor: podiumRanks.map(r => getRankColor(r)),
                borderColor: podiumRanks.map(r => getRankBorder(r)),
                borderWidth: 1,
                borderRadius: {topLeft: 8, topRight: 8}
            }]
        }, 
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) { return ' ' + context.raw + ' visits'; }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: false } },
                x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } }
            }
        }
    });
    <?php
endif; ?>

    // Visit Status doughnut
    <?php if (in_array('visit_status', $sections) && !empty($visitStatuses)): ?>
    const statusData = <?php echo json_encode($visitStatuses); ?>;
    const statusColorsMap = { 'Completed': '#27ae60', 'Follow-up': '#f39c12', 'Referred': '#c0392b' };
    new Chart(document.getElementById('statusChart'), {
        type:'doughnut', data:{
            labels: statusData.map(d=>d.status),
            datasets:[{data:statusData.map(d=>d.count),backgroundColor:statusData.map(d=>statusColorsMap[d.status]||'#6b7c93'),borderWidth:2,borderColor:'#fff'}]
        }, options:{responsive:true,maintainAspectRatio:false,animation:false,plugins:{legend:{position:'bottom',labels:{font:{size:10}}}},cutout:'55%'}
    });
    <?php endif; ?>

    // Top Allergens horizontal bar
    <?php if (in_array('top_allergens', $sections) && !empty($topAllergens)): ?>
    const allergenData = <?php echo json_encode($topAllergens); ?>;
    new Chart(document.getElementById('allergensChart'), {
        type:'bar', data:{
            labels: allergenData.map(d=>d.allergen.length>20?d.allergen.substring(0,20)+'…':d.allergen),
            datasets:[{label:'Students',data:allergenData.map(d=>d.count),backgroundColor:'rgba(231,76,60,0.7)',borderColor:'#c0392b',borderWidth:1,borderRadius:6}]
        }, options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,animation:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{stepSize:1}}}}
    });
    <?php endif; ?>

    // Top Vaccines horizontal bar
    <?php if (in_array('top_vaccines', $sections) && !empty($topVaccines)): ?>
    const vaccineData = <?php echo json_encode($topVaccines); ?>;
    new Chart(document.getElementById('vaccinesChart'), {
        type:'bar', data:{
            labels: vaccineData.map(d=>d.vaccine_name.length>20?d.vaccine_name.substring(0,20)+'…':d.vaccine_name),
            datasets:[{label:'Doses',data:vaccineData.map(d=>d.count),backgroundColor:'rgba(39,174,96,0.7)',borderColor:'#27ae60',borderWidth:1,borderRadius:6}]
        }, options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,animation:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{stepSize:1}}}}
    });
    <?php endif; ?>

    // Convert canvases to images for print
    function preparePrint() {
        document.querySelectorAll('canvas').forEach(canvas => {
            const img = document.getElementById(canvas.id + 'Img');
            if (img) {
                img.src = canvas.toDataURL('image/png');
                img.style.display = 'block';
                canvas.style.display = 'none';
            }
        });
        setTimeout(() => { window.print(); }, 300);
    }

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '<?php echo BASE_URL; ?>/admin/reports.php';
        }
    }
    </script>
</body>
</html>