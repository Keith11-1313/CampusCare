<?php
/**
 * CampusCare - Student Health Profile (Nurse)
 * Full profile with tabs: info, allergies, conditions, medications, immunizations, emergency contacts, visits
 */
$pageTitle = 'Student Profile';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();

$studentId = intval($_GET['id'] ?? 0);
if (!$studentId) {
    redirect(BASE_URL . '/nurse/students.php', 'error', 'Student not found.');
}

$student = $db->fetch("SELECT s.*, p.code as program_code, p.name as program_name, yl.name as year_level_name FROM students s LEFT JOIN programs p ON s.program_id=p.id LEFT JOIN year_levels yl ON s.year_level_id=yl.id WHERE s.id=?", [$studentId]);
if (!$student) {
    redirect(BASE_URL . '/nurse/students.php', 'error', 'Student not found.');
}

// Handle AJAX operations for health records
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? ''))
        jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);
    $action = $_POST['action'];
    $table = $_POST['table'] ?? '';
    $allowed = ['allergies', 'chronic_conditions', 'medications', 'immunizations', 'emergency_contacts'];
    if (!in_array($table, $allowed))
        jsonResponse(['success' => false, 'message' => 'Invalid table.']);

    if ($action === 'add') {
        $fields = [];
        $vals = [];
        $params = [];
        $fields[] = 'student_id';
        $vals[] = '?';
        $params[] = $studentId;
        foreach ($_POST['data'] as $k => $v) {
            $fields[] = $k;
            $vals[] = '?';
            $params[] = trim($v) ?: null;
        }
        $db->query("INSERT INTO $table (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")", $params);
        logAccess($_SESSION['user_id'], 'add_health_record', "Added $table record for student " . $student['student_id']);
        jsonResponse(['success' => true, 'message' => 'Record added successfully.']);
    }
    if ($action === 'delete') {
        $recordId = intval($_POST['record_id'] ?? 0);
        $db->query("DELETE FROM $table WHERE id=? AND student_id=?", [$recordId, $studentId]);
        jsonResponse(['success' => true, 'message' => 'Record deleted.']);
    }
}

// Fetch all health data
$allergies = $db->fetchAll("SELECT * FROM allergies WHERE student_id=? ORDER BY created_at DESC", [$studentId]);
$conditions = $db->fetchAll("SELECT * FROM chronic_conditions WHERE student_id=? ORDER BY created_at DESC", [$studentId]);
$medications = $db->fetchAll("SELECT * FROM medications WHERE student_id=? ORDER BY created_at DESC", [$studentId]);
$immunizations = $db->fetchAll("SELECT * FROM immunizations WHERE student_id=? ORDER BY date_administered DESC", [$studentId]);
$emergencyContacts = $db->fetchAll("SELECT * FROM emergency_contacts WHERE student_id=? ORDER BY is_primary DESC", [$studentId]);
$visits = $db->fetchAll("SELECT v.*, CONCAT(u.first_name,' ',u.last_name) as nurse_name FROM visits v LEFT JOIN users u ON v.attended_by=u.id WHERE v.student_id=? ORDER BY v.visit_date DESC LIMIT 20", [$studentId]);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-person-badge me-2"></i>Student Profile</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="students.php">Students</a></li><li class="breadcrumb-item active"><?php echo e($student['student_id']); ?></li></ol></nav></div>

<!-- Profile Header -->
<div class="card mb-4">
    <div class="profile-header">
        <div class="d-flex align-items-center">
            <div class="profile-avatar me-3"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></div>
            <div>
                <h3 class="mb-1"><?php echo e($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></h3>
                <div class="opacity-75"><i class="bi bi-hash me-1"></i><?php echo e($student['student_id']); ?> &nbsp;|&nbsp; <?php echo e($student['program_code'] ?? 'N/A'); ?> — <?php echo e($student['year_level_name'] ?? ''); ?> <?php echo e($student['section'] ?? ''); ?></div>
            </div>
            <div class="ms-auto"><a href="<?php echo BASE_URL; ?>/nurse/new_visit.php?student_id=<?php echo $student['id']; ?>" class="btn btn-light"><i class="bi bi-plus-lg me-1"></i>New Visit</a></div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><div class="info-item"><div class="info-label">Date of Birth</div><div class="info-value"><?php echo formatDate($student['date_of_birth']); ?> (Age: <?php echo calculateAge($student['date_of_birth']); ?>)</div></div></div>
            <div class="col-md-2"><div class="info-item"><div class="info-label">Gender</div><div class="info-value"><?php echo e($student['gender']); ?></div></div></div>
            <div class="col-md-2"><div class="info-item"><div class="info-label">Blood Type</div><div class="info-value"><?php echo e($student['blood_type'] ?? 'N/A'); ?></div></div></div>
            <div class="col-md-2"><div class="info-item"><div class="info-label">Contact</div><div class="info-value"><?php echo e($student['contact_number'] ?? 'N/A'); ?></div></div></div>
            <div class="col-md-3"><div class="info-item"><div class="info-label">Email</div><div class="info-value"><?php echo e($student['email'] ?? 'N/A'); ?></div></div></div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#allergies">Allergies <span class="badge bg-secondary ms-1"><?php echo count($allergies); ?></span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#conditions">Conditions <span class="badge bg-secondary ms-1"><?php echo count($conditions); ?></span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#meds">Medications <span class="badge bg-secondary ms-1"><?php echo count($medications); ?></span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#immunizations">Immunizations <span class="badge bg-secondary ms-1"><?php echo count($immunizations); ?></span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#emergency">Emergency Contacts <span class="badge bg-secondary ms-1"><?php echo count($emergencyContacts); ?></span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#visitHistory">Visit History <span class="badge bg-secondary ms-1"><?php echo count($visits); ?></span></a></li>
</ul>

<div class="tab-content">
<!-- Allergies -->
<div class="tab-pane fade show active" id="allergies">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between align-items-center"><span>Allergies</span>
<button class="btn btn-sm btn-primary" onclick="showAddForm('allergies')"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Allergen</th><th>Reaction</th><th>Severity</th><th>Notes</th><th></th></tr></thead>
<tbody>
<?php if (empty($allergies)): ?><tr><td colspan="5" class="text-center text-muted py-3">No allergies recorded.</td></tr>
<?php
else:
    foreach ($allergies as $a): ?>
<tr><td class="fw-semibold"><?php echo e($a['allergen']); ?></td><td><?php echo e($a['reaction'] ?? '—'); ?></td><td><?php echo statusBadge($a['severity']); ?></td><td><small><?php echo e($a['notes'] ?? '—'); ?></small></td>
<td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('allergies',<?php echo $a['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div></div>
</div>

<!-- Conditions -->
<div class="tab-pane fade" id="conditions">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>Chronic Conditions</span>
<button class="btn btn-sm btn-primary" onclick="showAddForm('chronic_conditions')"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Condition</th><th>Diagnosed</th><th>Status</th><th>Notes</th><th></th></tr></thead>
<tbody>
<?php if (empty($conditions)): ?><tr><td colspan="5" class="text-center text-muted py-3">No conditions recorded.</td></tr>
<?php
else:
    foreach ($conditions as $c): ?>
<tr><td class="fw-semibold"><?php echo e($c['condition_name']); ?></td><td><?php echo formatDate($c['diagnosis_date']); ?></td><td><?php echo statusBadge($c['status']); ?></td><td><small><?php echo e($c['notes'] ?? '—'); ?></small></td>
<td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('chronic_conditions',<?php echo $c['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div></div>
</div>

<!-- Medications -->
<div class="tab-pane fade" id="meds">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>Medications</span>
<button class="btn btn-sm btn-primary" onclick="showAddForm('medications')"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Medication</th><th>Dosage</th><th>Frequency</th><th>Doctor</th><th></th></tr></thead>
<tbody>
<?php if (empty($medications)): ?><tr><td colspan="5" class="text-center text-muted py-3">No medications recorded.</td></tr>
<?php
else:
    foreach ($medications as $m): ?>
<tr><td class="fw-semibold"><?php echo e($m['medication_name']); ?></td><td><?php echo e($m['dosage'] ?? '—'); ?></td><td><?php echo e($m['frequency'] ?? '—'); ?></td><td><small><?php echo e($m['prescribing_doctor'] ?? '—'); ?></small></td>
<td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('medications',<?php echo $m['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div></div>
</div>

<!-- Immunizations -->
<div class="tab-pane fade" id="immunizations">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>Immunizations</span>
<button class="btn btn-sm btn-primary" onclick="showAddForm('immunizations')"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Vaccine</th><th>Date</th><th>Dose</th><th>Administered By</th><th></th></tr></thead>
<tbody>
<?php if (empty($immunizations)): ?><tr><td colspan="5" class="text-center text-muted py-3">No immunizations recorded.</td></tr>
<?php
else:
    foreach ($immunizations as $im): ?>
<tr><td class="fw-semibold"><?php echo e($im['vaccine_name']); ?></td><td><?php echo formatDate($im['date_administered']); ?></td><td><?php echo e($im['dose_number'] ?? '—'); ?></td><td><small><?php echo e($im['administered_by'] ?? '—'); ?></small></td>
<td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('immunizations',<?php echo $im['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div></div>
</div>

<!-- Emergency Contacts -->
<div class="tab-pane fade" id="emergency">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>Emergency Contacts</span>
<button class="btn btn-sm btn-primary" onclick="showAddForm('emergency_contacts')"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Name</th><th>Relationship</th><th>Phone</th><th>Primary</th><th></th></tr></thead>
<tbody>
<?php if (empty($emergencyContacts)): ?><tr><td colspan="5" class="text-center text-muted py-3">No emergency contacts.</td></tr>
<?php
else:
    foreach ($emergencyContacts as $ec): ?>
<tr><td class="fw-semibold"><?php echo e($ec['contact_name']); ?></td><td><?php echo e($ec['relationship']); ?></td><td><?php echo e($ec['phone_number']); ?></td><td><?php echo $ec['is_primary'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
<td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('emergency_contacts',<?php echo $ec['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div></div>
</div>

<!-- Visit History -->
<div class="tab-pane fade" id="visitHistory">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header">Visit History</div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Date</th><th>Complaint</th><th>Assessment</th><th>Treatment</th><th>Status</th><th>Nurse</th></tr></thead>
<tbody>
<?php if (empty($visits)): ?><tr><td colspan="6" class="text-center text-muted py-3">No visits recorded.</td></tr>
<?php
else:
    foreach ($visits as $v): ?>
<tr><td><small><?php echo formatDateTime($v['visit_date'], 'M d, Y h:i A'); ?></small></td>
<td><?php echo truncate($v['complaint'], 30); ?></td>
<td><small><?php echo truncate($v['assessment'] ?? '—', 30); ?></small></td>
<td><small><?php echo truncate($v['treatment'] ?? '—', 30); ?></small></td>
<td><?php echo statusBadge($v['status']); ?></td>
<td><small><?php echo e($v['nurse_name'] ?? '—'); ?></small></td></tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div></div>
</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const csrf = '<?php echo getCSRFToken(); ?>';
const studentUrl = 'student_profile.php?id=<?php echo $studentId; ?>';

function deleteRecord(table, id) {
    showConfirm('Delete Record?','Are you sure you want to delete this record?','Yes, Delete').then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action','delete'); fd.append('table',table); fd.append('record_id',id); fd.append('csrf_token',csrf);
            fetch(studentUrl, {method:'POST',body:fd}).then(r=>r.json()).then(d => {
                if (d.success) {
                    Swal.fire({icon:'success', title:'Deleted!', text:d.message, confirmButtonColor:'#0d6e3f'}).then(()=>location.reload());
                } else {
                    Swal.fire({icon:'error', title:'Error', text:d.message, confirmButtonColor:'#0d6e3f'});
                }
            });
        }
    });
}

function showAddForm(table) {
    let html = '';
    const ls = 'display:block;text-align:left;font-weight:600;font-size:0.85rem;margin:12px auto 4px;width:85%;color:#333;';
    switch(table) {
        case 'allergies':
            html = '<label style="'+ls+'">Allergen <span style="color:red">*</span></label><input class="swal2-input" id="s_allergen" placeholder="e.g. Peanuts">' +
                   '<label style="'+ls+'">Reaction</label><input class="swal2-input" id="s_reaction" placeholder="e.g. Hives, swelling">' +
                   '<label style="'+ls+'">Severity</label><select class="swal2-select" id="s_severity"><option value="Mild">Mild</option><option value="Moderate">Moderate</option><option value="Severe">Severe</option></select>' +
                   '<label style="'+ls+'">Notes</label><input class="swal2-input" id="s_notes" placeholder="Additional notes">';
            break;
        case 'chronic_conditions':
            html = '<label style="'+ls+'">Condition Name <span style="color:red">*</span></label><input class="swal2-input" id="s_condition_name" placeholder="e.g. Asthma">' +
                   '<label style="'+ls+'">Diagnosis Date</label><input class="swal2-input" id="s_diagnosis_date" type="date">' +
                   '<label style="'+ls+'">Status</label><select class="swal2-select" id="s_status"><option value="Active">Active</option><option value="Managed">Managed</option><option value="Resolved">Resolved</option></select>' +
                   '<label style="'+ls+'">Notes</label><input class="swal2-input" id="s_notes" placeholder="Additional notes">';
            break;
        case 'medications':
            html = '<label style="'+ls+'">Medication Name <span style="color:red">*</span></label><input class="swal2-input" id="s_medication_name" placeholder="e.g. Salbutamol">' +
                   '<label style="'+ls+'">Dosage</label><input class="swal2-input" id="s_dosage" placeholder="e.g. 200mg">' +
                   '<label style="'+ls+'">Frequency</label><input class="swal2-input" id="s_frequency" placeholder="e.g. Twice daily">' +
                   '<label style="'+ls+'">Prescribing Doctor</label><input class="swal2-input" id="s_prescribing_doctor" placeholder="Doctor name">';
            break;
        case 'immunizations':
            html = '<label style="'+ls+'">Vaccine Name <span style="color:red">*</span></label><input class="swal2-input" id="s_vaccine_name" placeholder="e.g. Hepatitis B">' +
                   '<label style="'+ls+'">Date Administered</label><input class="swal2-input" id="s_date_administered" type="date">' +
                   '<label style="'+ls+'">Dose Number</label><input class="swal2-input" id="s_dose_number" placeholder="e.g. 1st dose">' +
                   '<label style="'+ls+'">Administered By</label><input class="swal2-input" id="s_administered_by" placeholder="Name of administrator">';
            break;
        case 'emergency_contacts':
            html = '<label style="'+ls+'">Contact Name <span style="color:red">*</span></label><input class="swal2-input" id="s_contact_name" placeholder="Full name">' +
                   '<label style="'+ls+'">Relationship <span style="color:red">*</span></label><input class="swal2-input" id="s_relationship" placeholder="e.g. Parent, Guardian">' +
                   '<label style="'+ls+'">Phone Number <span style="color:red">*</span></label><input class="swal2-input" id="s_phone_number" placeholder="e.g. 09xxxxxxxxx">';
            break;
    }
    Swal.fire({
        title: 'Add Record', html: html, showCancelButton: true,
        confirmButtonColor: '#0d6e3f', confirmButtonText: 'Save',
        preConfirm: () => {
            const data = {};
            Swal.getPopup().querySelectorAll('input,select').forEach(el => {
                const key = el.id.replace('s_','');
                data[key] = el.value;
            });
            return data;
        }
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action','add'); fd.append('table',table); fd.append('csrf_token',csrf);
            Object.entries(result.value).forEach(([k,v]) => fd.append('data['+k+']',v));
            fetch(studentUrl, {method:'POST',body:fd}).then(r=>r.json()).then(d => {
                if (d.success) {
                    Swal.fire({icon:'success', title:'Saved!', text:d.message, confirmButtonColor:'#0d6e3f'}).then(()=>location.reload());
                } else {
                    Swal.fire({icon:'error', title:'Error', text:d.message, confirmButtonColor:'#0d6e3f'});
                }
            });
        }
    });
}
</script>
