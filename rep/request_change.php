<?php
$pageTitle = 'Request Change of Role';
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
    } elseif (empty($reason)) {
        $error = 'Please provide a reason for the request.';
    } else {
        try {
            // Check if there's already a pending request
            $pending = $db->fetchColumn("SELECT COUNT(*) FROM rep_requests WHERE rep_user_id = ? AND status = 'pending'", [$user['id']]);
            
            if ($pending > 0) {
                $error = 'You already have a pending request. Please wait for the admin to process it.';
            } else {
                $db->execute(
                    "INSERT INTO rep_requests (rep_user_id, nominee_student_id, reason, status) VALUES (?, ?, ?, 'pending')",
                    [$user['id'], $nomineeId, $reason]
                );
                $message = 'Your request has been submitted successfully and is awaiting admin approval.';
            }
        } catch (Exception $e) {
            $error = 'Error submitting request: ' . $e->getMessage();
        }
    }
}

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
    <h1><i class="bi bi-person-x me-2"></i>Request Change of Role</h1>
    <p class="text-muted mb-0">Nominate a new representative for your section and request account deactivation.</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo e($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="nominee_id" class="form-label fw-bold">Select New Representative Nominee</label>
                        <select name="nominee_id" id="nominee_id" class="form-select form-select-lg" required>
                            <option value="">-- Select a student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['student_id'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text mt-2">Choose the student who will take over your role as section representative.</div>
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="form-label fw-bold">Reason for Deactivation</label>
                        <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Briefly explain why you are stepping down..." required></textarea>
                    </div>

                    <div class="alert alert-warning mb-4 shadow-none border-1 border-warning">
                        <div class="d-flex">
                            <i class="bi bi-info-circle-fill me-3 fs-4 text-warning"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-1">Important Note</h6>
                                <p class="mb-0 small text-dark">Once approved, your account will be deactivated and you will no longer have access to the representative dashboard. The admin will create a new account for the nominated student.</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4"><i class="bi bi-arrow-left me-2"></i>Cancel</a>
                        <button type="submit" name="submit_request" class="btn btn-primary px-5 py-2 fw-semibold">
                            Submit Request <i class="bi bi-send-fill ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
