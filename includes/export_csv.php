<?php
/**
 * CampusCare - CSV Export Helper
 * Generates downloadable CSV of visit data
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = Database::getInstance();
$visits = $db->fetchAll(
    "SELECT v.visit_date, s.student_id, s.first_name, s.last_name, 
            p.code as program, yl.name as year_level, s.section,
            v.blood_pressure, v.temperature, v.pulse_rate, v.respiratory_rate,
            v.complaint, v.assessment, v.treatment, v.status,
            CONCAT(u.first_name,' ',u.last_name) as attended_by
     FROM visits v
     JOIN students s ON v.student_id = s.id
     LEFT JOIN programs p ON s.program_id = p.id
     LEFT JOIN year_levels yl ON s.year_level_id = yl.id
     LEFT JOIN users u ON v.attended_by = u.id
     ORDER BY v.visit_date DESC"
);

$filename = 'CampusCare_Visits_Report_' . date('Y-m-d') . '.csv';

// Clear any buffered output (e.g. HTML from header.php) before sending CSV
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Visit Date', 'Student ID', 'First Name', 'Last Name', 'Program', 'Year Level', 'Section', 'Blood Pressure', 'Temperature', 'Pulse Rate', 'Respiratory Rate', 'Complaint', 'Assessment', 'Treatment', 'Status', 'Attended By']);

foreach ($visits as $v) {
    fputcsv($output, [
        $v['visit_date'], $v['student_id'], $v['first_name'], $v['last_name'],
        $v['program'], $v['year_level'], $v['section'],
        $v['blood_pressure'], $v['temperature'], $v['pulse_rate'], $v['respiratory_rate'],
        $v['complaint'], $v['assessment'], $v['treatment'], $v['status'], $v['attended_by']
    ]);
}
fclose($output);
logAccess($_SESSION['user_id'], 'export_csv', 'Exported visits report as CSV');
