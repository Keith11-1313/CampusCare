<?php
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

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-plus-circle me-2"></i>Record New Visit</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">New Visit</li></ol></nav></div>

<div class="row justify-content-center">
<div class="col-lg-10">
<div class="card"><div class="card-body p-4">

<!-- Stepper Timeline -->
<div class="stepper" id="visitStepper">
    <div class="stepper-step active" data-step="1">
        <div class="stepper-circle">1</div>
        <div class="stepper-label">Patient</div>
    </div>
    <div class="stepper-step" data-step="2">
        <div class="stepper-circle">2</div>
        <div class="stepper-label">Vital Signs</div>
    </div>
    <div class="stepper-step" data-step="3">
        <div class="stepper-circle">3</div>
        <div class="stepper-label">Clinical Notes</div>
    </div>
    <div class="stepper-step" data-step="4">
        <div class="stepper-circle">4</div>
        <div class="stepper-label">Follow-up</div>
    </div>
</div>

<form method="POST" class="needs-validation" novalidate>
<?php csrfField(); ?>

<!-- Step 1: Patient Info -->
<div class="step-section active" data-step="1">
    <h6 class="fw-bold text-primary-cc mb-3"><i class="bi bi-person me-2"></i>Patient Information</h6>
    <div class="mb-3">
        <label class="form-label">Student <span class="required-asterisk">*</span></label>
        <div class="student-autocomplete" id="studentAutocomplete">
            <div class="student-ac-input-wrap search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" id="studentSearchInput"
                       placeholder="Search by name or student ID..."
                       autocomplete="off"
                       <?php if ($preStudent): ?>
                       value="<?php echo e($preStudent['student_id'] . ' — ' . $preStudent['last_name'] . ', ' . $preStudent['first_name']); ?>"
                       <?php endif; ?>>
            </div>
            <input type="hidden" name="student_id" id="studentIdHidden" value="<?php echo $preStudent ? $preStudent['id'] : ''; ?>" required>
            <div class="student-ac-dropdown" id="studentDropdown"></div>
            <div class="invalid-feedback" style="display:none;" id="studentInvalidFeedback">Please select a student.</div>
        </div>
    </div>
</div>

<!-- Step 2: Vital Signs -->
<div class="step-section" data-step="2">
    <h6 class="fw-bold text-primary-cc mb-3"><i class="bi bi-heart-pulse me-2"></i>Vital Signs</h6>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Blood Pressure</label><input type="text" class="form-control" name="blood_pressure" placeholder="e.g. 120/80"></div>
        <div class="col-md-4"><label class="form-label">Temperature (°C)</label><input type="number" step="0.1" class="form-control" name="temperature" placeholder="e.g. 36.5"></div>
        <div class="col-md-4"><label class="form-label">Pulse Rate (bpm)</label><input type="number" class="form-control" name="pulse_rate" placeholder="e.g. 72"></div>
        <div class="col-md-4"><label class="form-label">Respiratory Rate</label><input type="number" class="form-control" name="respiratory_rate" placeholder="e.g. 18"></div>
        <div class="col-md-4"><label class="form-label">Weight (kg)</label><input type="number" step="0.1" class="form-control" name="weight" placeholder="e.g. 65.0"></div>
        <div class="col-md-4"><label class="form-label">Height (cm)</label><input type="number" step="0.1" class="form-control" name="height" placeholder="e.g. 170.0"></div>
    </div>
</div>

<!-- Step 3: Clinical Notes -->
<div class="step-section" data-step="3">
    <h6 class="fw-bold text-primary-cc mb-3"><i class="bi bi-clipboard2-pulse me-2"></i>Clinical Notes</h6>
    <div class="mb-3"><label class="form-label">Complaint <span class="required-asterisk">*</span></label><textarea class="form-control" name="complaint" rows="3" required placeholder="Describe the patient's complaint..."></textarea><div class="invalid-feedback">Complaint is required.</div></div>
    <div class="mb-3"><label class="form-label">Assessment</label><textarea class="form-control" name="assessment" rows="3" placeholder="Clinical assessment and findings..."></textarea></div>
    <div class="mb-3"><label class="form-label">Treatment Provided</label><textarea class="form-control" name="treatment" rows="3" placeholder="Treatment given or recommended..."></textarea></div>
</div>

<!-- Step 4: Follow-up -->
<div class="step-section" data-step="4">
    <h6 class="fw-bold text-primary-cc mb-3"><i class="bi bi-calendar-event me-2"></i>Follow-up</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Visit Status</label><select class="form-select" name="status"><option value="Completed">Completed</option><option value="Follow-up">Follow-up Needed</option><option value="Referred">Referred</option></select></div>
        <div class="col-md-6"><label class="form-label">Follow-up Date</label><input type="date" class="form-control" name="follow_up_date"></div>
        <div class="col-12"><label class="form-label">Follow-up Notes</label><textarea class="form-control" name="follow_up_notes" rows="2" placeholder="Additional notes for follow-up..."></textarea></div>
    </div>
</div>

<!-- Stepper Navigation -->
<div class="stepper-nav mt-4">
    <button type="button" class="btn btn-outline-secondary" id="visitStepBack" style="display:none;" onclick="visitStepNav(-1)">
        <i class="bi bi-arrow-left me-1"></i>Back
    </button>
    <div class="ms-auto d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="button" class="btn btn-primary" id="visitStepNext" onclick="visitStepNav(1)">
            Next<i class="bi bi-arrow-right ms-1"></i>
        </button>
        <button type="submit" class="btn btn-primary" id="visitSubmitBtn" style="display:none;">
            <i class="bi bi-check-lg me-2"></i>Save Visit Record
        </button>
    </div>
</div>
</form>
</div></div></div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
let currentVisitStep = 1;
const totalVisitSteps = 4;

function goToVisitStep(step) {
    currentVisitStep = step;
    document.querySelectorAll('#visitStepper .stepper-step').forEach(s => {
        const sStep = parseInt(s.dataset.step);
        s.classList.remove('active', 'completed');
        if (sStep === step) s.classList.add('active');
        else if (sStep < step) s.classList.add('completed');
    });
    document.querySelectorAll('#visitStepper .stepper-step').forEach(s => {
        const sStep = parseInt(s.dataset.step);
        const circle = s.querySelector('.stepper-circle');
        if (sStep < step) circle.innerHTML = '<i class="bi bi-check-lg"></i>';
        else circle.textContent = sStep;
    });
    document.querySelectorAll('.step-section').forEach(sec => {
        sec.classList.toggle('active', parseInt(sec.dataset.step) === step);
    });
    document.getElementById('visitStepBack').style.display = step > 1 ? '' : 'none';
    document.getElementById('visitStepNext').style.display = step < totalVisitSteps ? '' : 'none';
    document.getElementById('visitSubmitBtn').style.display = step === totalVisitSteps ? '' : 'none';
}

function visitStepNav(dir) {
    const next = currentVisitStep + dir;
    if (next < 1 || next > totalVisitSteps) return;

    // Validate current step before moving forward
    if (dir === 1) {
        const currentSection = document.querySelector(`.step-section[data-step="${currentVisitStep}"]`);
        let stepValid = true;

        // Check standard required fields (input, select, textarea)
        const requiredFields = currentSection.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            // For hidden student_id input, validate via the visible search input
            if (field.type === 'hidden' && field.id === 'studentIdHidden') {
                if (!field.value) {
                    stepValid = false;
                    const searchInput = document.getElementById('studentSearchInput');
                    searchInput.classList.add('is-invalid');
                    document.getElementById('studentInvalidFeedback').style.display = 'block';
                }
                return;
            }
            if (!field.value.trim()) {
                stepValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
            if (!field.checkValidity()) {
                stepValid = false;
                field.classList.add('is-invalid');
            }
        });

        if (!stepValid) {
            currentSection.style.animation = 'none';
            currentSection.offsetHeight;
            currentSection.style.animation = 'shake 0.4s ease';
            return;
        }
    }

    goToVisitStep(next);
}

// Clear validation errors on input/change within visit stepper fields
document.querySelectorAll('form.needs-validation .step-section input[required], form.needs-validation .step-section select[required], form.needs-validation .step-section textarea[required]').forEach(field => {
    field.addEventListener('input', () => field.classList.remove('is-invalid'));
    field.addEventListener('change', () => field.classList.remove('is-invalid'));
});

// ── Student Autocomplete ──
(function() {
    const input    = document.getElementById('studentSearchInput');
    const hidden   = document.getElementById('studentIdHidden');
    const dropdown = document.getElementById('studentDropdown');
    const feedback = document.getElementById('studentInvalidFeedback');
    let debounce   = null;
    let activeIdx  = -1;

    input.addEventListener('input', function() {
        const q = this.value.trim();
        hidden.value = '';
        feedback.style.display = 'none';
        input.classList.remove('is-invalid');

        clearTimeout(debounce);
        if (q.length < 1) { closeDropdown(); return; }

        debounce = setTimeout(() => {
            fetch('new_visit.php?search_student=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => renderDropdown(data.results || []))
                .catch(() => closeDropdown());
        }, 250);
    });

    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.student-ac-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            highlightItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            highlightItem(items);
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            items[activeIdx].click();
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    function renderDropdown(results) {
        activeIdx = -1;
        if (!results.length) {
            dropdown.innerHTML = '<div class="student-ac-empty"><i class="bi bi-info-circle me-2"></i>No students found</div>';
            dropdown.classList.add('show');
            return;
        }
        dropdown.innerHTML = results.map((s, i) =>
            `<div class="student-ac-item" data-id="${s.id}" data-index="${i}">
                <span class="student-ac-item-id">${escHtml(s.student_id)}</span><span> — </span>
                <span class="student-ac-item-name">${escHtml(s.last_name)}, ${escHtml(s.first_name)}</span>
            </div>`
        ).join('');
        dropdown.classList.add('show');

        dropdown.querySelectorAll('.student-ac-item').forEach(item => {
            item.addEventListener('click', function() {
                selectStudent(this.dataset.id,
                    this.querySelector('.student-ac-item-id').textContent,
                    this.querySelector('.student-ac-item-name').textContent);
            });
        });
    }

    function selectStudent(id, sid, name) {
        hidden.value = id;
        input.value = sid + ' — ' + name;
        feedback.style.display = 'none';
        input.classList.remove('is-invalid');
        closeDropdown();
    }

    function closeDropdown() {
        dropdown.classList.remove('show');
        dropdown.innerHTML = '';
        activeIdx = -1;
    }

    function highlightItem(items) {
        items.forEach(i => i.classList.remove('active'));
        if (items[activeIdx]) {
            items[activeIdx].classList.add('active');
            items[activeIdx].scrollIntoView({ block: 'nearest' });
        }
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#studentAutocomplete')) closeDropdown();
    });

    // Validate before form submit
    document.querySelector('form.needs-validation').addEventListener('submit', function(e) {
        if (!hidden.value) {
            e.preventDefault();
            e.stopPropagation();
            input.classList.add('is-invalid');
            feedback.style.display = 'block';
            goToVisitStep(1);
            input.focus();
        }
    });
})();
</script>

