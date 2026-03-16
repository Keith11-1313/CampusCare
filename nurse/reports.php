<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();

// Handle export requests
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require_once __DIR__ . '/../includes/export_pdf.php';
    exit;
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
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-filetype-pdf me-1"></i>Export PDF
        </button>
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
                <div class="modal-body">
                    <p class="text-muted mb-3">Select the sections you want to include in the exported report.</p>
                    
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
                        <label class="form-check-label" for="secComplaints">Top Health Complaints (Chart & Table)</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="sections[]" value="visit_records" id="secRecords" checked>
                        <label class="form-check-label" for="secRecords">Visit Records Table</label>
                    </div>
                    
                    <hr class="my-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="page_breaks" value="1" id="pageBreaks" checked>
                        <label class="form-check-label fw-bold" for="pageBreaks">Add page break between sections</label>
                    </div>
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

    // Top complaints
    const rawCompData = <?php echo json_encode($topComplaints); ?>;
    
    // Sort and rearrange for Podium: [Rank 2, Rank 1, Rank 3, Rank 4, ...]
    let podiumData = [];
    if (rawCompData.length > 0) {
        let top3 = rawCompData.slice(0, 3);
        let rest = rawCompData.slice(3, 10);
        
        // Rearrange top 3: [1, 0, 2] -> [Rank 2, Rank 1, Rank 3]
        if (top3.length === 3) podiumData = [top3[1], top3[0], top3[2]];
        else if (top3.length === 2) podiumData = [top3[1], top3[0]];
        else podiumData = [top3[0]];
        
        podiumData = podiumData.concat(rest);
    }
    
    // Extract category name
    const formatLabel = (label) => label.split(':')[0].substring(0, 20);
    
    // Colors for podium
    const getColors = (data) => {
        let maxCount = Math.max(...data.map(d => d.count));
        return data.map((d, index) => {
            if (d.count === maxCount) return 'rgba(241, 196, 15, 0.8)'; // Gold
            if (index === 0 && data.length > 2) return 'rgba(189, 195, 199, 0.8)'; // Silver
            if (index === 2 && data.length > 2) return 'rgba(211, 84, 0, 0.8)'; // Bronze
            if (index === 0 && data.length === 2) return 'rgba(189, 195, 199, 0.8)'; // Silver
            return 'rgba(26,115,167,0.5)'; // Regular color for others
        });
    };

    new Chart(document.getElementById('complaintsChart'), {
        type: 'bar', // Vertical bar for podium
        data: {
            labels: podiumData.map(d => formatLabel(d.complaint)),
            datasets: [{
                label: 'Occurrences',
                data: podiumData.map(d => d.count),
                backgroundColor: getColors(podiumData),
                borderColor: getColors(podiumData).map(c => c.replace('0.8', '1').replace('0.5', '1')),
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
