<?php
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

// ── Access Control Gate — Re-authentication (HIPAA §164.312(a)(1)) ──
// Single-use verification: required every time a student profile is opened
$accessKey = 'access_granted_' . $studentId;
$isVerified = isset($_SESSION[$accessKey]) && $_SESSION[$accessKey] === true;
if ($isVerified) {
    // Consume the one-time token so next visit requires re-authentication
    unset($_SESSION[$accessKey]);
}
$verifyError = '';

// Handle password verification POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_access') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $verifyError = 'Invalid security token. Please try again.';
    }
    else {
        $password = $_POST['verify_password'] ?? '';
        $userData = $db->fetch("SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);
        if ($userData && password_verify($password, $userData['password'])) {
            $_SESSION[$accessKey] = true;
            logAccess($_SESSION['user_id'], 'access_verified', 'Re-authenticated to access student ' . $student['student_id']);
            header('Location: student_profile.php?id=' . $studentId);
            exit;
        }
        else {
            $verifyError = 'Incorrect password. Please try again.';
            logAccess($_SESSION['user_id'], 'access_denied', 'Failed re-authentication for student ' . $student['student_id']);
        }
    }
}

// Handle AJAX operations (add/delete/update) — these come from the already-loaded page
// and have their own CSRF protection, so they must run before the access gate check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add', 'delete', 'update'])) {
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
        $_SESSION[$accessKey] = true;
        jsonResponse(['success' => true, 'message' => 'Record added successfully.']);
    }
    if ($action === 'delete') {
        $recordId = intval($_POST['record_id'] ?? 0);
        $db->query("DELETE FROM $table WHERE id=? AND student_id=?", [$recordId, $studentId]);
        logAccess($_SESSION['user_id'], 'delete_health_record', "Deleted $table record #$recordId for student " . $student['student_id']);
        $_SESSION[$accessKey] = true;
        jsonResponse(['success' => true, 'message' => 'Record deleted.']);
    }
    if ($action === 'update') {
        $recordId = intval($_POST['record_id'] ?? 0);
        $sets = [];
        $params = [];
        foreach ($_POST['data'] as $k => $v) {
            $sets[] = "$k=?";
            $params[] = trim($v) ?: null;
        }
        $params[] = $recordId;
        $params[] = $studentId;
        $db->query("UPDATE $table SET " . implode(',', $sets) . " WHERE id=? AND student_id=?", $params);
        logAccess($_SESSION['user_id'], 'update_health_record', "Updated $table record #$recordId for student " . $student['student_id']);
        $_SESSION[$accessKey] = true;
        jsonResponse(['success' => true, 'message' => 'Record updated successfully.']);
    }
}

// If not verified, show gate modal (no health data is loaded or rendered)
if (!$isVerified) {
    require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-person-badge me-2"></i>Student Profile</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="students.php">Students</a></li><li class="breadcrumb-item active"><?php echo e($student['student_id']); ?></li></ol></nav></div>

<!-- PHI Access Gate Modal (non-dismissable) -->
<div class="modal fade" id="phiGateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="phiGateModalLabel">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="modal-body p-4 text-center">
        <div class="access-control-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h5 class="fw-bold mb-1" id="phiGateModalLabel">Access Verification</h5>
        <p class="text-muted small mb-3">Enter your password to view protected health information for:</p>
        <div class="access-control-student mb-3">
            <div class="fw-semibold access-control-student-name">
                <small class="text-muted"><?php echo e($student['student_id']); ?></small>
                <?php echo e(' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
            </div>
        </div>

        <?php if ($verifyError): ?>
        <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-3 access-control-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?php echo e($verifyError); ?></div>
        </div>
        <?php
    endif; ?>

        <form method="POST" action="student_profile.php?id=<?php echo $studentId; ?>">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="verify_access">
            <div class="mb-3 text-start">
                <label for="verify_password" class="form-label">Password</label>
                <div class="position-relative">
                    <i class="bi bi-lock access-control-field-icon text-muted"></i>
                    <input type="password" class="form-control access-control-input" id="verify_password" name="verify_password"
                           placeholder="Enter your password" required>
                    <button class="btn btn-link text-muted p-0 access-control-toggle" type="button" id="toggleGatePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="bi bi-unlock me-2"></i>Verify & Access Records
            </button>
            <a href="students.php" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i>Back to Students
            </a>
        </form>
        <p class="text-muted mt-3 mb-0 access-control-hint">
            <i class="bi bi-info-circle me-1"></i>Verification is required each time you access a student's records.
        </p>
    </div>
</div>
</div>
</div>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
?>

<script>
// Auto-open the gate modal and focus the password field
const phiGateModal = new bootstrap.Modal(document.getElementById('phiGateModal'));
phiGateModal.show();
document.getElementById('phiGateModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('verify_password').focus();
});

// Toggle password visibility
document.getElementById('toggleGatePassword')?.addEventListener('click', function() {
    const pwd = document.getElementById('verify_password');
    const icon = this.querySelector('i');
    if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else { pwd.type = 'password'; icon.className = 'bi bi-eye'; }
});
</script>

    <?php
    exit; // Stop here — no PHI loaded or rendered
}

// ── PHI Access Granted ──

// HIPAA §164.312(b): Log PHI access — record who viewed this student's health profile
logAccess($_SESSION['user_id'], 'view_student_profile', 'Viewed health profile for student ' . $student['student_id']);

// Fetch all health data
$allergies = $db->fetchAll("SELECT * FROM allergies WHERE student_id=? ORDER BY created_at DESC", [$studentId]);
$conditions = $db->fetchAll("SELECT * FROM chronic_conditions WHERE student_id=? ORDER BY created_at DESC", [$studentId]);
$medications = $db->fetchAll("SELECT * FROM medications WHERE student_id=? ORDER BY created_at DESC", [$studentId]);
$immunizations = $db->fetchAll("SELECT * FROM immunizations WHERE student_id=? ORDER BY date_administered DESC", [$studentId]);
$emergencyContacts = $db->fetchAll("SELECT * FROM emergency_contacts WHERE student_id=? ORDER BY id ASC", [$studentId]);
$visits = $db->fetchAll("SELECT v.*, CONCAT(u.first_name,' ',u.last_name) as nurse_name FROM visits v LEFT JOIN users u ON v.attended_by=u.id WHERE v.student_id=? ORDER BY v.visit_date DESC LIMIT 20", [$studentId]);

// Predefined common options + any existing DB values
$presetAllergens = ['Amoxicillin', 'Aspirin', 'Bee Stings', 'Cats', 'Cockroach', 'Codeine', 'Dairy / Lactose', 'Dogs', 'Dust Mites', 'Eggs', 'Fish', 'Gluten / Wheat', 'Grass Pollen', 'Ibuprofen', 'Insect Bites', 'Latex', 'Mold', 'Peanuts', 'Penicillin', 'Pollen', 'Sesame', 'Shellfish', 'Soy', 'Sulfa Drugs', 'Sulfites', 'Tree Nuts'];
$dbAllergens = array_column($db->fetchAll("SELECT DISTINCT allergen FROM allergies WHERE allergen IS NOT NULL AND allergen != '' ORDER BY allergen ASC"), 'allergen');
$allAllergens = array_unique(array_merge($presetAllergens, $dbAllergens));
sort($allAllergens);

$presetConditions = ['Anxiety Disorder', 'Asthma', 'Attention Deficit Hyperactivity Disorder (ADHD)', 'Autism Spectrum Disorder', 'Bipolar Disorder', 'Cerebral Palsy', 'Chronic Fatigue Syndrome', 'Chronic Migraine', 'Congenital Heart Disease', 'Crohn\'s Disease', 'Cystic Fibrosis', 'Depression', 'Diabetes - Type 1', 'Diabetes - Type 2', 'Down Syndrome', 'Eating Disorder', 'Eczema / Dermatitis', 'Epilepsy / Seizure Disorder', 'Hemophilia', 'Hypertension', 'Hyperthyroidism', 'Hypothyroidism', 'Irritable Bowel Syndrome (IBS)', 'Kidney Disease', 'Lupus (SLE)', 'Muscular Dystrophy', 'Obsessive-Compulsive Disorder (OCD)', 'Polycystic Ovary Syndrome (PCOS)', 'Psoriasis', 'Rheumatoid Arthritis', 'Scoliosis', 'Sickle Cell Disease', 'Thalassemia', 'Tourette Syndrome', 'Tuberculosis (latent)', 'Ulcerative Colitis'];
$dbConditions = array_column($db->fetchAll("SELECT DISTINCT condition_name FROM chronic_conditions WHERE condition_name IS NOT NULL AND condition_name != '' ORDER BY condition_name ASC"), 'condition_name');
$allConditions = array_unique(array_merge($presetConditions, $dbConditions));
sort($allConditions);

$presetVaccines = ['BCG (Bacillus Calmette-Guérin)', 'Chickenpox (Varicella)', 'COVID-19 - AstraZeneca', 'COVID-19 - Janssen', 'COVID-19 - Moderna', 'COVID-19 - Pfizer-BioNTech', 'COVID-19 - Sinovac', 'DPT (Diphtheria, Pertussis, Tetanus)', 'Flu (Influenza) - Seasonal', 'Hepatitis A', 'Hepatitis B', 'HPV (Human Papillomavirus)', 'Japanese Encephalitis', 'Measles, Mumps, Rubella (MMR)', 'Meningococcal', 'Oral Polio Vaccine (OPV)', 'Inactivated Polio Vaccine (IPV)', 'Pneumococcal (PCV13)', 'Rabies', 'Rotavirus', 'Tdap (Tetanus, Diphtheria, Pertussis)', 'Tetanus Toxoid (TT)', 'Typhoid', 'Yellow Fever'];
$dbVaccines = array_column($db->fetchAll("SELECT DISTINCT vaccine_name FROM immunizations WHERE vaccine_name IS NOT NULL AND vaccine_name != '' ORDER BY vaccine_name ASC"), 'vaccine_name');
$allVaccines = array_unique(array_merge($presetVaccines, $dbVaccines));
sort($allVaccines);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-person-badge me-2"></i>Student Profile</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="students.php">Students</a></li><li class="breadcrumb-item active"><?php echo e($student['student_id']); ?></li></ol></nav></div>

<!-- Profile Header -->
<div class="card mb-4">
    <div class="profile-header">
        <div class="d-flex align-items-center">

            <div>
                <h3 class="mb-1"><?php echo e($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></h3>
                <div class="opacity-75"><i class="bi bi-hash me-1"></i><?php echo e($student['student_id']); ?> &nbsp;|&nbsp; <?php echo e($student['program_code'] ?? 'N/A'); ?> — <?php echo e($student['year_level_name'] ?? ''); ?> <?php echo e($student['section'] ?? ''); ?></div>
            </div>
            <div class="ms-auto"><a href="<?php echo BASE_URL; ?>/nurse/new_visit.php?student_id=<?php echo $student['id']; ?>" class="btn btn-light"><i class="bi bi-plus-lg me-1"></i>New Visit</a></div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="info-item">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value">
                    <?php echo formatDate($student['date_of_birth']); ?> (Age: <?php echo calculateAge($student['date_of_birth']); ?>)</div>
                </div>
            </div>  
            <div class="col-md-2">
                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?php echo e($student['gender']); ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-item">
                    <div class="info-label">Blood Type</div>
                    <div class="info-value"><?php echo e($student['blood_type'] ?? 'N/A'); ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-item">
                    <div class="info-label">Contact</div>
                    <div class="info-value"><?php echo e($student['contact_number'] ?? 'N/A'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo e($student['email'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#allergies">Allergies <span class="badge bg-secondary ms-1"><?php echo count($allergies); ?></span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#conditions">Conditions <span class="badge bg-secondary ms-1"><?php echo count($conditions); ?></span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#meds">Medications <span class="badge bg-secondary ms-1"><?php echo count($medications); ?></span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#immunizations">Immunizations <span class="badge bg-secondary ms-1"><?php echo count($immunizations); ?></span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#emergency">Emergency Contacts <span class="badge bg-secondary ms-1"><?php echo count($emergencyContacts); ?></span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#visitHistory">Visit History <span class="badge bg-secondary ms-1"><?php echo count($visits); ?></span></a>
    </li>
</ul>

<div class="tab-content">
    <!-- Allergies -->
    <div class="tab-pane fade show active" id="allergies">
        <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Allergies</span>
                <button class="btn btn-sm btn-primary" onclick="showAddForm('allergies')"><i class="bi bi-plus-lg me-1"></i>Add</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Allergen</th>
                                <th>Reaction</th>
                                <th>Severity</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (empty($allergies)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No allergies recorded.</td>
                            </tr>
<?php
else:
    foreach ($allergies as $a): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($a['allergen']); ?></td>
                                <td><?php echo e($a['reaction'] ?? '—'); ?></td>
                                <td><?php echo statusBadge($a['severity']); ?></td>
                                <td><small><?php echo e($a['notes'] ?? '—'); ?></small></td>
                                <td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('allergies',<?php echo $a['id']; ?>)"><i class="bi bi-trash"></i></button></td>
                            </tr>
<?php
    endforeach;
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Conditions -->
    <div class="tab-pane fade" id="conditions">
        <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
            <div class="card-header d-flex justify-content-between">
                <span>Chronic Conditions</span>
                <button class="btn btn-sm btn-primary" onclick="showAddForm('chronic_conditions')"><i class="bi bi-plus-lg me-1"></i>Add</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Condition</th>
                                <th>Diagnosed</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
    <?php if (empty($conditions)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No conditions recorded.</td>
                            </tr>
    <?php
else:
    foreach ($conditions as $c): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($c['condition_name']); ?></td>
                                <td><?php echo formatDate($c['diagnosis_date']); ?></td>
                                <td><?php echo statusBadge($c['status']); ?></td>
                                <td><small><?php echo e($c['notes'] ?? '—'); ?></small></td>
                                <td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('chronic_conditions',<?php echo $c['id']; ?>)"><i class="bi bi-trash"></i></button></td>
                            </tr>
    <?php
    endforeach;
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Medications -->
    <div class="tab-pane fade" id="meds">
        <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
            <div class="card-header d-flex justify-content-between">
                <span>Medications</span>
                <button class="btn btn-sm btn-primary" onclick="showAddForm('medications')"><i class="bi bi-plus-lg me-1"></i>Add</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Doctor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (empty($medications)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No medications recorded.</td>
                            </tr>
<?php
else:
    foreach ($medications as $m): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($m['medication_name']); ?></td>
                                <td><?php echo e($m['dosage'] ?? '—'); ?></td>
                                <td><?php echo e($m['frequency'] ?? '—'); ?></td>
                                <td><small><?php echo e($m['prescribing_doctor'] ?? '—'); ?></small></td>
                                <td><button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteRecord('medications',<?php echo $m['id']; ?>)"><i class="bi bi-trash"></i></button></td>
                            </tr>
<?php
    endforeach;
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Immunizations -->
    <div class="tab-pane fade" id="immunizations">
        <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
            <div class="card-header d-flex justify-content-between">
                <span>Immunizations</span>
                <button class="btn btn-sm btn-primary" onclick="showAddForm('immunizations')"><i class="bi bi-plus-lg me-1"></i>Add</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Date</th>
                                <th>Dose</th>
                                <th>Administered By</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (empty($immunizations)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No immunizations recorded.</td>
                            </tr>
<?php
else:
    foreach ($immunizations as $im): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($im['vaccine_name']); ?></td>
                                <td><?php echo formatDate($im['date_administered']); ?></td>
                                <td><?php echo e($im['dose_number'] ?? '—'); ?></td>
                                <td><small><?php echo e($im['administered_by'] ?? '—'); ?></small></td>
                                <td><button class="btn btn-sm btn-outline-primary btn-icon" onclick="editImmunization(this)" data-id="<?php echo $im['id']; ?>" data-vaccine="<?php echo e($im['vaccine_name']); ?>" data-date="<?php echo e($im['date_administered'] ?? ''); ?>" data-dose="<?php echo e($im['dose_number'] ?? ''); ?>" data-administered="<?php echo e($im['administered_by'] ?? ''); ?>"><i class="bi bi-pencil"></i></button></td>
                            </tr>
<?php
    endforeach;
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contacts -->
    <div class="tab-pane fade" id="emergency">
        <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
            <div class="card-header d-flex justify-content-between">
                <span>Emergency Contacts</span>
                <button class="btn btn-sm btn-primary" onclick="showAddForm('emergency_contacts')"><i class="bi bi-plus-lg me-1"></i>Add</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Phone</th>
                                <th>Primary</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (empty($emergencyContacts)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No emergency contacts.</td>
                            </tr>
<?php
else:
    foreach ($emergencyContacts as $idx => $ec): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($ec['contact_name']); ?></td>
                                <td><?php echo e($ec['relationship']); ?></td>
                                <td><?php echo e($ec['phone_number']); ?></td>
                                <td><?php echo $idx === 0 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                                <td><button class="btn btn-sm btn-outline-primary btn-icon" onclick="editContact(this)" data-id="<?php echo $ec['id']; ?>" data-name="<?php echo e($ec['contact_name']); ?>" data-relationship="<?php echo e($ec['relationship']); ?>" data-phone="<?php echo e($ec['phone_number']); ?>"><i class="bi bi-pencil"></i></button></td>
                            </tr>
<?php
    endforeach;
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Visit History -->
    <div class="tab-pane fade" id="visitHistory">
        <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
            <div class="card-header">Visit History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Complaint</th>
                                <th>Assessment</th>
                                <th>Treatment</th>
                                <th>Status</th>
                                <th>Nurse</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (empty($visits)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No visits recorded.</td>
                            </tr>
<?php
else:
    foreach ($visits as $v): ?>
                            <tr>
                                <td><small><?php echo formatDateTime($v['visit_date'], 'M d, Y h:i A'); ?></small></td>
                                <td><?php echo e($v['complaint_category']); ?></td>
                                <td><small><?php echo truncate($v['complaint'] ?? '—', 30); ?></small></td>
                                <td><small><?php echo truncate($v['assessment'] ?? '—', 30); ?></small></td>
                                <td><small><?php echo truncate($v['treatment'] ?? '—', 30); ?></small></td>
                                <td><?php echo statusBadge($v['status']); ?></td>
                                <td><small><?php echo e($v['nurse_name'] ?? '—'); ?></small></td>
                            </tr>
<?php
    endforeach;
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Add Allergy Modal -->
<div class="modal fade" id="allergyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Allergy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="allergyForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="table" value="allergies">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Allergen <span class="required-asterisk">*</span></label>
                        <select class="form-select" name="data[allergen]" id="allergenSelect" required>
                            <option value="" disabled selected>Select an allergen</option>
                            <?php foreach ($allAllergens as $a): ?>
                            <option value="<?php echo e($a); ?>"><?php echo e($a); ?></option>
                            <?php
endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reaction</label>
                        <input type="text" class="form-control" name="data[reaction]" placeholder="e.g. Hives, swelling">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Severity</label>
                        <select class="form-select" name="data[severity]">
                            <option value="Mild">Mild</option>
                            <option value="Moderate">Moderate</option>
                            <option value="Severe">Severe</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="data[notes]" placeholder="Additional notes">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Condition Modal -->
<div class="modal fade" id="conditionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Chronic Condition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="conditionForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="table" value="chronic_conditions">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Condition Name <span class="required-asterisk">*</span></label>
                        <select class="form-select" name="data[condition_name]" id="conditionSelect" required>
                            <option value="" disabled selected>Select a condition</option>
                            <?php foreach ($allConditions as $c): ?>
                            <option value="<?php echo e($c); ?>"><?php echo e($c); ?></option>
                            <?php
endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diagnosis Date</label>
                        <input type="date" class="form-control" name="data[diagnosis_date]">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="data[status]">
                            <option value="Active">Active</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="data[notes]" placeholder="Additional notes">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Medication Modal -->
<div class="modal fade" id="medicationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Medication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="medicationForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="table" value="medications">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Medication Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="data[medication_name]" required placeholder="e.g. Salbutamol">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dosage</label>
                        <input type="hidden" name="data[dosage]" id="medDosageHidden">
                        <div class="input-group">
                            <input type="number" class="form-control" id="medDosageAmount" placeholder="e.g. 200" min="0" step="any">
                            <select class="form-select" id="medDosageUnit" style="max-width:120px;">
                                <option value="mg">mg</option>
                                <option value="ml">ml</option>
                                <option value="mcg">mcg</option>
                                <option value="g">g</option>
                                <option value="IU">IU</option>
                                <option value="tablet(s)">tablet(s)</option>
                                <option value="capsule(s)">capsule(s)</option>
                                <option value="drop(s)">drop(s)</option>
                                <option value="puff(s)">puff(s)</option>
                                <option value="tsp">tsp</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Frequency</label>
                        <select class="form-select" name="data[frequency]" id="medFrequency" onchange="toggleOtherFrequency(this)">
                            <option value="" selected>Select frequency</option>
                            <option value="Once daily">Once daily</option>
                            <option value="Twice daily">Twice daily</option>
                            <option value="Three times daily">Three times daily</option>
                            <option value="Four times daily">Four times daily</option>
                            <option value="Every 4 hours">Every 4 hours</option>
                            <option value="Every 6 hours">Every 6 hours</option>
                            <option value="Every 8 hours">Every 8 hours</option>
                            <option value="Every 12 hours">Every 12 hours</option>
                            <option value="Once a week">Once a week</option>
                            <option value="As needed (PRN)">As needed (PRN)</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="medFrequencyOther" placeholder="Please specify" style="display:none;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prescribing Doctor</label>
                        <input type="text" class="form-control" name="data[prescribing_doctor]" placeholder="Doctor name" pattern="[a-zA-Z\s\-\.\u00f1\u00d1']+" title="Letters, spaces, hyphens, periods, and apostrophes only" oninput="this.value=this.value.replace(/[^a-zA-Z\s\-\.'\u00f1\u00d1]/g,'')">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Immunization Modal -->
<div class="modal fade" id="immunizationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="immunizationModalTitle">Add Immunization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="immunizationForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="immunizationAction" value="add">
                    <input type="hidden" name="table" value="immunizations">
                    <input type="hidden" name="record_id" id="immunizationRecordId" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Vaccine Name <span class="required-asterisk">*</span></label>
                        <select class="form-select" name="data[vaccine_name]" id="vaccineSelect" required>
                            <option value="" disabled selected>Select a vaccine</option>
                            <?php foreach ($allVaccines as $v): ?>
                            <option value="<?php echo e($v); ?>"><?php echo e($v); ?></option>
                            <?php
endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Administered</label>
                        <input type="date" class="form-control" name="data[date_administered]">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dose Number</label>
                        <input type="number" class="form-control" name="data[dose_number]" placeholder="e.g. 1" min="1" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Administered By</label>
                        <input type="text" class="form-control" name="data[administered_by]" placeholder="Name of administrator">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Emergency Contact Modal -->
<div class="modal fade" id="emContactModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emContactModalTitle">Add Emergency Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="emContactForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="emContactAction" value="add">
                    <input type="hidden" name="table" value="emergency_contacts">
                    <input type="hidden" name="record_id" id="emContactRecordId" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Contact Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" id="emContactName" name="data[contact_name]" required placeholder="Full name" pattern="[a-zA-Z\s\-\.\u00f1\u00d1']+" title="Letters, spaces, hyphens, periods, and apostrophes only" oninput="this.value=this.value.replace(/[^a-zA-Z\s\-\.'\u00f1\u00d1]/g,'')">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relationship <span class="required-asterisk">*</span></label>
                        <select class="form-select" id="emContactRelationship" name="data[relationship]" required onchange="toggleOtherRelationship(this)">
                            <option value="" disabled selected>Select relationship</option>
                            <option value="Mother">Mother</option>
                            <option value="Father">Father</option>
                            <option value="Parent">Parent</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Grandparent">Grandparent</option>
                            <option value="Aunt/Uncle">Aunt/Uncle</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="emContactRelationshipOther" placeholder="Please specify" style="display:none;" pattern="[a-zA-Z\s\-\.\u00f1\u00d1']+" title="Letters, spaces, hyphens, periods, and apostrophes only" oninput="this.value=this.value.replace(/[^a-zA-Z\s\-\.'\u00f1\u00d1]/g,'')">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="required-asterisk">*</span></label>
                        <input type="tel" class="form-control" id="emContactPhone" name="data[phone_number]" required placeholder="09XXXXXXXXX" maxlength="11" pattern="09[0-9]{9}" title="Must be 11 digits starting with 09 (e.g. 09171234567)" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrf = '<?php echo getCSRFToken(); ?>';
const studentUrl = 'student_profile.php?id=<?php echo $studentId; ?>';

const modals = {
    allergies: new bootstrap.Modal(document.getElementById('allergyModal')),
    chronic_conditions: new bootstrap.Modal(document.getElementById('conditionModal')),
    medications: new bootstrap.Modal(document.getElementById('medicationModal')),
    immunizations: new bootstrap.Modal(document.getElementById('immunizationModal')),
    emergency_contacts: new bootstrap.Modal(document.getElementById('emContactModal'))
};

const formIds = {
    allergies: 'allergyForm',
    chronic_conditions: 'conditionForm',
    medications: 'medicationForm',
    immunizations: 'immunizationForm',
    emergency_contacts: 'emContactForm'
};

function deleteRecord(table, id) {
    showConfirm('Delete Record?','Are you sure you want to delete this record?','Yes, Delete').then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action','delete'); fd.append('table',table); fd.append('record_id',id); fd.append('csrf_token',csrf);
            fetch(studentUrl, {method:'POST',body:fd}).then(r=>r.json()).then(d => {
                if (d.success) {
                    scheduleToast('success', d.message);
                } else {
                    showAlert('error', 'Error', d.message);
                }
            });
        }
    });
}

function showAddForm(table) {
    const formId = formIds[table];
    if (formId) {
        document.getElementById(formId).reset();
        if (table === 'immunizations') {
            document.getElementById('immunizationAction').value = 'add';
            document.getElementById('immunizationRecordId').value = '';
            document.getElementById('immunizationModalTitle').textContent = 'Add Immunization';
        }
        if (table === 'emergency_contacts') {
            document.getElementById('emContactAction').value = 'add';
            document.getElementById('emContactRecordId').value = '';
            document.getElementById('emContactModalTitle').textContent = 'Add Emergency Contact';
            document.getElementById('emContactRelationshipOther').style.display = 'none';
            document.getElementById('emContactRelationshipOther').required = false;
            document.getElementById('emContactRelationshipOther').value = '';
            document.getElementById('emContactRelationship').name = 'data[relationship]';
            const hidden = document.getElementById('emContactRelationshipHidden');
            if (hidden) hidden.remove();
        }
        modals[table].show();
    }
}

function toggleOtherRelationship(select) {
    const otherInput = document.getElementById('emContactRelationshipOther');
    if (select.value === 'Other') {
        otherInput.style.display = '';
        otherInput.required = true;
        otherInput.focus();
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

function editImmunization(btn) {
    document.getElementById('immunizationForm').reset();
    document.getElementById('immunizationAction').value = 'update';
    document.getElementById('immunizationRecordId').value = btn.dataset.id;
    document.getElementById('immunizationModalTitle').textContent = 'Edit Immunization';

    // Set vaccine select
    const vaccineSelect = document.getElementById('vaccineSelect');
    vaccineSelect.value = btn.dataset.vaccine || '';

    // Set date
    const dateInput = document.querySelector('#immunizationForm input[name="data[date_administered]"]');
    if (dateInput && btn.dataset.date) dateInput.value = btn.dataset.date;

    // Set dose number
    const doseInput = document.querySelector('#immunizationForm input[name="data[dose_number]"]');
    if (doseInput && btn.dataset.dose) doseInput.value = btn.dataset.dose;

    // Set administered by
    const adminInput = document.querySelector('#immunizationForm input[name="data[administered_by]"]');
    if (adminInput && btn.dataset.administered) adminInput.value = btn.dataset.administered;

    modals.immunizations.show();
}

function editContact(btn) {
    document.getElementById('emContactForm').reset();
    document.getElementById('emContactAction').value = 'update';
    document.getElementById('emContactRecordId').value = btn.dataset.id;
    document.getElementById('emContactName').value = btn.dataset.name;
    document.getElementById('emContactPhone').value = btn.dataset.phone;
    document.getElementById('emContactModalTitle').textContent = 'Edit Emergency Contact';

    // Set relationship — check if value matches a preset option
    const select = document.getElementById('emContactRelationship');
    const otherInput = document.getElementById('emContactRelationshipOther');
    const rel = btn.dataset.relationship || '';
    const presetValues = [...select.options].map(o => o.value);

    if (presetValues.includes(rel)) {
        select.value = rel;
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    } else {
        select.value = 'Other';
        otherInput.style.display = '';
        otherInput.required = true;
        otherInput.value = rel;
    }

    modals.emergency_contacts.show();
}

// Before submit, if "Other" is selected, swap the custom value into the relationship field
document.getElementById('emContactForm').addEventListener('submit', function(e) {
    const select = document.getElementById('emContactRelationship');
    const otherInput = document.getElementById('emContactRelationshipOther');
    if (select.value === 'Other' && otherInput.value.trim()) {
        // Create a hidden input with the custom value to override the select
        let hidden = document.getElementById('emContactRelationshipHidden');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.id = 'emContactRelationshipHidden';
            hidden.name = 'data[relationship]';
            this.appendChild(hidden);
        }
        hidden.value = otherInput.value.trim();
        select.removeAttribute('name');
    } else {
        // Ensure select has the name attribute
        select.name = 'data[relationship]';
        const hidden = document.getElementById('emContactRelationshipHidden');
        if (hidden) hidden.remove();
    }
}, true);

// --- Medication: Dosage & Frequency helpers ---

function toggleOtherFrequency(select) {
    const otherInput = document.getElementById('medFrequencyOther');
    if (select.value === 'Other') {
        otherInput.style.display = '';
        otherInput.required = true;
        otherInput.focus();
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

// Before medication submit: combine dosage amount+unit and handle frequency Other
document.getElementById('medicationForm').addEventListener('submit', function(e) {
    // Combine dosage
    const amount = document.getElementById('medDosageAmount').value.trim();
    const unit = document.getElementById('medDosageUnit').value;
    document.getElementById('medDosageHidden').value = amount ? (amount + ' ' + unit) : '';

    // Handle frequency "Other"
    const freqSelect = document.getElementById('medFrequency');
    const freqOther = document.getElementById('medFrequencyOther');
    if (freqSelect.value === 'Other' && freqOther.value.trim()) {
        let hidden = document.getElementById('medFrequencyHidden');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.id = 'medFrequencyHidden';
            hidden.name = 'data[frequency]';
            this.appendChild(hidden);
        }
        hidden.value = freqOther.value.trim();
        freqSelect.removeAttribute('name');
    } else {
        freqSelect.name = 'data[frequency]';
        const hidden = document.getElementById('medFrequencyHidden');
        if (hidden) hidden.remove();
    }
}, true);

// Attach submit handlers to all modal forms
Object.entries(formIds).forEach(([table, formId]) => {
    document.getElementById(formId).addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(studentUrl, {method:'POST', body: new FormData(this)}).then(r=>r.json()).then(d => {
            if (d.success) {
                modals[table].hide();
                scheduleToast('success', d.message);
            } else {
                showAlert('error', 'Error', d.message);
            }
        });
    });
});
</script>

