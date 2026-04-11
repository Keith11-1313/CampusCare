<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// ── AJAX: Verify current password (must run before header.php outputs HTML) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'verify_current') {
    header('Content-Type: application/json');

    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $db = Database::getInstance();
    $user = getCurrentUser();
    $currentPassword = $_POST['current_password'] ?? '';
    if (empty($currentPassword)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your current password.']);
        exit;
    }

    $userData = $db->fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
    if (!password_verify($currentPassword, $userData['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Password verified.']);
    exit;
}

$pageTitle = 'Change Password';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$user = getCurrentUser();
$errors = [];
$success = false;

// ── Handle form submission (update password) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_submit'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }
    else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate
        if (empty($currentPassword))
            $errors[] = 'Current password is required.';
        if (empty($newPassword))
            $errors[] = 'New password is required.';
        else {
            $pwdErrors = validatePasswordStrength($newPassword);
            $errors = array_merge($errors, $pwdErrors);
        }
        if ($newPassword !== $confirmPassword)
            $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            // Verify current password
            $userData = $db->fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
            if (!password_verify($currentPassword, $userData['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
            else {
                // Ensure new password is not the same as old password
                if (password_verify($newPassword, $userData['password'])) {
                    $errors[] = 'New password must be different from your current password.';
                } else {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $user['id']]);
                    logAccess($user['id'], 'change_password', $user['first_name'] . ' ' . $user['last_name'] . ' changed password');
                    $success = true;
                    setFlashMessage('success', 'Password changed successfully!');
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-key me-2"></i>Change Password</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo getDashboardUrl($user['role']); ?>">Dashboard</a></li>
            <li class="breadcrumb-item active">Change Password</li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger d-flex align-items-center" style="font-size: 0.85rem; border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo e($err); ?>
                </div>
                <?php endforeach; ?>

                <!-- Stepper -->
                <div class="stepper" id="passwordStepper">
                    <div class="stepper-step active" id="stepItem1">
                        <div class="stepper-circle">1</div>
                        <div class="stepper-label">Verify Password</div>
                    </div>
                    <div class="stepper-step" id="stepItem2">
                        <div class="stepper-circle">2</div>
                        <div class="stepper-label">New Password</div>
                    </div>
                </div>

                <form method="POST" class="needs-validation" novalidate id="changePasswordForm">
                    <?php csrfField(); ?>
                    <input type="hidden" name="change_submit" value="1">
                    
                    <!-- Step 1: Current Password -->
                    <div class="step-section active" id="stepSection1">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="required-asterisk">*</span></label>
                            <div class="position-relative">
                                <input type="password" class="form-control login-input-pwd" id="current_password" name="current_password" required>
                                <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" data-target="current_password" tabindex="-1" title="Toggle visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please enter your current password.</div>
                            <div id="currentPwdError" class="alert alert-danger d-flex align-items-center mt-2" style="font-size: 0.82rem; border-radius: 8px; display: none !important;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <span></span>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" id="btnVerifyCurrent">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="verifySpinner"></span>
                            <i class="bi bi-shield-check me-1" id="verifyIcon"></i>Verify & Continue
                        </button>
                    </div>

                    <!-- Step 2: New Password -->
                    <div class="step-section" id="stepSection2">
                        <div class="alert alert-success d-flex align-items-center mb-3" style="font-size: 0.82rem; border-radius: 8px;">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <span>Password verified! Set your new password below.</span>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="required-asterisk">*</span></label>
                            <div class="position-relative">
                                <input type="password" class="form-control login-input-pwd" id="new_password" name="new_password" minlength="8" required disabled>
                                <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" data-target="new_password" tabindex="-1" title="Toggle visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <ul class="pwd-requirements list-unstyled mt-1 mb-0" id="pwdRequirements" style="font-size:0.78rem;">
                                <li id="req-length"><i class="bi bi-x-circle text-muted me-1"></i>At least 8 characters</li>
                                <li id="req-upper"><i class="bi bi-x-circle text-muted me-1"></i>One uppercase letter</li>
                                <li id="req-lower"><i class="bi bi-x-circle text-muted me-1"></i>One lowercase letter</li>
                                <li id="req-number"><i class="bi bi-x-circle text-muted me-1"></i>One number</li>
                                <li id="req-special"><i class="bi bi-x-circle text-muted me-1"></i>One special character</li>
                                <li id="req-notold"><i class="bi bi-x-circle text-muted me-1"></i>Must not be the same as current password</li>
                            </ul>
                            <div class="invalid-feedback">Please enter a valid new password.</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="required-asterisk">*</span></label>
                            <div class="position-relative">
                                <input type="password" class="form-control login-input-pwd" id="confirm_password" name="confirm_password" required disabled>
                                <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" data-target="confirm_password" tabindex="-1" title="Toggle visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please confirm your new password.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="btnSubmit" disabled>
                            <i class="bi bi-check-lg me-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.querySelectorAll('.login-pwd-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var input = document.getElementById(this.getAttribute('data-target'));
        var icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
});

// CSRF token
const csrfToken = '<?php echo getCSRFToken(); ?>';
let currentPasswordVerified = false;

// Verify current password via AJAX
document.getElementById('btnVerifyCurrent').addEventListener('click', function() {
    const currentPwd = document.getElementById('current_password').value;
    const errorEl = document.getElementById('currentPwdError');

    // Hide previous messages
    errorEl.style.display = 'none';

    if (!currentPwd) {
        errorEl.querySelector('span').textContent = 'Please enter your current password.';
        errorEl.style.display = 'flex';
        return;
    }

    const btn = this;
    const spinner = document.getElementById('verifySpinner');
    const icon = document.getElementById('verifyIcon');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');

    const formData = new FormData();
    formData.append('ajax_action', 'verify_current');
    formData.append('current_password', currentPwd);
    formData.append('csrf_token', csrfToken);

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');

            if (data.success) {
                currentPasswordVerified = true;

                // Update stepper: step 1 completed, step 2 active
                document.getElementById('stepItem1').classList.remove('active');
                document.getElementById('stepItem1').classList.add('completed');
                document.getElementById('stepItem1').querySelector('.stepper-circle').innerHTML = '<i class="bi bi-check-lg"></i>';
                document.getElementById('stepItem2').classList.add('active');

                // Swap step sections
                document.getElementById('stepSection1').classList.remove('active');
                document.getElementById('stepSection2').classList.add('active');

                // Enable new password fields
                document.getElementById('new_password').disabled = false;
                document.getElementById('confirm_password').disabled = false;
                document.getElementById('btnSubmit').disabled = false;

                // Focus the new password field
                document.getElementById('new_password').focus();
            } else {
                errorEl.querySelector('span').textContent = data.message;
                errorEl.style.display = 'flex';
            }
        })
        .catch(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
            errorEl.querySelector('span').textContent = 'An error occurred. Please try again.';
            errorEl.style.display = 'flex';
        });
});

// Allow Enter key on current password to trigger verify
document.getElementById('current_password').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !currentPasswordVerified) {
        e.preventDefault();
        document.getElementById('btnVerifyCurrent').click();
    }
});

// Live password requirements check
function checkPwdRequirements() {
    const pwd = document.getElementById('new_password').value;
    const currentPwd = document.getElementById('current_password').value;
    const notSameAsOld = pwd.length > 0 && currentPwd.length > 0 && pwd !== currentPwd;
    const rules = [
        { id: 'req-length', test: pwd.length >= 8 },
        { id: 'req-upper', test: /[A-Z]/.test(pwd) },
        { id: 'req-lower', test: /[a-z]/.test(pwd) },
        { id: 'req-number', test: /[0-9]/.test(pwd) },
        { id: 'req-special', test: /[^a-zA-Z0-9]/.test(pwd) },
        { id: 'req-notold', test: notSameAsOld }
    ];
    rules.forEach(function(rule) {
        const el = document.getElementById(rule.id);
        const icon = el.querySelector('i');
        if (pwd.length === 0) {
            icon.className = 'bi bi-x-circle text-muted me-1';
        } else if (rule.test) {
            icon.className = 'bi bi-check-circle-fill text-success me-1';
        } else {
            icon.className = 'bi bi-x-circle-fill text-danger me-1';
        }
    });
}
document.getElementById('new_password').addEventListener('input', checkPwdRequirements);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
