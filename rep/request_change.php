<?php
$pageTitle = 'Requests';
require_once __DIR__ . '/../includes/header.php';
requireRole('rep');
$db = Database::getInstance();
$user = getCurrentUser();

$programId = $user['assigned_program_id'] ?? null;
$yearLevelId = $user['assigned_year_level_id'] ?? null;
$section = $user['assigned_section'] ?? null;

// Handle form submission
$message = '';
$error = '';

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



// Get students from the same section
$students = [];
if ($programId && $yearLevelId && $section) {
    $students = $db->fetchAll(
        "SELECT id, student_id, first_name, last_name FROM students WHERE program_id = ? AND year_level_id = ? AND section = ? AND status = 'active' ORDER BY last_name, first_name",
    [$programId, $yearLevelId, $section]
    );
}

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

<div class="row g-4">
    <!-- Replacement Request Card -->
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
                            <label for="nominee_id" class="form-label fw-bold">Select New Representative Nominee</label>
                            <select name="nominee_id" id="nominee_id" class="form-select" required>
                                <option value="">-- Select a student --</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['student_id'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']); ?></option>
                                <?php
    endforeach; ?>
                            </select>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
