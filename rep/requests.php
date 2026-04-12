<?php
$pageTitle = 'Requests';
require_once __DIR__ . '/../includes/header.php';
requireRole('rep');
$db = Database::getInstance();
$user = getCurrentUser();

$programId = $user['assigned_program_id'] ?? null;
$yearLevelId = $user['assigned_year_level_id'] ?? null;
$section = $user['assigned_section'] ?? null;

// Handle student search AJAX (scoped to rep's section)
if (isset($_GET['search_student']) && !empty($_GET['search_student'])) {
    $raw = trim($_GET['search_student']);
    // Validate: only letters, numbers, spaces, dots, hyphens, and commas allowed
    if (!preg_match('/^[a-zA-Z0-9\s.\-,]+$/', $raw)) {
        jsonResponse(['results' => [], 'error' => 'Please enter a valid name or student ID (letters, numbers, spaces, dots, and hyphens only).']);
    }
    $q = '%' . $raw . '%';
    $sectionParams = [];
    $sectionFilter = '';
    if ($programId) { $sectionFilter .= " AND program_id=?"; $sectionParams[] = $programId; }
    if ($yearLevelId) { $sectionFilter .= " AND year_level_id=?"; $sectionParams[] = $yearLevelId; }
    if ($section) { $sectionFilter .= " AND section=?"; $sectionParams[] = $section; }
    $searchParams = array_merge($sectionParams, [$q, $q, $q, $q, $q, $q]);
    $results = $db->fetchAll("SELECT id, student_id, first_name, last_name FROM students WHERE status='active' $sectionFilter AND (student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ? OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ? OR CONCAT(first_name, ' ', LEFT(middle_name, 1), '. ', last_name) LIKE ?) LIMIT 10", $searchParams);
    jsonResponse(['results' => $results]);
}

// Handle form submission
$message = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'replacement';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $nomineeId = $_POST['nominee_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');

    if (empty($nomineeId)) {
        $error = 'Please select a nominee.';
    }
    elseif (empty($reason)) {
        $error = 'Please provide a reason for the request.';
    }
    else {
        try {
            // Check if there's already a pending replacement request
            $pending = $db->fetchColumn("SELECT COUNT(*) FROM current_requests WHERE rep_user_id = ? AND request_type = 'replacement' AND status = 'pending'", [$user['id']]);

            if ($pending > 0) {
                $error = 'You already have a pending replacement request. Please wait for the admin to process it.';
            }
            else {
                $db->query(
                    "INSERT INTO current_requests (rep_user_id, request_type, nominee_student_id, reason, status) VALUES (?, 'replacement', ?, ?, 'pending')",
                [$user['id'], $nomineeId, $reason]
                );
                // Log the replacement request submission
                $nominee = $db->fetch("SELECT student_id, first_name, last_name FROM students WHERE id = ?", [$nomineeId]);
                $nomineeName = $nominee ? $nominee['first_name'] . ' ' . $nominee['last_name'] . ' (' . $nominee['student_id'] . ')' : 'Unknown';
                logAccess($_SESSION['user_id'], 'request_rep_replacement', 'Requested replacement. Nominee: ' . $nomineeName . '. Reason: ' . $reason);
                $message = 'Your replacement request has been submitted successfully and is awaiting admin approval.';
            }
        }
        catch (Exception $e) {
            $error = 'Error submitting request: ' . $e->getMessage();
        }
    }
    $activeTab = 'replacement';
}

// Handle student deletion request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deletion'])) {
    $studentId = $_POST['student_id'] ?? null;
    $reason = trim($_POST['deletion_reason'] ?? '');

    if (empty($studentId)) {
        $error = 'Please select a student.';
    }
    elseif (empty($reason)) {
        $error = 'Please provide a reason for the deletion request.';
    }
    else {
        try {
            // Check if there's already a pending deletion request for this student
            $pendingDeletion = $db->fetchColumn(
                "SELECT COUNT(*) FROM current_requests WHERE rep_user_id = ? AND request_type = 'student_deletion' AND nominee_student_id = ? AND status = 'pending'",
                [$user['id'], $studentId]
            );

            if ($pendingDeletion > 0) {
                $error = 'You already have a pending deletion request for this student.';
            }
            else {
                $db->query(
                    "INSERT INTO current_requests (rep_user_id, request_type, nominee_student_id, reason, status) VALUES (?, 'student_deletion', ?, ?, 'pending')",
                    [$user['id'], $studentId, $reason]
                );
                $student = $db->fetch("SELECT student_id, first_name, last_name FROM students WHERE id = ?", [$studentId]);
                $studentName = $student ? $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')' : 'Unknown';
                logAccess($_SESSION['user_id'], 'request_student_deletion', 'Requested student deletion. Student: ' . $studentName . '. Reason: ' . $reason);
                $message = 'Your student deletion request has been submitted and is awaiting admin approval.';
            }
        }
        catch (Exception $e) {
            $error = 'Error submitting request: ' . $e->getMessage();
        }
    }
    $activeTab = 'deletion';
}

// Check if there's already a pending or approved replacement request
$existingRequest = $db->fetch(
    "SELECT rr.*, s.student_id as nominee_sid, s.first_name as nominee_fname, s.last_name as nominee_lname 
     FROM current_requests rr 
     LEFT JOIN students s ON rr.nominee_student_id = s.id 
     WHERE rr.rep_user_id = ? AND rr.request_type = 'replacement' AND rr.status IN ('pending', 'approved') 
     ORDER BY rr.created_at DESC LIMIT 1",
[$user['id']]
);
$hasActiveRequest = !empty($existingRequest);



// Fetch all requests by this rep (My Requests)
$myRequests = $db->fetchAll(
    "SELECT cr.*, 
            s.student_id as nominee_sid, s.first_name as nominee_fname, s.last_name as nominee_lname
     FROM current_requests cr 
     LEFT JOIN students s ON cr.nominee_student_id = s.id
     WHERE cr.rep_user_id = ? 
     ORDER BY cr.created_at DESC",
    [$user['id']]
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-send me-2"></i>Requests</h1>
    <p class="text-muted mb-0">Submit requests to the administrator.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo e($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
endif; ?>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-4" id="requestTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'replacement' ? 'active' : ''; ?>" id="replacement-tab" data-bs-toggle="tab" data-bs-target="#replacement" type="button" role="tab">
            <i class="bi bi-person-x me-1"></i>Change Role
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'deletion' ? 'active' : ''; ?>" id="deletion-tab" data-bs-toggle="tab" data-bs-target="#deletion" type="button" role="tab">
            <i class="bi bi-person-dash me-1"></i>Delete Student
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'my_requests' ? 'active' : ''; ?>" id="my-requests-tab" data-bs-toggle="tab" data-bs-target="#my-requests" type="button" role="tab">
            <i class="bi bi-inbox me-1"></i>My Requests
            <?php
            $pendingCount = 0;
            foreach ($myRequests as $r) {
                if ($r['status'] === 'pending') $pendingCount++;
            }
            if ($pendingCount > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $pendingCount; ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="requestTabContent">

    <!-- Tab 1: Replacement Request -->
    <div class="tab-pane fade <?php echo $activeTab === 'replacement' ? 'show active' : ''; ?>" id="replacement" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <i class="bi bi-person-x me-2"></i>Request Change of Role
                    </div>
                    <div class="card-body p-4">
                        <?php if ($hasActiveRequest): ?>
                            <div class="text-center py-3">
                                <?php if ($existingRequest['status'] === 'pending'): ?>
                                    <i class="bi bi-hourglass-split text-warning" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-3 fw-bold">Request Pending</h5>
                                    <p class="text-muted small">Your replacement request is awaiting admin approval.</p>
                                <?php
    else: ?>
                                    <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-3 fw-bold">Request Approved</h5>
                                    <p class="text-muted small">The admin is setting up the new account.</p>
                                <?php
    endif; ?>

                                <div class="bg-light rounded p-3 text-start">
                                    <div class="row">
                                        <div class="col-sm-6 mb-2">
                                            <small class="text-muted d-block">Nominee</small>
                                            <span class="fw-semibold small"><?php echo e($existingRequest['nominee_fname'] . ' ' . $existingRequest['nominee_lname']); ?></span>
                                            <small class="text-muted d-block">ID: <?php echo e($existingRequest['nominee_sid']); ?></small>
                                        </div>
                                        <div class="col-sm-6 mb-2">
                                            <small class="text-muted d-block">Submitted</small>
                                            <span class="small"><?php echo formatDateTime($existingRequest['created_at']); ?></span>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">Reason</small>
                                        <span class="small"><?php echo e($existingRequest['reason']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php
else: ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Select New Representative Nominee</label>
                                    <div class="student-autocomplete" id="nomineeAutocomplete">
                                        <div class="student-ac-input-wrap search-box">
                                            <i class="bi bi-search search-icon"></i>
                                            <input type="text" class="form-control" id="nomineeSearchInput"
                                                   placeholder="Search by name or student ID..." autocomplete="off">
                                        </div>
                                        <input type="hidden" name="nominee_id" id="nomineeIdHidden" required>
                                        <div class="student-ac-dropdown" id="nomineeDropdown"></div>
                                        <div class="invalid-feedback" style="display:none;" id="nomineeInvalidFeedback">Please select a student.</div>
                                    </div>
                                    <div class="form-text mt-2 small">Choose the student who will take over your role.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="reason" class="form-label fw-bold">Reason for Deactivation</label>
                                    <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Briefly explain why you are stepping down..." required></textarea>
                                </div>

                                <div class="alert alert-warning mb-3 shadow-none border-1 border-warning py-2 px-3" style="font-size: 0.8rem;">
                                    <i class="bi bi-info-circle-fill me-1 text-warning"></i>
                                    Once approved, your account will be deactivated.
                                </div>

                                <button type="submit" name="submit_request" class="btn btn-danger w-100 fw-semibold">
                                    Submit Request <i class="bi bi-send-fill ms-2"></i>
                                </button>
                            </form>
                        <?php
endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Student Deletion Request -->
    <div class="tab-pane fade <?php echo $activeTab === 'deletion' ? 'show active' : ''; ?>" id="deletion" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <i class="bi bi-person-dash me-2"></i>Request Student Deletion
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Select Student to Remove</label>
                                <div class="student-autocomplete" id="deletionAutocomplete">
                                    <div class="student-ac-input-wrap search-box">
                                        <i class="bi bi-search search-icon"></i>
                                        <input type="text" class="form-control" id="deletionSearchInput"
                                               placeholder="Search by name or student ID..." autocomplete="off">
                                    </div>
                                    <input type="hidden" name="student_id" id="deletionIdHidden" required>
                                    <div class="student-ac-dropdown" id="deletionDropdown"></div>
                                    <div class="invalid-feedback" style="display:none;" id="deletionInvalidFeedback">Please select a student.</div>
                                </div>
                                <div class="form-text mt-2 small">Choose the student you want to request deletion for.</div>
                            </div>

                            <div class="mb-3">
                                <label for="deletion_reason" class="form-label fw-bold">Reason for Deletion</label>
                                <textarea name="deletion_reason" id="deletion_reason" class="form-control" rows="3" placeholder="Explain why this student should be removed (e.g., transferred, dropped out, incorrect entry)..." required></textarea>
                            </div>

                            <div class="alert alert-warning mb-3 shadow-none border-1 border-warning py-2 px-3" style="font-size: 0.8rem;">
                                <i class="bi bi-info-circle-fill me-1 text-warning"></i>
                                Once approved by the admin, the student record will be archived.
                            </div>

                            <button type="submit" name="submit_deletion" class="btn btn-danger w-100 fw-semibold">
                                Submit Deletion Request <i class="bi bi-send-fill ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: My Requests -->
    <div class="tab-pane fade <?php echo $activeTab === 'my_requests' ? 'show active' : ''; ?>" id="my-requests" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="bi bi-inbox me-2"></i>My Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Details</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myRequests)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">No requests submitted yet.</p>
                                </td></tr>
                            <?php
else: ?>
                                <?php foreach ($myRequests as $r): ?>
                                    <tr>
                                        <td class="align-middle small"><?php echo formatDateTime($r['created_at']); ?></td>
                                        <td class="align-middle">
                                            <?php if ($r['request_type'] === 'password_reset'): ?>
                                                <span class="badge bg-info text-dark"><i class="bi bi-key me-1"></i>Password Reset</span>
                                            <?php elseif ($r['request_type'] === 'student_deletion'): ?>
                                                <span class="badge bg-danger"><i class="bi bi-person-dash me-1"></i>Student Deletion</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-person-x me-1"></i>Replacement</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php if ($r['nominee_fname']): ?>
                                                <div class="fw-semibold"><?php echo e($r['nominee_fname'] . ' ' . $r['nominee_lname']); ?></div>
                                                <small class="text-muted">ID: <?php echo e($r['nominee_sid']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="text-wrap" style="max-width: 250px;">
                                                <small><?php echo e($r['reason']); ?></small>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <?php
            $badgeClass = 'bg-secondary';
            if ($r['status'] === 'pending') $badgeClass = 'bg-warning text-dark';
            elseif ($r['status'] === 'approved') $badgeClass = 'bg-success';
            elseif ($r['status'] === 'rejected') $badgeClass = 'bg-danger';
            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($r['status']); ?></span>
                                            <?php if ($r['admin_notes']): ?>
                                                <div class="mt-1 small text-muted">Note: <?php echo e($r['admin_notes']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// ── Student Autocomplete Factory ──
function initStudentAutocomplete(config) {
    const input    = document.getElementById(config.inputId);
    const hidden   = document.getElementById(config.hiddenId);
    const dropdown = document.getElementById(config.dropdownId);
    const feedback = document.getElementById(config.feedbackId);
    const form     = input.closest('form');
    if (!input || !hidden || !dropdown) return;

    let debounce  = null;
    let activeIdx = -1;

    // Only allow letters, numbers, spaces, dots, hyphens, commas
    const validPattern = /^[a-zA-Z0-9\s.\-,]*$/;

    input.addEventListener('input', function() {
        const raw = this.value;
        hidden.value = '';
        if (feedback) feedback.style.display = 'none';
        input.classList.remove('is-invalid');

        // Silently strip invalid characters
        if (!validPattern.test(raw)) {
            this.value = raw.replace(/[^a-zA-Z0-9\s.\-,]/g, '');
        }

        const q = this.value.trim();
        clearTimeout(debounce);
        if (q.length < 1) { closeDropdown(); return; }

        debounce = setTimeout(() => {
            fetch('requests.php?search_student=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        dropdown.innerHTML = '<div class="student-ac-empty"><i class="bi bi-exclamation-triangle me-2"></i>' + escHtml(data.error) + '</div>';
                        dropdown.classList.add('show');
                        return;
                    }
                    renderDropdown(data.results || []);
                })
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
        if (feedback) feedback.style.display = 'none';
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
        if (!e.target.closest('#' + config.wrapperId)) closeDropdown();
    });

    // Validate before form submit
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!hidden.value) {
                e.preventDefault();
                e.stopPropagation();
                input.classList.add('is-invalid');
                if (feedback) {
                    feedback.textContent = 'Please select a student from the dropdown.';
                    feedback.style.display = 'block';
                }
                input.focus();
            }
        });
    }
}

// Initialize both autocompletes
initStudentAutocomplete({
    wrapperId: 'nomineeAutocomplete',
    inputId: 'nomineeSearchInput',
    hiddenId: 'nomineeIdHidden',
    dropdownId: 'nomineeDropdown',
    feedbackId: 'nomineeInvalidFeedback'
});

initStudentAutocomplete({
    wrapperId: 'deletionAutocomplete',
    inputId: 'deletionSearchInput',
    hiddenId: 'deletionIdHidden',
    dropdownId: 'deletionDropdown',
    feedbackId: 'deletionInvalidFeedback'
});
</script>
