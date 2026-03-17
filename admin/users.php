<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$db = Database::getInstance();

// Handle POST actions (create, update, deactivate, activate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $assignedProgramId = !empty($_POST['assigned_program_id']) ? intval($_POST['assigned_program_id']) : null;
        $assignedYearLevelId = !empty($_POST['assigned_year_level_id']) ? intval($_POST['assigned_year_level_id']) : null;
        $assignedSection = trim($_POST['assigned_section'] ?? '');

        // Validation
        $errors = [];
        if (empty($username))
            $errors[] = 'Username is required.';
        if (empty($firstName))
            $errors[] = 'First name is required.';
        if (empty($lastName))
            $errors[] = 'Last name is required.';
        if (!in_array($role, ['admin', 'nurse', 'rep']))
            $errors[] = 'Invalid role.';

        // Check username uniqueness
        $existingUser = $db->fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
        if ($existingUser)
            $errors[] = 'Username already exists.';

        if (!empty($errors)) {
            jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
        }

        if ($action === 'create') {
            $password = $_POST['password'] ?? '';
            if (empty($password) || strlen($password) < 6) {
                jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            }
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $db->query(
                "INSERT INTO users (username, password, first_name, last_name, email, role, assigned_program_id, assigned_year_level_id, assigned_section) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$username, $hashedPassword, $firstName, $lastName, $email ?: null, $role, $assignedProgramId, $assignedYearLevelId, $assignedSection ?: null]
            );
            logAccess($_SESSION['user_id'], 'create_user', 'Created user: ' . $username);
            jsonResponse(['success' => true, 'message' => 'User created successfully.']);
        }
        else {
            $updateFields = "first_name = ?, last_name = ?, email = ?, role = ?, assigned_program_id = ?, assigned_year_level_id = ?, assigned_section = ?";
            $params = [$firstName, $lastName, $email ?: null, $role, $assignedProgramId, $assignedYearLevelId, $assignedSection ?: null];

            // Update password if provided
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 6) {
                    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
                }
                $updateFields = "password = ?, " . $updateFields;
                array_unshift($params, password_hash($_POST['password'], PASSWORD_DEFAULT));
            }

            $params[] = $id;
            $db->query("UPDATE users SET $updateFields WHERE id = ?", $params);
            // Clear cached user data if editing self
            if ($id == $_SESSION['user_id']) {
                $_SESSION['user_data'] = null;
            }
            logAccess($_SESSION['user_id'], 'update_user', 'Updated user: ' . $username);
            jsonResponse(['success' => true, 'message' => 'User updated successfully.']);
        }
    }

    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id == $_SESSION['user_id']) {
            jsonResponse(['success' => false, 'message' => 'You cannot deactivate your own account.']);
        }
        $currentStatus = $db->fetchColumn("SELECT status FROM users WHERE id = ?", [$id]);
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

        if ($newStatus === 'inactive') {
            $reason = trim($_POST['deactivation_reason'] ?? '');
            if (empty($reason)) {
                jsonResponse(['success' => false, 'message' => 'Please select a reason for deactivation.']);
            }
            $db->query("UPDATE users SET status = ?, deactivation_reason = ? WHERE id = ?", [$newStatus, $reason, $id]);
        }
        else {
            $db->query("UPDATE users SET status = ?, deactivation_reason = NULL WHERE id = ?", [$newStatus, $id]);
        }

        $statusAction = $newStatus === 'active' ? 'activate_user' : 'deactivate_user';
        $logDesc = "Changed user ID $id status to $newStatus";
        if ($newStatus === 'inactive' && !empty($reason)) {
            $logDesc .= " (Reason: $reason)";
        }
        logAccess($_SESSION['user_id'], $statusAction, $logDesc);
        jsonResponse(['success' => true, 'message' => 'User ' . ($newStatus === 'active' ? 'activated' : 'deactivated') . ' successfully.']);
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $userData = $db->fetch("SELECT id, username, first_name, last_name, email, role, assigned_program_id, assigned_year_level_id, assigned_section FROM users WHERE id = ?", [$id]);
        jsonResponse(['success' => true, 'user' => $userData]);
    }
}

// Fetch data for listing
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}
if (!empty($roleFilter)) {
    $where .= " AND role = ?";
    $params[] = $roleFilter;
}
if (!empty($statusFilter)) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$totalUsers = $db->fetchColumn("SELECT COUNT(*) FROM users $where", $params);
$totalPages = ceil($totalUsers / $perPage);
$users = $db->fetchAll("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
$programs = $db->fetchAll("SELECT * FROM programs WHERE status = 'active' ORDER BY name");
$yearLevels = $db->fetchAll("SELECT * FROM year_levels WHERE status = 'active' ORDER BY order_num");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div>
        <h1><i class="bi bi-people me-2"></i>User Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Users</li></ol>
        </nav>
    </div>
    <button class="btn btn-primary" onclick="openUserModal()">
        <i class="bi bi-plus-lg me-1"></i>Add User
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo e($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-7">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo e($search); ?>">
            </div>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="role">
                <option value="">All Roles</option>
                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="nurse" <?php echo $roleFilter === 'nurse' ? 'selected' : ''; ?>>Nurse/Staff</option>
                <option value="rep" <?php echo $roleFilter === 'rep' ? 'selected' : ''; ?>>Class Rep</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-1 mt-1">
            <?php if ($search || $roleFilter || $statusFilter): ?>
                <a href="users.php" class="btn btn-outline-secondary w-100">Clear</a>
            <?php
else: ?>
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php
endif; ?>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Assignment</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php
else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                                <div>
                                    <div class="fw-semibold"><?php echo e($u['first_name'] . ' ' . $u['last_name']); ?></div>
                                    <small class="text-muted"><?php echo e($u['email']); ?></small>
                                </div>
                        </td>
                        <td><code><?php echo e($u['username']); ?></code></td>
                        <td><?php echo getRoleDisplayName($u['role']); ?></td>
                        <td>
                            <?php if ($u['role'] === 'rep' && $u['assigned_program_id']): ?>
                            <small class="text-muted">
                                <?php
            $prog = $db->fetch("SELECT code FROM programs WHERE id = ?", [$u['assigned_program_id']]);
            $yl = $db->fetch("SELECT name FROM year_levels WHERE id = ?", [$u['assigned_year_level_id']]);
            echo e(($prog['code'] ?? '') . ' ' . ($yl['name'] ?? '') . ' Sec. ' . $u['assigned_section']);
?>
                            </small>
                            <?php
        else: ?>
                            <small class="text-muted">—</small>
                            <?php
        endif; ?>
                        </td>
                        <td><small><?php echo $u['last_login'] ? formatDateTime($u['last_login'], 'M d Y, h:i A') : 'Never'; ?></small></td>
                        <td>
                            <?php echo statusBadge($u['status']); ?>
                            <?php if ($u['status'] === 'inactive' && !empty($u['deactivation_reason'])): ?>
                            <br><small class="text-muted"><i class="bi bi-info-circle me-1"></i><?php echo e($u['deactivation_reason']); ?></small>
                            <?php
        endif; ?>
                        </td>
                        <td class="text-center table-action-btns">
                            <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editUser(<?php echo $u['id']; ?>)" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-outline-<?php echo $u['status'] === 'active' ? 'warning' : 'success'; ?> btn-icon" 
                                    onclick="toggleUserStatus(<?php echo $u['id']; ?>, '<?php echo $u['status']; ?>', '<?php echo e($u['username']); ?>')" title="<?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                <i class="bi bi-<?php echo $u['status'] === 'active' ? 'person-slash' : 'person-check'; ?>"></i>
                            </button>
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
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white">
        <?php echo generatePagination($page, $totalPages, 'users.php?search=' . urlencode($search) . '&role=' . urlencode($roleFilter) . '&status=' . urlencode($statusFilter)); ?>
    </div>
    <?php
endif; ?>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId" value="0">

                    <!-- Stepper Timeline -->
                    <div class="stepper" id="userStepper">
                        <div class="stepper-step active" data-step="1">
                            <div class="stepper-circle">1</div>
                            <div class="stepper-label">Personal Info</div>
                        </div>
                        <div class="stepper-step" data-step="2">
                            <div class="stepper-circle">2</div>
                            <div class="stepper-label">Account</div>
                        </div>
                        <div class="stepper-step" data-step="3">
                            <div class="stepper-circle">3</div>
                            <div class="stepper-label">Assignment</div>
                        </div>
                    </div>

                    <!-- Step 1: Personal Info -->
                    <div class="step-section active" data-step="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" name="last_name" id="lastName" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Account Setup -->
                    <div class="step-section" data-step="2">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Username <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" name="username" id="username" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password <span class="required-asterisk" id="pwdRequired">*</span></label>
                                <input type="password" class="form-control" name="password" id="password" minlength="6">
                                <div class="form-text" id="pwdHint">Minimum 6 characters.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Role <span class="required-asterisk">*</span></label>
                                <select class="form-select" name="role" id="role" required onchange="toggleRepFields()">
                                    <option value="">Select Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="nurse">School Nurse/Staff</option>
                                    <option value="rep">Class Representative</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Assignment -->
                    <div class="step-section" data-step="3">
                        <div id="repFields">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Assigned Program</label>
                                    <select class="form-select" name="assigned_program_id" id="assignedProgram">
                                        <option value="">Select Program</option>
                                        <?php foreach ($programs as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo e($p['code'] . ' - ' . $p['name']); ?></option>
                                        <?php
endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Year Level</label>
                                    <select class="form-select" name="assigned_year_level_id" id="assignedYearLevel">
                                        <option value="">Select</option>
                                        <?php foreach ($yearLevels as $yl): ?>
                                        <option value="<?php echo $yl['id']; ?>"><?php echo e($yl['name']); ?></option>
                                        <?php
endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Section</label>
                                    <input type="text" class="form-control" name="assigned_section" id="assignedSection" placeholder="e.g. A">
                                </div>
                            </div>
                        </div>
                        <div id="noRepMessage" class="text-center text-muted py-4" style="display:none;">
                            <i class="bi bi-info-circle fs-4 d-block mb-2 opacity-50"></i>
                            <p class="mb-0 fs-sm">Assignment is only applicable for<br><strong>Class Representative</strong> role.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="userStepBack" style="display:none;" onclick="userStepNav(-1)">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="userStepNext" onclick="userStepNav(1)">
                            Next<i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn" style="display:none;">
                            <i class="bi bi-check-lg me-1"></i>Save User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deactivation Reason Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></i>Deactivate User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">You are about to deactivate <strong id="deactivateUsername"></strong>.</p>
                <div class="mb-3">
                    <label class="form-label">Reason for Deactivation <span class="required-asterisk">*</span></label>
                    <select class="form-select" id="deactivationReason" required>
                        <option value="">Select a reason...</option>
                        <option value="Violation of policies">Violation of policies</option>
                        <option value="End of employment/enrollment">End of employment/enrollment</option>
                        <option value="Account inactivity">Account inactivity</option>
                        <option value="Requested by user">Requested by user</option>
                        <option value="Security concern">Security concern</option>
                        <option value="Other">Other</option>
                    </select>
                    <div class="invalid-feedback">Please select a reason.</div>
                </div>
                <div class="mb-0" id="otherReasonGroup" style="display:none;">
                    <label class="form-label">Please specify <span class="required-asterisk">*</span></label>
                    <textarea class="form-control" id="otherReasonText" rows="3" placeholder="Enter reason for deactivation..."></textarea>
                    <div class="invalid-feedback">Please provide a reason.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmDeactivateBtn">
                    <i class="bi bi-person-slash me-1"></i>Deactivate
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const userModal = new bootstrap.Modal(document.getElementById('userModal'));
let currentUserStep = 1;
const totalUserSteps = 3;

function goToUserStep(step) {
    currentUserStep = step;
    // Update stepper indicators
    document.querySelectorAll('#userStepper .stepper-step').forEach(s => {
        const sStep = parseInt(s.dataset.step);
        s.classList.remove('active', 'completed');
        if (sStep === step) s.classList.add('active');
        else if (sStep < step) s.classList.add('completed');
    });
    // Update completed circles with check icon
    document.querySelectorAll('#userStepper .stepper-step').forEach(s => {
        const sStep = parseInt(s.dataset.step);
        const circle = s.querySelector('.stepper-circle');
        if (sStep < step) circle.innerHTML = '<i class="bi bi-check-lg"></i>';
        else circle.textContent = sStep;
    });
    // Show/hide step sections
    document.querySelectorAll('#userModal .step-section').forEach(sec => {
        sec.classList.toggle('active', parseInt(sec.dataset.step) === step);
    });
    // Show/hide nav buttons
    document.getElementById('userStepBack').style.display = step > 1 ? '' : 'none';
    document.getElementById('userStepNext').style.display = step < totalUserSteps ? '' : 'none';
    document.getElementById('submitBtn').style.display = step === totalUserSteps ? '' : 'none';
}

function userStepNav(dir) {
    const next = currentUserStep + dir;
    if (next < 1 || next > totalUserSteps) return;

    // Validate current step before moving forward
    if (dir === 1) {
        const currentSection = document.querySelector(`.step-section[data-step="${currentUserStep}"]`);
        const requiredFields = currentSection.querySelectorAll('[required]');
        let stepValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                stepValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
            // Also check HTML5 validity (e.g. email format, minlength)
            if (!field.checkValidity()) {
                stepValid = false;
                field.classList.add('is-invalid');
            }
        });

        if (!stepValid) {
            // Shake the current step section for visual feedback
            currentSection.style.animation = 'none';
            currentSection.offsetHeight; // trigger reflow
            currentSection.style.animation = 'shake 0.4s ease';
            return;
        }
    }

    goToUserStep(next);
}

// Clear validation errors on input/change within stepper fields
document.querySelectorAll('#userForm .step-section input[required], #userForm .step-section select[required]').forEach(field => {
    field.addEventListener('input', () => field.classList.remove('is-invalid'));
    field.addEventListener('change', () => field.classList.remove('is-invalid'));
});

function toggleRepFields() {
    const isRep = document.getElementById('role').value === 'rep';
    document.getElementById('repFields').style.display = isRep ? 'block' : 'none';
    document.getElementById('noRepMessage').style.display = isRep ? 'none' : 'block';
}

function openUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('formAction').value = 'create';
    document.getElementById('userId').value = 0;
    document.getElementById('userForm').reset();
    document.getElementById('password').required = true;
    document.getElementById('pwdRequired').style.display = 'inline';
    document.getElementById('pwdHint').textContent = 'Minimum 6 characters.';
    document.getElementById('repFields').style.display = 'none';
    document.getElementById('noRepMessage').style.display = 'block';
    document.getElementById('userForm').classList.remove('was-validated');
    goToUserStep(1);
    userModal.show();
}

function editUser(id) {
    const formData = new FormData();
    formData.append('action', 'get');
    formData.append('id', id);
    formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');
    
    fetch('users.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const u = data.user;
                document.getElementById('userModalTitle').textContent = 'Edit User';
                document.getElementById('formAction').value = 'update';
                document.getElementById('userId').value = u.id;
                document.getElementById('firstName').value = u.first_name;
                document.getElementById('lastName').value = u.last_name;
                document.getElementById('username').value = u.username;
                document.getElementById('email').value = u.email || '';
                document.getElementById('role').value = u.role;
                document.getElementById('password').value = '';
                document.getElementById('password').required = false;
                document.getElementById('pwdRequired').style.display = 'none';
                document.getElementById('pwdHint').textContent = 'Leave blank to keep current password.';
                document.getElementById('assignedProgram').value = u.assigned_program_id || '';
                document.getElementById('assignedYearLevel').value = u.assigned_year_level_id || '';
                document.getElementById('assignedSection').value = u.assigned_section || '';
                toggleRepFields();
                document.getElementById('userForm').classList.remove('was-validated');
                goToUserStep(1);
                userModal.show();
            }
        });
}

const deactivateModal = new bootstrap.Modal(document.getElementById('deactivateModal'));
let deactivateUserId = null;

function toggleUserStatus(id, currentStatus, username) {
    if (currentStatus === 'active') {
        // Deactivating — show Bootstrap modal with reason dropdown
        deactivateUserId = id;
        document.getElementById('deactivateUsername').textContent = username;
        document.getElementById('deactivationReason').value = '';
        document.getElementById('deactivationReason').classList.remove('is-invalid');
        document.getElementById('otherReasonGroup').style.display = 'none';
        document.getElementById('otherReasonText').value = '';
        document.getElementById('otherReasonText').classList.remove('is-invalid');
        deactivateModal.show();
    } else {
        // Activating — simple confirm
        showConfirm('Activate User?', `Are you sure you want to activate "${username}"?`, 'Yes, activate').then(result => {
            if (result.isConfirmed) {
                submitToggleStatus(id, '');
            }
        });
    }
}

// Show/hide "Other" comment textarea
document.getElementById('deactivationReason').addEventListener('change', function() {
    if (this.value) this.classList.remove('is-invalid');
    const otherGroup = document.getElementById('otherReasonGroup');
    if (this.value === 'Other') {
        otherGroup.style.display = 'block';
        document.getElementById('otherReasonText').value = '';
    } else {
        otherGroup.style.display = 'none';
    }
});

document.getElementById('confirmDeactivateBtn').addEventListener('click', function() {
    const select = document.getElementById('deactivationReason');
    const otherText = document.getElementById('otherReasonText');
    let reason = select.value;

    if (!reason) {
        select.classList.add('is-invalid');
        return;
    }

    if (reason === 'Other') {
        const comment = otherText.value.trim();
        if (!comment) {
            otherText.classList.add('is-invalid');
            return;
        }
        reason = 'Other: ' + comment;
    }

    deactivateModal.hide();
    submitToggleStatus(deactivateUserId, reason);
});

// Remove validation styling on textarea input
document.getElementById('otherReasonText').addEventListener('input', function() {
    if (this.value.trim()) this.classList.remove('is-invalid');
});

function submitToggleStatus(id, reason) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('id', id);
    formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');
    if (reason) formData.append('deactivation_reason', reason);

    fetch('users.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) scheduleToast('success', data.message);
            else showToast('error', data.message);
        });
}

// Form submission via AJAX
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
        this.classList.add('was-validated');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('users.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                userModal.hide();
                scheduleToast('success', data.message);
            } else {
                showToast('error', data.message);
            }
        })
        .catch(err => showToast('error', 'An error occurred. Please try again.'));
});

// Handle pre-fill from Rep Requests
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('prefill_request')) {
        openUserModal();
        document.getElementById('firstName').value = urlParams.get('first_name') || '';
        document.getElementById('lastName').value = urlParams.get('last_name') || '';
        document.getElementById('username').value = urlParams.get('username') || '';
        document.getElementById('role').value = urlParams.get('role') || 'rep';
        document.getElementById('assignedProgram').value = urlParams.get('prog') || '';
        document.getElementById('assignedYearLevel').value = urlParams.get('yl') || '';
        document.getElementById('assignedSection').value = urlParams.get('sec') || '';
        toggleRepFields();
        
        // Start on step 1 so admin can review all prefilled data
        goToUserStep(1);
    }
});
</script>
