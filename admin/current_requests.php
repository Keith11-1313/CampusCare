<?php
$pageTitle = 'Current Requests';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    }
    else {
        $requestId = intval($_POST['request_id'] ?? 0);
        $action = $_POST['action'];

        if ($action === 'approve') {
            $request = $db->fetch(
                "SELECT rr.*, u.username as old_rep_username, u.first_name as user_fname, u.last_name as user_lname,
                        s.student_id as nominee_student_id, s.first_name, s.last_name, s.program_id, s.year_level_id, s.section 
                     FROM current_requests rr 
                     JOIN users u ON rr.rep_user_id = u.id 
                     LEFT JOIN students s ON rr.nominee_student_id = s.id 
                     WHERE rr.id = ?",
            [$requestId]
            );

            if ($request && $request['status'] === 'pending') {
                if ($request['request_type'] === 'replacement') {
                    // Redirect to users.php with prefill data — approval & deactivation will happen there after account creation
                    $params = http_build_query([
                        'prefill_request' => $requestId,
                        'first_name' => $request['first_name'],
                        'last_name' => $request['last_name'],
                        'username' => $request['nominee_student_id'],
                        'role' => 'rep',
                        'prog' => $request['program_id'],
                        'yl' => $request['year_level_id'],
                        'sec' => $request['section']
                    ]);

                    redirect(BASE_URL . "/admin/users.php?$params&msg=Please+complete+the+new+class+representative+account+setup.+The+old+class+representative+will+be+deactivated+once+saved.");
                }
<<<<<<< HEAD

                elseif ($request['request_type'] === 'student_deletion') {
                    // Student deletion — archive the student
                    if ($request['nominee_student_id']) {
                        $db->query("UPDATE students SET status = 'archived' WHERE id = ?", [$request['nominee_student_id']]);
                        $db->query("UPDATE current_requests SET status = 'approved', admin_notes = 'Student has been archived.' WHERE id = ?", [$requestId]);
                        $studentName = $request['first_name'] . ' ' . $request['last_name'] . ' (' . $request['nominee_student_id'] . ')';
                        logAccess($_SESSION['user_id'], 'approve_student_deletion', "Approved student deletion request ID $requestId. Student: $studentName. Requested by: " . $request['old_rep_username']);
                        $message = 'Student ' . $request['first_name'] . ' ' . $request['last_name'] . ' has been archived successfully.';
                    } else {
                        $error = 'Student not found for this deletion request.';
=======
                elseif ($request['request_type'] === 'password_reset') {
                    // Password reset — generate new password and update
                    $newPassword = trim($_POST['new_password'] ?? '');
                    if (empty($newPassword) || strlen($newPassword) < 6) {
                        $error = 'Please provide a new password (at least 6 characters).';
                    }
                    else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $request['rep_user_id']]);
                        $db->query("UPDATE current_requests SET status = 'approved', admin_notes = 'Password has been reset.' WHERE id = ?", [$requestId]);
                        logAccess($_SESSION['user_id'], 'approve_password_reset', "Approved password reset request ID $requestId for user: " . $request['old_rep_username'] . " (" . $request['user_fname'] . " " . $request['user_lname'] . ")");
                        $message = 'Password has been reset successfully for ' . $request['user_fname'] . ' ' . $request['user_lname'] . '.';
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
                    }
                }
            }
            else {
                $error = 'Invalid request or already processed.';
            }
        }
        elseif ($action === 'reject') {
            $notes = trim($_POST['admin_notes'] ?? '');
            // Get request details for logging
            $reqInfo = $db->fetch(
                "SELECT rr.request_type, u.username FROM current_requests rr JOIN users u ON rr.rep_user_id = u.id WHERE rr.id = ?",
            [$requestId]
            );
            $db->query(
                "UPDATE current_requests SET status = 'rejected', admin_notes = ? WHERE id = ?",
            [$notes, $requestId]
            );
<<<<<<< HEAD
            $rt = $reqInfo['request_type'] ?? 'replacement';
            $typeLabel = $rt === 'student_deletion' ? 'student deletion' : 'replacement';
=======
            $typeLabel = ($reqInfo['request_type'] ?? 'replacement') === 'password_reset' ? 'password reset' : 'replacement';
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
            logAccess($_SESSION['user_id'], 'reject_request', "Rejected $typeLabel request ID $requestId. User: " . ($reqInfo['username'] ?? 'unknown'));
            $message = 'Request has been rejected.';
        }
    }
}

// Fetch requests
$requests = $db->fetchAll(
    "SELECT rr.*, 
            u.first_name as user_fname, u.last_name as user_lname, u.username as user_username, u.role as user_role,
            s.student_id as nominee_sid, s.first_name as nominee_fname, s.last_name as nominee_lname,
            p.code as prog_code, yl.name as yl_name, s.section
     FROM current_requests rr
     JOIN users u ON rr.rep_user_id = u.id
     LEFT JOIN students s ON rr.nominee_student_id = s.id
     LEFT JOIN programs p ON s.program_id = p.id
     LEFT JOIN year_levels yl ON s.year_level_id = yl.id
     ORDER BY rr.created_at DESC"
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-inbox me-2"></i>Current Requests</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Current Requests</li>
        </ol>
    </nav>
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

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Requested By</th>
                        <th>Details</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">No requests found.</td></tr>
                    <?php
else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td class="align-middle small"><?php echo formatDateTime($r['created_at']); ?></td>
                                <td class="align-middle">
<<<<<<< HEAD
                                    <?php if ($r['request_type'] === 'student_deletion'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-person-dash me-1"></i>Student Deletion</span>
=======
                                    <?php if ($r['request_type'] === 'password_reset'): ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-key me-1"></i>Password Reset</span>
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-person-x me-1"></i>Replacement</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">
                                    <div class="fw-semibold"><?php echo e($r['user_fname'] . ' ' . $r['user_lname']); ?></div>
                                    <small class="text-muted">@<?php echo e($r['user_username']); ?></small>
                                    <small class="d-block text-muted"><?php echo ucfirst($r['user_role']); ?></small>
                                </td>
                                <td class="align-middle">
                                    <?php if ($r['request_type'] === 'replacement' && $r['nominee_fname']): ?>
                                        <div class="fw-semibold text-primary"><?php echo e($r['nominee_fname'] . ' ' . $r['nominee_lname']); ?></div>
                                        <small class="text-muted">ID: <?php echo e($r['nominee_sid']); ?></small>
                                        <?php if ($r['prog_code']): ?>
                                            <small class="d-block text-muted"><?php echo e($r['prog_code'] . ' ' . $r['yl_name'] . ' - ' . $r['section']); ?></small>
                                        <?php endif; ?>
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
        if ($r['status'] === 'pending')
            $badgeClass = 'bg-warning text-dark';
        elseif ($r['status'] === 'approved')
            $badgeClass = 'bg-success';
        elseif ($r['status'] === 'rejected')
            $badgeClass = 'bg-danger';
?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($r['status']); ?></span>
                                    <?php if ($r['admin_notes']): ?>
                                        <div class="mt-1 small text-muted">Note: <?php echo e($r['admin_notes']); ?></div>
                                    <?php
        endif; ?>
                                </td>
                                <td class="align-middle text-center">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="d-flex justify-content-center gap-2">
<<<<<<< HEAD
                                            <?php if ($r['request_type'] === 'student_deletion'): ?>
                                                <form method="POST" id="approveForm<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                                    <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="button" class="btn btn-sm btn-success" onclick="confirmStudentDeletion(<?php echo $r['id']; ?>, '<?php echo e($r['nominee_fname'] . ' ' . $r['nominee_lname']); ?>')">
                                                        <i class="bi bi-check-lg me-1"></i>Approve
                                                    </button>
                                                </form>
=======
                                            <?php if ($r['request_type'] === 'password_reset'): ?>
                                                <button type="button" class="btn btn-sm btn-success" onclick="openPasswordResetModal(<?php echo $r['id']; ?>, '<?php echo e($r['user_fname'] . ' ' . $r['user_lname']); ?>')">
                                                    <i class="bi bi-check-lg me-1"></i>Reset
                                                </button>
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
                                            <?php else: ?>
                                                <form method="POST" id="approveForm<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                                    <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="button" class="btn btn-sm btn-success" onclick="confirmApprove(<?php echo $r['id']; ?>)">
                                                        <i class="bi bi-check-lg me-1"></i>Approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="openRejectModal(<?php echo $r['id']; ?>)">
                                                <i class="bi bi-x-lg me-1"></i>Reject
                                            </button>
                                        </div>
                                    <?php
        else: ?>
                                        <span class="text-muted mt-2">Processed</span>
                                    <?php
        endif; ?>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    <input type="hidden" name="action" value="reject">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection / Admin Notes</label>
                        <textarea name="admin_notes" class="form-control" rows="3" required placeholder="Provide a reason for rejecting this request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<<<<<<< HEAD

=======
<!-- Password Reset Modal -->
<div class="modal fade" id="passwordResetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="request_id" id="resetRequestId">
                    <input type="hidden" name="action" value="approve">
                    <p class="text-muted small">Set a new password for <strong id="resetUserName"></strong>.</p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold">New Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" required placeholder="Enter new password (min 6 characters)"
                                   style="padding-right: 45px;">
                            <button class="btn btn-link position-absolute text-muted p-0" type="button" id="toggleNewPassword" style="right:12px;top:50%;transform:translateY(-50%);text-decoration:none;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af

<script>
function confirmApprove(id) {
    showConfirm(
        'Approve this request?',
        'The current class representative will be <strong>deactivated</strong> and you will be redirected to create a new account for the nominee.',
        'Yes, approve',
        'question'
    ).then(result => {
        if (result.isConfirmed) {
            document.getElementById('approveForm' + id).submit();
        }
    });
}

<<<<<<< HEAD
function confirmStudentDeletion(id, studentName) {
    showConfirm(
        'Archive this student?',
        'The student <strong>' + studentName + '</strong> will be <strong>archived</strong> and removed from the active student list. This action can be reversed by the admin.',
        'Yes, archive',
        'warning'
    ).then(result => {
        if (result.isConfirmed) {
            document.getElementById('approveForm' + id).submit();
        }
    });
}

=======
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
function openRejectModal(id) {
    document.getElementById('rejectRequestId').value = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

<<<<<<< HEAD

=======
function openPasswordResetModal(id, userName) {
    document.getElementById('resetRequestId').value = id;
    document.getElementById('resetUserName').textContent = userName;
    document.getElementById('new_password').value = '';
    new bootstrap.Modal(document.getElementById('passwordResetModal')).show();
}

// Toggle password visibility in reset modal
document.getElementById('toggleNewPassword').addEventListener('click', function() {
    const pwd = document.getElementById('new_password');
    const icon = this.querySelector('i');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
