<?php
/**
 * CampusCare - New Visit / Consultation Form (Nurse)
 */
$pageTitle = 'Record New Visit';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();

// Pre-select student if passed via URL
$preSelectId = intval($_GET['student_id'] ?? 0);
$preStudent = null;
if ($preSelectId) {
    $preStudent = $db->fetch("SELECT id, student_id, first_name, last_name FROM students WHERE id=? AND status='active'", [$preSelectId]);
}

// Handle student search AJAX
if (isset($_GET['search_student']) && !empty($_GET['search_student'])) {
    $q = '%' . trim($_GET['search_student']) . '%';
    $results = $db->fetchAll("SELECT id, student_id, first_name, last_name FROM students WHERE status='active' AND (student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ?) LIMIT 10", [$q, $q, $q]);
    jsonResponse(['results' => $results]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid token.');
        header('Location: new_visit.php');
        exit;
    }
    $sid = intval($_POST['student_id'] ?? 0);
    $complaint = trim($_POST['complaint'] ?? '');

    if (!$sid || empty($complaint)) {
        setFlashMessage('error', 'Student and complaint are required.');
        header('Location: new_visit.php');
        exit;
    }

    $db->query(
        "INSERT INTO visits (student_id, attended_by, visit_date, blood_pressure, temperature, pulse_rate, respiratory_rate, weight, height, complaint, assessment, treatment, follow_up_notes, follow_up_date, status) VALUES (?,?,NOW(),?,?,?,?,?,?,?,?,?,?,?,?)",
    [$sid, $_SESSION['user_id'],
        trim($_POST['blood_pressure'] ?? '') ?: null,
        trim($_POST['temperature'] ?? '') ?: null,
        trim($_POST['pulse_rate'] ?? '') ?: null,
        trim($_POST['respiratory_rate'] ?? '') ?: null,
        trim($_POST['weight'] ?? '') ?: null,
        trim($_POST['height'] ?? '') ?: null,
        $complaint,
        trim($_POST['assessment'] ?? '') ?: null,
        trim($_POST['treatment'] ?? '') ?: null,
        trim($_POST['follow_up_notes'] ?? '') ?: null,
        !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null,
        $_POST['status'] ?? 'Completed']
    );

    $studentInfo = $db->fetch("SELECT student_id FROM students WHERE id=?", [$sid]);
    logAccess($_SESSION['user_id'], 'create_visit', 'Recorded visit for ' . $studentInfo['student_id']);
    setFlashMessage('success', 'Visit recorded successfully!');
    header('Location: visits.php');
    exit;
}

$students = $db->fetchAll("SELECT id, student_id, first_name, last_name FROM students WHERE status='active' ORDER BY last_name LIMIT 500");
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-plus-circle me-2"></i>Record New Visit</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">New Visit</li></ol></nav></div>

<div class="row justify-content-center">
<div class="col-lg-10">
<div class="card"><div class="card-body p-4">
<form method="POST" class="needs-validation" novalidate>
<?php csrfField(); ?>

<h6 class="fw-bold text-primary-cc mb-3"><i class="bi bi-person me-2"></i>Patient Information</h6>
<div class="mb-3">
    <label class="form-label">Student <span class="required-asterisk">*</span></label>
    <select class="form-select" name="student_id" id="studentSelect" required>
        <option value="">Select Student...</option>
        <?php foreach ($students as $s): ?>
        <option value="<?php echo $s['id']; ?>" <?php echo($preStudent && $preStudent['id'] == $s['id']) ? 'selected' : ''; ?>><?php echo e($s['student_id'] . ' — ' . $s['last_name'] . ', ' . $s['first_name']); ?></option>
        <?php
endforeach; ?>
    </select>
    <div class="invalid-feedback">Please select a student.</div>
</div>

<h6 class="fw-bold text-primary-cc mb-3 mt-4"><i class="bi bi-heart-pulse me-2"></i>Vital Signs</h6>
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">Blood Pressure</label><input type="text" class="form-control" name="blood_pressure" placeholder="e.g. 120/80"></div>
    <div class="col-md-4"><label class="form-label">Temperature (°C)</label><input type="number" step="0.1" class="form-control" name="temperature" placeholder="e.g. 36.5"></div>
    <div class="col-md-4"><label class="form-label">Pulse Rate (bpm)</label><input type="number" class="form-control" name="pulse_rate" placeholder="e.g. 72"></div>
    <div class="col-md-4"><label class="form-label">Respiratory Rate</label><input type="number" class="form-control" name="respiratory_rate" placeholder="e.g. 18"></div>
    <div class="col-md-4"><label class="form-label">Weight (kg)</label><input type="number" step="0.1" class="form-control" name="weight" placeholder="e.g. 65.0"></div>
    <div class="col-md-4"><label class="form-label">Height (cm)</label><input type="number" step="0.1" class="form-control" name="height" placeholder="e.g. 170.0"></div>
</div>

<h6 class="fw-bold text-primary-cc mb-3 mt-4"><i class="bi bi-clipboard2-pulse me-2"></i>Clinical Notes</h6>
<div class="mb-3"><label class="form-label">Complaint <span class="required-asterisk">*</span></label><textarea class="form-control" name="complaint" rows="3" required placeholder="Describe the patient's complaint..."></textarea><div class="invalid-feedback">Complaint is required.</div></div>
<div class="mb-3"><label class="form-label">Assessment</label><textarea class="form-control" name="assessment" rows="3" placeholder="Clinical assessment and findings..."></textarea></div>
<div class="mb-3"><label class="form-label">Treatment Provided</label><textarea class="form-control" name="treatment" rows="3" placeholder="Treatment given or recommended..."></textarea></div>

<h6 class="fw-bold text-primary-cc mb-3 mt-4"><i class="bi bi-calendar-event me-2"></i>Follow-up</h6>
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">Visit Status</label><select class="form-select" name="status"><option value="Completed">Completed</option><option value="Follow-up">Follow-up Needed</option><option value="Referred">Referred</option></select></div>
    <div class="col-md-4"><label class="form-label">Follow-up Date</label><input type="date" class="form-control" name="follow_up_date"></div>
    <div class="col-md-4"></div>
    <div class="col-12"><label class="form-label">Follow-up Notes</label><textarea class="form-control" name="follow_up_notes" rows="2" placeholder="Additional notes for follow-up..."></textarea></div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-2"></i>Save Visit Record</button>
    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
</div></div></div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
