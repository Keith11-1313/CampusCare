<?php
$pageTitle = 'Rep Replacement Requests';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $requestId = intval($_POST['request_id'] ?? 0);
        $action = $_POST['action'];

        if ($action === 'approve') {
                // Get request details (no DB changes yet — approval happens after account creation in users.php)
                $request = $db->fetch(
                    "SELECT rr.*, u.username as old_rep_username, s.student_id as nominee_student_id, s.first_name, s.last_name, s.program_id, s.year_level_id, s.section 
                     FROM rep_requests rr 
                     JOIN users u ON rr.rep_user_id = u.id 
                     JOIN students s ON rr.nominee_student_id = s.id 
                     WHERE rr.id = ?", 
                    [$requestId]
                );

                if ($request && $request['status'] === 'pending') {
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
                    
                    redirect(BASE_URL . "/admin/users.php?$params&msg=Please+complete+the+new+rep+account+setup.+The+old+rep+will+be+deactivated+once+saved.");
                } else {
                    $error = 'Invalid request or already processed.';
                }
        } elseif ($action === 'reject') {
            $notes = trim($_POST['admin_notes'] ?? '');
            // Get request details for logging
            $reqInfo = $db->fetch(
                "SELECT u.username FROM rep_requests rr JOIN users u ON rr.rep_user_id = u.id WHERE rr.id = ?",
                [$requestId]
            );
            $db->query(
                "UPDATE rep_requests SET status = 'rejected', admin_notes = ? WHERE id = ?",
                [$notes, $requestId]
            );
            logAccess($_SESSION['user_id'], 'reject_rep_request', "Rejected replacement request ID $requestId. Rep: " . ($reqInfo['username'] ?? 'unknown'));
            $message = 'Request has been rejected.';
        }
    }
}

// Fetch requests
$requests = $db->fetchAll(
    "SELECT rr.*, 
            u.first_name as old_rep_fname, u.last_name as old_rep_lname, u.username as old_rep_username,
            s.student_id as nominee_sid, s.first_name as nominee_fname, s.last_name as nominee_lname,
            p.code as prog_code, yl.name as yl_name, s.section
     FROM rep_requests rr
     JOIN users u ON rr.rep_user_id = u.id
     JOIN students s ON rr.nominee_student_id = s.id
     LEFT JOIN programs p ON s.program_id = p.id
     LEFT JOIN year_levels yl ON s.year_level_id = yl.id
     ORDER BY rr.created_at DESC"
);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-x me-2"></i>Rep Replacement Requests</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Rep Requests</li>
        </ol>
    </nav>
</div>

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

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <th>Current Rep</th>
                        <th>Section</th>
                        <th>Nominee</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">No requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td class="align-middle small"><?php echo formatDateTime($r['created_at']); ?></td>
                                <td class="align-middle">
                                    <div class="fw-semibold"><?php echo e($r['old_rep_fname'] . ' ' . $r['old_rep_lname']); ?></div>
                                    <small class="text-muted">@<?php echo e($r['old_rep_username']); ?></small>
                                </td>
                                <td class="align-middle small">
                                    <?php echo e($r['prog_code'] . ' ' . $r['yl_name'] . ' - ' . $r['section']); ?>
                                </td>
                                <td class="align-middle">
                                    <div class="fw-semibold text-primary"><?php echo e($r['nominee_fname'] . ' ' . $r['nominee_lname']); ?></div>
                                    <small class="text-muted">ID: <?php echo e($r['nominee_sid']); ?></small>
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
                                <td class="align-middle text-center">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="d-flex justify-content-center gap-2">
                                            <form method="POST" id="approveForm<?php echo $r['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="button" class="btn btn-sm btn-success" onclick="confirmApprove(<?php echo $r['id']; ?>)">
                                                    <i class="bi bi-check-lg me-1"></i>Approve
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="openRejectModal(<?php echo $r['id']; ?>)">
                                                <i class="bi bi-x-lg me-1"></i>Reject
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted mt-2">Processed</span>
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

<script>
function confirmApprove(id) {
    showConfirm(
        'Approve this request?',
        'The current rep will be <strong>deactivated</strong> and you will be redirected to create a new account for the nominee.',
        'Yes, approve',
        'question'
    ).then(result => {
        if (result.isConfirmed) {
            document.getElementById('approveForm' + id).submit();
        }
    });
}

function openRejectModal(id) {
    document.getElementById('rejectRequestId').value = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
