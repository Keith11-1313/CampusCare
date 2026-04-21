<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('rep');

$db = Database::getInstance();

// Get current user's assignment
require_once __DIR__ . '/../includes/functions.php';
$user = getCurrentUser();
$programId = $user['assigned_program_id'] ?? null;
$yearLevelId = $user['assigned_year_level_id'] ?? null;
$section = $user['assigned_section'] ?? null;

$search = trim($_GET['search'] ?? '');

$where = "WHERE s.status='active'";
$params = [];
if ($programId) {
    $where .= " AND s.program_id=?";
    $params[] = $programId;
}
if ($yearLevelId) {
    $where .= " AND s.year_level_id=?";
    $params[] = $yearLevelId;
}
if ($section) {
    $where .= " AND s.section=?";
    $params[] = $section;
}
if (!empty($search)) {
<<<<<<< HEAD
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', LEFT(s.middle_name, 1), '. ', s.last_name) LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk, $sk]);
=======
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk]);
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
}

$students = $db->fetchAll(
    "SELECT s.student_id, s.first_name, s.middle_name, s.last_name, s.gender, 
            s.date_of_birth, s.blood_type, s.contact_number, s.email, s.address,
            p.code as program_code, p.name as program_name, 
            yl.name as year_level, s.section
     FROM students s 
     LEFT JOIN programs p ON s.program_id = p.id 
     LEFT JOIN year_levels yl ON s.year_level_id = yl.id 
     $where 
     ORDER BY s.last_name, s.first_name", $params
);

$filename = 'CampusCare_Students_' . date('Y-m-d') . '.csv';

// Clear any buffered output before sending CSV
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student ID', 'First Name', 'Middle Name', 'Last Name', 'Gender', 'Date of Birth', 'Blood Type', 'Contact Number', 'Email', 'Address', 'Program Code', 'Program Name', 'Year Level', 'Section']);

foreach ($students as $s) {
    fputcsv($output, [
        $s['student_id'], $s['first_name'], $s['middle_name'], $s['last_name'],
        $s['gender'], $s['date_of_birth'], $s['blood_type'],
        $s['contact_number'], $s['email'], $s['address'],
        $s['program_code'], $s['program_name'], $s['year_level'], $s['section']
    ]);
}
fclose($output);
logAccess($_SESSION['user_id'], 'export_students_csv', 'Exported student records as CSV');
