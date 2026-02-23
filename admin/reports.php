<?php
/**
 * CampusCare - Reports (Admin)
 * Charts + CSV/PDF export
 */
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

// Handle export requests
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    if ($type === 'csv') {
        require_once __DIR__ . '/../includes/export_csv.php';
        exit;
    }
    if ($type === 'pdf') {
        require_once __DIR__ . '/../includes/export_pdf.php';
        exit;
    }
}

// Chart data: visits by month (last 12 months)
$visitsByMonth = $db->fetchAll(
    "SELECT DATE_FORMAT(visit_date,'%Y-%m') as month, COUNT(*) as count 
     FROM visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month ORDER BY month"
);

// Chart data: top 10 complaints
$topComplaints = $db->fetchAll(
    "SELECT complaint, COUNT(*) as count FROM visits 
     GROUP BY complaint ORDER BY count DESC LIMIT 10"
);

// Chart data: visits by program
$visitsByProgram = $db->fetchAll(
    "SELECT p.code, COUNT(v.id) as count FROM visits v 
     JOIN students s ON v.student_id=s.id 
     LEFT JOIN programs p ON s.program_id=p.id 
     GROUP BY p.code ORDER BY count DESC LIMIT 8"
);

// Summary stats
$totalVisits = $db->fetchColumn("SELECT COUNT(*) FROM visits");
$totalStudentsWithVisits = $db->fetchColumn("SELECT COUNT(DISTINCT student_id) FROM visits");
$avgVisitsPerDay = $db->fetchColumn("SELECT ROUND(COUNT(*)/GREATEST(DATEDIFF(MAX(visit_date),MIN(visit_date)),1),1) FROM visits");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div><h1><i class="bi bi-graph-up me-2"></i>Reports & Analytics</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Reports</li></ol></nav></div>
    <div>
        <a href="?export=csv" class="btn btn-outline-success me-1"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
        <a href="?export=pdf" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Export PDF</a>
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

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Monthly visits
    const monthData = <?php echo json_encode($visitsByMonth); ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type:'bar', data:{
            labels: monthData.map(d=>d.month),
            datasets:[{label:'Visits',data:monthData.map(d=>d.count),backgroundColor:'rgba(13,110,63,0.7)',borderColor:'#0d6e3f',borderWidth:1,borderRadius:6}]
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

    // Top complaints
    const compData = <?php echo json_encode($topComplaints); ?>;
    new Chart(document.getElementById('complaintsChart'), {
        type:'bar', data:{
            labels: compData.map(d=>d.complaint.substring(0,30)),
            datasets:[{label:'Occurrences',data:compData.map(d=>d.count),backgroundColor:'rgba(26,115,167,0.7)',borderColor:'#1a73a7',borderWidth:1,borderRadius:6}]
        }, options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{stepSize:1}}}}
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
