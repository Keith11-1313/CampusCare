<?php
/**
 * CampusCare - PDF Export Helper
 * Generates a simple downloadable PDF report using basic PHP
 * Uses a lightweight HTML-to-PDF approach via browser print
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

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
$topComplaints = $db->fetchAll("SELECT complaint, COUNT(*) as cnt FROM visits GROUP BY complaint ORDER BY cnt DESC LIMIT 10");

logAccess($_SESSION['user_id'], 'export_pdf', 'Generated PDF visits report');

// Clear any buffered output (e.g. HTML from header.php) before rendering PDF view
while (ob_get_level()) {
    ob_end_clean();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CampusCare - Visits Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6e3f; padding-bottom: 10px; }
        .header h1 { color: #0d6e3f; font-size: 20px; margin-bottom: 3px; }
        .header p { font-size: 12px; color: #666; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 14px; color: #0d6e3f; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #0d6e3f; color: white; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        .stats { display: flex; gap: 20px; margin-bottom: 15px; }
        .stat-box { background: #f0f8f4; padding: 10px 15px; border-radius: 5px; flex: 1; }
        .stat-box .value { font-size: 20px; font-weight: bold; color: #0d6e3f; }
        .stat-box .label { font-size: 10px; color: #666; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
        .no-print { margin-bottom: 15px; text-align: center; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print" style="padding:15px 0;">
        <button onclick="window.print()" style="padding:10px 30px;background:#0d6e3f;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px;">
            🖨️ Print / Save as PDF
        </button>
        <button onclick="goBack()" style="padding:10px 20px;background:#6c757d;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px;margin-left:10px;">← Back to Reports</button>
    </div>

    <div class="header">
        <h1>CampusCare — Clinic Visits Report</h1>
        <p>Generated on <?php echo date('F d, Y h:i A'); ?> | Total Records: <?php echo $totalVisits; ?></p>
    </div>

    <div class="section">
        <h2>Top Health Complaints</h2>
        <table>
            <thead><tr><th>#</th><th>Complaint</th><th>Occurrences</th></tr></thead>
            <tbody>
            <?php foreach ($topComplaints as $i => $c): ?>
            <tr><td><?php echo $i + 1; ?></td><td><?php echo e($c['complaint']); ?></td><td><?php echo $c['cnt']; ?></td></tr>
            <?php
endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
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

    <div class="footer">CampusCare — School Clinic Patient Information & Medicine Record System</div>

    <script>
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
