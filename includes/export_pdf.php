<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'nurse']);

$db = Database::getInstance();

// Fetch report data
$visits = $db->fetchAll(
    "SELECT v.visit_date, s.student_id, CONCAT(s.first_name,' ',s.last_name) as student_name,
            p.code as program, v.complaint, v.assessment, v.treatment, v.status,
            CONCAT(u.first_name,' ',u.last_name) as attended_by
     FROM visits v
     JOIN students s ON v.student_id = s.id
     LEFT JOIN programs p ON s.program_id = p.id
     LEFT JOIN users u ON v.attended_by = u.id
     ORDER BY v.visit_date DESC"
);

$totalVisits = count($visits);
$topComplaints = $db->fetchAll("SELECT complaint_category, COUNT(*) as cnt FROM visits GROUP BY complaint_category ORDER BY cnt DESC LIMIT 10");

// Chart data: visits by month (last 12 months)
$visitsByMonth = $db->fetchAll(
    "SELECT DATE_FORMAT(visit_date,'%Y-%m') as month, COUNT(*) as count 
     FROM visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month ORDER BY month"
);

// Chart data: visits by program
$visitsByProgram = $db->fetchAll(
    "SELECT p.code, COUNT(v.id) as count FROM visits v 
     JOIN students s ON v.student_id=s.id 
     LEFT JOIN programs p ON s.program_id=p.id 
     GROUP BY p.code ORDER BY count DESC LIMIT 8"
);

// Summary stats
$totalStudentsWithVisits = $db->fetchColumn("SELECT COUNT(DISTINCT student_id) FROM visits");
$avgVisitsPerDay = $db->fetchColumn("SELECT ROUND(COUNT(*)/GREATEST(DATEDIFF(MAX(visit_date),MIN(visit_date)),1),1) FROM visits");

logAccess($_SESSION['user_id'], 'export_pdf', 'Generated PDF visits report with charts');

// Clear any buffered output before rendering PDF view
while (ob_get_level()) {
    ob_end_clean();
}
?>
<?php
$sections = isset($_GET['sections']) && is_array($_GET['sections']) ? $_GET['sections'] : ['summary', 'visits_month', 'visits_program', 'top_complaints', 'visit_records'];
$usePageBreaks = isset($_GET['page_breaks']) && $_GET['page_breaks'] == '1';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CampusCare - Visits Report</title>
    <script src="<?php echo BASE_URL; ?>/node_modules/chart.js/dist/chart.umd.js"></script>
<style>
        /* General medical-themed font stack */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; background-color: #fff; }

        /* Header with Clinical Blue */
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #005a9c; padding-bottom: 10px; }
        .header h1 { color: #005a9c; font-size: 20px; margin-bottom: 3px; }
        .header p { font-size: 12px; color: #666; }

        /* Section Headings */
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 14px; color: #005a9c; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px; }

        /* Table Styles - Professional Navy */
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #005a9c; color: white; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
        tr:nth-child(even) { background: #f8fafc; }

        /* Stats Section - Soft Blue Backgrounds */
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { 
            background: #f0f7ff; /* Very light clinical blue */
            padding: 10px 15px; 
            border-radius: 5px; 
            flex: 1; 
            text-align: center; 
            border: 1px solid #d1e3f8; 
        }
        .stat-box .value { font-size: 22px; font-weight: bold; color: #005a9c; }
        .stat-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Chart Containers */
        .charts-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .chart-box { flex: 1; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
        .chart-box.wide { flex: 2; }
        .chart-box h3 { font-size: 12px; color: #003d6b; margin-bottom: 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 5px; }
        .chart-box canvas { width: 100% !important; height: 220px !important; }

        /* Footer */
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        
        .no-print { margin-bottom: 15px; text-align: center; }

        <?php if ($usePageBreaks): ?>
        .page-break { page-break-before: always; }
        <?php
else: ?>
        .page-break { margin-top: 30px; }
        <?php
endif; ?>

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .charts-row { break-inside: avoid; }
            .section { break-inside: avoid; }
            .stat-box { border: 1px solid #ddd; background: #f9f9f9 !important; -webkit-print-color-adjust: exact; }
            th { background: #005a9c !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding:15px 0;">
        <button onclick="window.print()" style="padding:10px 30px;background:#005a9c;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px;">
            Print / Save as PDF
        </button>
        <button onclick="goBack()" style="padding:10px 20px;background:#6c757d;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px;margin-left:10px;">← Back to Reports</button>
    </div>

    <div class="header">
        <h1>CampusCare — Clinic Visits Report</h1>
        <p>Generated on <?php echo date('F d, Y h:i A'); ?> | Total Records: <?php echo $totalVisits; ?></p>
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
        <div class="chart-box wide">
            <h3>Visits by Month (Last 12 Months)</h3>
            <canvas id="monthlyChart"></canvas>
        </div>
        <?php
    endif; ?>
        
        <?php if (in_array('visits_program', $sections)): ?>
        <div class="chart-box">
            <h3>Visits by Program</h3>
            <canvas id="programChart"></canvas>
        </div>
        <?php
    endif; ?>
    </div>
    <?php $hasPreviousSection = true;
endif; ?>

    <!-- Top Complaints -->
    <?php if (in_array('top_complaints', $sections)): ?>
    <div class="section <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <div class="chart-box" style="border:1px solid #eee; border-radius:6px; padding:15px; margin-bottom:20px;">
            <h3>Top Health Complaints</h3>
            <canvas id="complaintsChart" style="height:250px !important;"></canvas>
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

    <!-- Visit Records Table -->
    <?php if (in_array('visit_records', $sections)): ?>
    <div class="section <?php echo($hasPreviousSection) ? 'page-break' : ''; ?>">
        <h2>Visit Records</h2>
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

    // Top complaints horizontal bar chart
    <?php if (in_array('top_complaints', $sections)): ?>
    const compData = <?php echo json_encode($topComplaints); ?>;
    new Chart(document.getElementById('complaintsChart'), {
        type:'bar', data:{
            labels: compData.map(d=>d.complaint_category.substring(0,30)),
            datasets:[{label:'Occurrences',data:compData.map(d=>d.cnt),backgroundColor:'rgba(26,115,167,0.7)',borderColor:'#1a73a7',borderWidth:1,borderRadius:6}]
        }, options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,animation:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{stepSize:1}}}}
    });
    <?php
endif; ?>

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
