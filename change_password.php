<?php
$pageTitle = 'Change Password';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$db = Database::getInstance();
$user = getCurrentUser();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if (strlen($newPassword) < 6)
            $errors[] = 'New password must be at least 6 characters.';
        if ($newPassword !== $confirmPassword)
            $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            // Verify current password
            $userData = $db->fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
            if (!password_verify($currentPassword, $userData['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
            else {
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
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="font-size: 0.85rem; border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                        <?php
    endforeach; ?>
                    </ul>
                </div>
                <?php
endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <?php csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="required-asterisk">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control login-input-pwd" id="current_password" name="current_password" required>
                            <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" data-target="current_password" tabindex="-1" title="Toggle visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please enter your current password.</div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="required-asterisk">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control login-input-pwd" id="new_password" name="new_password" minlength="6" required>
                            <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" data-target="new_password" tabindex="-1" title="Toggle visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                        <div class="invalid-feedback">Please enter a new password (min 6 characters).</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="required-asterisk">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control login-input-pwd" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" data-target="confirm_password" tabindex="-1" title="Toggle visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please confirm your new password.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
