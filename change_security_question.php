<?php
$pageTitle = 'Security Question';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$db = Database::getInstance();
$user = getCurrentUser();
$errors = [];
$success = false;

// Fetch current security question
$userData = $db->fetch("SELECT security_question FROM users WHERE id = ?", [$user['id']]);
$currentQuestion = $userData['security_question'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }
    else {
        $currentPassword = $_POST['current_password'] ?? '';
        $securityQuestion = trim($_POST['security_question'] ?? '');
        $securityAnswer = trim($_POST['security_answer'] ?? '');

        // Validate
        if (empty($currentPassword))
            $errors[] = 'Current password is required.';
        if (empty($securityQuestion))
            $errors[] = 'Please select a security question.';
        if (empty($securityAnswer))
            $errors[] = 'Security answer is required.';

        if (empty($errors)) {
            // Verify current password
            $pw = $db->fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
            if (!password_verify($currentPassword, $pw['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
            else {
                $hashedAnswer = password_hash(strtolower($securityAnswer), PASSWORD_DEFAULT);
                $db->query("UPDATE users SET security_question = ?, security_answer = ? WHERE id = ?", [$securityQuestion, $hashedAnswer, $user['id']]);
                logAccess($user['id'], 'change_security_question', $user['first_name'] . ' ' . $user['last_name'] . ' updated security question');
                $success = true;
                setFlashMessage('success', 'Security question updated successfully!');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-shield-lock me-2"></i>Security Question</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo getDashboardUrl($user['role']); ?>">Dashboard</a></li>
            <li class="breadcrumb-item active">Security Question</li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <?php if (!empty($currentQuestion)): ?>
                <div class="alert alert-info mb-3 py-2 px-3" style="font-size: 0.85rem; border-radius: 8px;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Your current security question is set. You can update it below.
                </div>
                <?php else: ?>
                <div class="alert alert-warning mb-3 py-2 px-3" style="font-size: 0.85rem; border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    No security question set. Setting one allows you to reset your password without contacting an admin.
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="font-size: 0.85rem; border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <?php csrfField(); ?>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="required-asterisk">*</span></label>
<<<<<<< HEAD
                        <div class="position-relative">
                            <input type="password" class="form-control login-input-pwd" id="current_password" name="current_password" required>
                            <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" data-target="current_password" tabindex="-1" title="Toggle visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
=======
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
                        <div class="form-text">Required to verify your identity.</div>
                        <div class="invalid-feedback">Please enter your current password.</div>
                    </div>

                    <div class="mb-3">
                        <label for="security_question" class="form-label">Security Question <span class="required-asterisk">*</span></label>
                        <select class="form-select" id="security_question" name="security_question" required>
                            <option value="">Select a question...</option>
                            <option value="What is your mother's maiden name?" <?php echo $currentQuestion === "What is your mother's maiden name?" ? 'selected' : ''; ?>>What is your mother's maiden name?</option>
                            <option value="What is the name of your first pet?" <?php echo $currentQuestion === "What is the name of your first pet?" ? 'selected' : ''; ?>>What is the name of your first pet?</option>
                            <option value="What is your favorite food?" <?php echo $currentQuestion === "What is your favorite food?" ? 'selected' : ''; ?>>What is your favorite food?</option>
                            <option value="What city were you born in?" <?php echo $currentQuestion === "What city were you born in?" ? 'selected' : ''; ?>>What city were you born in?</option>
                            <option value="What is the name of your best friend?" <?php echo $currentQuestion === "What is the name of your best friend?" ? 'selected' : ''; ?>>What is the name of your best friend?</option>
                            <option value="What is your favorite color?" <?php echo $currentQuestion === "What is your favorite color?" ? 'selected' : ''; ?>>What is your favorite color?</option>
                        </select>
                        <div class="invalid-feedback">Please select a security question.</div>
                    </div>

                    <div class="mb-4">
                        <label for="security_answer" class="form-label">Security Answer <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" id="security_answer" name="security_answer" required>
                        <div class="form-text">Case-insensitive. This will replace any existing answer.</div>
                        <div class="invalid-feedback">Please enter your security answer.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-2"></i>Save Security Question
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<<<<<<< HEAD
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

=======
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
<?php require_once __DIR__ . '/includes/footer.php'; ?>
