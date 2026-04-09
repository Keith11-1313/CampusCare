<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . getDashboardUrl($_SESSION['user_role']));
    exit;
}

$error = '';
$forgotSuccess = '';
$forgotError = '';

// ── AJAX: Security Question Flow ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $db = Database::getInstance();
    $action = $_POST['ajax_action'];

    // Step 1: Fetch the security question for a given username
    if ($action === 'get_security_question') {
        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Please enter your username.']);
            exit;
        }

        $user = $db->fetch("SELECT id, status, security_question FROM users WHERE username = ?", [$username]);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'No account found with that username.']);
            exit;
        }
        if ($user['status'] === 'inactive') {
            echo json_encode(['success' => false, 'message' => 'This account has been deactivated. Please contact the administrator directly.']);
            exit;
        }
        if (empty($user['security_question'])) {
            echo json_encode(['success' => false, 'message' => 'No security question has been set for this account. Please use the "Contact Admin" option instead.']);
            exit;
        }

        echo json_encode(['success' => true, 'question' => $user['security_question']]);
        exit;
    }

    // Step 2: Verify the security answer
    if ($action === 'verify_answer') {
        $username = trim($_POST['username'] ?? '');
        $answer = trim($_POST['answer'] ?? '');

        if (empty($username) || empty($answer)) {
            echo json_encode(['success' => false, 'message' => 'Please provide both username and answer.']);
            exit;
        }

        $user = $db->fetch("SELECT id, security_answer FROM users WHERE username = ? AND status = 'active'", [$username]);

        if (!$user || empty($user['security_answer'])) {
            echo json_encode(['success' => false, 'message' => 'Unable to verify. Please try again.']);
            exit;
        }

        if (!password_verify(strtolower($answer), $user['security_answer'])) {
            echo json_encode(['success' => false, 'message' => 'Incorrect answer. Please try again.']);
            exit;
        }

        // Generate a one-time token to authorize the password reset
        $_SESSION['pw_reset_user_id'] = $user['id'];
        $_SESSION['pw_reset_token'] = bin2hex(random_bytes(16));

        echo json_encode(['success' => true, 'reset_token' => $_SESSION['pw_reset_token']]);
        exit;
    }

    // Step 3: Reset the password
    if ($action === 'reset_password') {
        $token = $_POST['reset_token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token) || !isset($_SESSION['pw_reset_token']) || !hash_equals($_SESSION['pw_reset_token'], $token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired reset session. Please start over.']);
            exit;
        }
        if (empty($newPassword) || strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }

        $userId = $_SESSION['pw_reset_user_id'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);

        // Log the action
        $user = $db->fetch("SELECT first_name, last_name FROM users WHERE id = ?", [$userId]);
        logAccess($userId, 'password_reset_security', ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') . ' reset password via security question');

        // Clear reset session
        unset($_SESSION['pw_reset_user_id'], $_SESSION['pw_reset_token']);

        echo json_encode(['success' => true, 'message' => 'Password has been reset successfully! You can now log in with your new password.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    }
    elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    }
    else {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE username = ?", [$username]);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Please contact the administrator.';
            }
            else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_data'] = null; // will be loaded on first access
                $_SESSION['last_activity'] = time();

                // Update last login
                $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

                // Log access
                logAccess($user['id'], 'login', $user['first_name'] . ' ' . $user['last_name'] . ' logged in');

                header('Location: ' . getDashboardUrl($user['role']));
                exit;
            }
        }
        else {
            $error = 'Invalid username or password.';
            // Log failed attempt
            $db->query(
                "INSERT INTO access_logs (user_id, action, description, ip_address) VALUES (NULL, 'login_failed', ?, ?)",
            ['Failed login attempt for username: ' . $username, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']
            );
        }
    }
}

// Handle forgot password (Contact Admin) form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_submit'])) {
    $forgotUsername = trim($_POST['forgot_username'] ?? '');
    $forgotReason = trim($_POST['forgot_reason'] ?? '');

    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $forgotError = 'Invalid security token. Please try again.';
    }
    elseif (empty($forgotUsername)) {
        $forgotError = 'Please enter your username.';
    }
    elseif (empty($forgotReason)) {
        $forgotError = 'Please provide a reason for the password reset request.';
    }
    else {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE username = ?", [$forgotUsername]);

        if (!$user) {
            $forgotError = 'No account found with that username.';
        }
        elseif ($user['status'] === 'inactive') {
            $forgotError = 'This account has been deactivated. Please contact the administrator directly.';
        }
        else {
            // Check for existing pending password reset request
            $pending = $db->fetchColumn(
                "SELECT COUNT(*) FROM current_requests WHERE rep_user_id = ? AND request_type = 'password_reset' AND status = 'pending'",
            [$user['id']]
            );

            if ($pending > 0) {
                $forgotError = 'A password reset request is already pending for this account. Please wait for the admin to process it.';
            }
            else {
                $db->query(
                    "INSERT INTO current_requests (rep_user_id, request_type, nominee_student_id, reason, status) VALUES (?, 'password_reset', NULL, ?, 'pending')",
                [$user['id'], $forgotReason]
                );
                $forgotSuccess = 'Your password reset request has been submitted. Please contact the administrator for your new password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo-main-w.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <!-- Floating background blobs -->
        <div class="login-blob login-blob-1"></div>
        <div class="login-blob login-blob-2"></div>
        <div class="login-blob login-blob-3"></div>
        <div class="login-card login-card-split animate-fade-in">
            <!-- Left Panel: Sign In Form -->
            <div class="login-card-left">
                <div class="login-card-left-header">
                    <h4 class="fw-bold mb-0">Sign In</h4>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center py-2 px-3 login-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo e($error); ?></div>
                </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <?php csrfField(); ?>
                    <input type="hidden" name="login_submit" value="1">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold login-form-label">Username</label>
                        <input type="text" class="form-control login-input" id="username" name="username" 
                               placeholder="Username" required autofocus
                               value="<?php echo e($username ?? ''); ?>">
                        <div class="invalid-feedback">Please enter your username.</div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold login-form-label">Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control login-input login-input-pwd" id="password" name="password" 
                                   placeholder="Password" required>
                            <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please enter your password.</div>
                    </div>

                    <button type="submit" class="btn login-btn-signin w-100 py-2 fw-semibold">
                        Sign In
                    </button>
                </form>

                <div class="login-card-left-footer">
                    <a href="#" class="login-forgot" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                        <i class="bi bi-key me-1"></i>Forgot Password?
                    </a>
                </div>
            </div>

            <!-- Right Panel: Welcome -->
            <div class="login-card-right">
                <div class="login-card-right-content">
                    <img src="<?php echo BASE_URL; ?>/assets/logo-main-w.png" alt="<?php echo APP_NAME; ?>" class="login-welcome-logo">
                    <h3 class="login-welcome-title">Welcome to CampusCare</h3>
                    <p class="login-welcome-subtitle"><?php echo APP_TAGLINE; ?></p>
                    <a href="<?php echo BASE_URL; ?>/index.php" class="btn login-btn-public">
                        <i class="bi bi-arrow-left me-1"></i>Public Page
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="forgotPasswordModalLabel"><i class="bi bi-key me-2 forgot-modal-icon"></i>Forgot Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Choose Method -->
                    <div id="forgotStep1">
                        <p class="text-muted mb-3 forgot-description-lg">How would you like to recover your password?</p>
                        <div class="d-flex flex-column gap-3">
                            <button type="button" class="forgot-method-card" onclick="showForgotMethod('admin')">
                                <div class="forgot-method-icon admin-icon">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <div class="fw-bold forgot-method-title">Contact Admin</div>
                                    <div class="forgot-method-desc">Submit a request to the administrator to reset your password</div>
                                </div>
                                <i class="bi bi-chevron-right forgot-method-chevron"></i>
                            </button>
                            <button type="button" class="forgot-method-card" onclick="showForgotMethod('security')">
                                <div class="forgot-method-icon security-icon">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <div>
                                    <div class="fw-bold forgot-method-title">Security Question</div>
                                    <div class="forgot-method-desc">Answer your security question to reset your password instantly</div>
                                </div>
                                <i class="bi bi-chevron-right forgot-method-chevron"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Option A: Contact Admin -->
                    <div id="forgotAdminSection" class="d-none">
                        <form method="POST" action="" id="forgotAdminForm">
                            <?php csrfField(); ?>
                            <input type="hidden" name="forgot_submit" value="1">
                            
                            <div class="forgot-section-title">
                                <div class="badge-icon admin-badge">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <span class="fw-semibold forgot-section-label">Contact Admin</span>
                            </div>

                            <p class="text-muted mb-3 forgot-description">Enter your username and a reason for the password reset. The administrator will be notified.</p>
                            
                            <div class="mb-3">
                                <label for="forgot_username" class="form-label fw-semibold forgot-form-label">Username</label>
                                <div class="position-relative">
                                    <i class="bi bi-person position-absolute text-muted forgot-input-icon"></i>
                                    <input type="text" class="form-control forgot-input-padded" id="forgot_username" name="forgot_username" 
                                           placeholder="Enter your username" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="forgot_reason" class="form-label fw-semibold forgot-form-label">Reason</label>
                                <textarea class="form-control" id="forgot_reason" name="forgot_reason" rows="3" 
                                          placeholder="Briefly explain why you need a password reset..." required></textarea>
                            </div>

                            <div class="alert alert-info mb-3 py-2 px-3 forgot-info-alert">
                                <i class="bi bi-info-circle-fill me-1"></i>
                                After submitting, please contact the administrator to receive your new password.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary forgot-rounded-btn" onclick="backToMethodChoice()">
                                    <i class="bi bi-arrow-left me-1"></i>Back
                                </button>
                                <button type="submit" class="btn btn-primary fw-semibold flex-fill forgot-rounded-btn">
                                    <i class="bi bi-send-fill me-1"></i>Submit Request
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Option B: Security Question -->
                    <div id="forgotSecuritySection" class="d-none">

                        <div class="forgot-section-title">
                            <div class="badge-icon security-badge">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <span class="fw-semibold forgot-section-label">Security Question</span>
                        </div>

                        <!-- Step Progress Dots -->
                        <div class="forgot-step-indicator" id="securityStepDots">
                            <div class="forgot-step-dot active" id="secDot1"></div>
                            <div class="forgot-step-dot" id="secDot2"></div>
                            <div class="forgot-step-dot" id="secDot3"></div>
                        </div>

                        <!-- Security Step 1: Enter Username -->
                        <div id="securityStep1">
                            <p class="text-muted mb-3 forgot-description">Enter your username to retrieve your security question.</p>
                            <div class="mb-3">
                                <label for="security_username" class="form-label fw-semibold forgot-form-label">Username</label>
                                <div class="position-relative">
                                    <i class="bi bi-person position-absolute text-muted forgot-input-icon"></i>
                                    <input type="text" class="form-control forgot-input-padded" id="security_username" 
                                           placeholder="Enter your username">
                                </div>
                            </div>
                            <div id="securityUsernameError" class="alert alert-danger py-2 px-3 mb-3 forgot-error-alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <span></span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary forgot-rounded-btn" onclick="backToMethodChoice()">
                                    <i class="bi bi-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-primary fw-semibold flex-fill forgot-rounded-btn" id="btnFetchQuestion" onclick="fetchSecurityQuestion()">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" id="fetchSpinner"></span>
                                    Continue<i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Security Step 2: Answer Question -->
                        <div id="securityStep2" class="d-none">
                            <p class="text-muted mb-3 forgot-description">Answer the security question below:</p>
                            <div class="mb-3 p-3 forgot-question-box">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-question-circle-fill forgot-question-icon"></i>
                                    <span class="fw-semibold forgot-question-text" id="displaySecurityQuestion"></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="security_answer" class="form-label fw-semibold forgot-form-label">Your Answer</label>
                                <input type="text" class="form-control" id="security_answer" placeholder="Type your answer...">
                            </div>
                            <div id="securityAnswerError" class="alert alert-danger py-2 px-3 mb-3 forgot-error-alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <span></span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary forgot-rounded-btn" onclick="securityGoBack(1)">
                                    <i class="bi bi-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-primary fw-semibold flex-fill forgot-rounded-btn" id="btnVerifyAnswer" onclick="verifySecurityAnswer()">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" id="verifySpinner"></span>
                                    Verify<i class="bi bi-check-lg ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Security Step 3: Set New Password -->
                        <div id="securityStep3" class="d-none">
                            <div class="d-flex align-items-center gap-2 mb-3 p-3 forgot-verified-box">
                                <i class="bi bi-check-circle-fill forgot-verified-icon"></i>
                                <span class="forgot-verified-text">Identity verified! Set your new password below.</span>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label fw-semibold forgot-form-label">New Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control login-input-pwd" id="new_password" 
                                           placeholder="Minimum 6 characters" minlength="6">
                                    <button class="btn btn-link position-absolute text-muted p-0 login-pwd-toggle" type="button" onclick="toggleNewPwd(this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text forgot-form-hint">Minimum 6 characters.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label fw-semibold forgot-form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       placeholder="Re-enter your new password">
                            </div>
                            <div id="securityResetError" class="alert alert-danger py-2 px-3 mb-3 forgot-error-alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <span></span>
                            </div>
                            <button type="button" class="btn btn-primary fw-semibold w-100 forgot-rounded-btn" id="btnResetPassword" onclick="resetPassword()">
                                <span class="spinner-border spinner-border-sm me-1 d-none" id="resetSpinner"></span>
                                <i class="bi bi-check-lg me-1"></i>Reset Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.23/dist/sweetalert2.all.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });

        // Form validation
        (function() {
            const form = document.querySelector('.needs-validation');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();

        // Show forgot password result messages
        <?php if ($forgotSuccess): ?>
        Swal.fire({
            icon: 'success',
            title: 'Request Submitted',
            text: <?php echo json_encode($forgotSuccess); ?>,
            confirmButtonColor: '#0d6efd'
        });
        <?php
elseif ($forgotError): ?>
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: <?php echo json_encode($forgotError); ?>,
            confirmButtonColor: '#0d6efd'
        });
        <?php
endif; ?>

        // ─── Forgot Password Modal Logic ───
        const csrfToken = '<?php echo getCSRFToken(); ?>';
        let securityResetToken = '';

        function showForgotMethod(method) {
            document.getElementById('forgotStep1').classList.add('d-none');
            if (method === 'admin') {
                document.getElementById('forgotAdminSection').classList.remove('d-none');
            } else {
                document.getElementById('forgotSecuritySection').classList.remove('d-none');
            }
        }

        function updateSecurityDots(activeStep) {
            for (let i = 1; i <= 3; i++) {
                const dot = document.getElementById('secDot' + i);
                dot.className = 'forgot-step-dot';
                if (i === activeStep) dot.classList.add('active');
                else if (i < activeStep) dot.classList.add('done');
            }
        }

        function backToMethodChoice() {
            document.getElementById('forgotStep1').classList.remove('d-none');
            document.getElementById('forgotAdminSection').classList.add('d-none');
            document.getElementById('forgotSecuritySection').classList.add('d-none');
            // Reset security question steps
            document.getElementById('securityStep1').classList.remove('d-none');
            document.getElementById('securityStep2').classList.add('d-none');
            document.getElementById('securityStep3').classList.add('d-none');
            updateSecurityDots(1);
            // Clear fields
            document.getElementById('security_username').value = '';
            document.getElementById('security_answer').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            hideAllSecurityErrors();
        }

        function hideAllSecurityErrors() {
            ['securityUsernameError', 'securityAnswerError', 'securityResetError'].forEach(id => {
                document.getElementById(id).style.display = 'none';
            });
        }

        function showSecurityError(elementId, message) {
            const el = document.getElementById(elementId);
            el.querySelector('span').textContent = message;
            el.style.display = 'block';
        }

        function securityGoBack(toStep) {
            hideAllSecurityErrors();
            if (toStep === 1) {
                document.getElementById('securityStep2').classList.add('d-none');
                document.getElementById('securityStep1').classList.remove('d-none');
                document.getElementById('security_answer').value = '';
                updateSecurityDots(1);
            }
        }

        // Step 1: Fetch security question
        function fetchSecurityQuestion() {
            const username = document.getElementById('security_username').value.trim();
            if (!username) {
                showSecurityError('securityUsernameError', 'Please enter your username.');
                return;
            }
            hideAllSecurityErrors();

            const btn = document.getElementById('btnFetchQuestion');
            const spinner = document.getElementById('fetchSpinner');
            btn.disabled = true;
            spinner.classList.remove('d-none');

            const formData = new FormData();
            formData.append('ajax_action', 'get_security_question');
            formData.append('username', username);
            formData.append('csrf_token', csrfToken);

            fetch('login.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    if (data.success) {
                        document.getElementById('displaySecurityQuestion').textContent = data.question;
                        document.getElementById('securityStep1').classList.add('d-none');
                        document.getElementById('securityStep2').classList.remove('d-none');
                        updateSecurityDots(2);
                    } else {
                        showSecurityError('securityUsernameError', data.message);
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    showSecurityError('securityUsernameError', 'An error occurred. Please try again.');
                });
        }

        // Step 2: Verify security answer
        function verifySecurityAnswer() {
            const username = document.getElementById('security_username').value.trim();
            const answer = document.getElementById('security_answer').value.trim();
            if (!answer) {
                showSecurityError('securityAnswerError', 'Please enter your answer.');
                return;
            }
            hideAllSecurityErrors();

            const btn = document.getElementById('btnVerifyAnswer');
            const spinner = document.getElementById('verifySpinner');
            btn.disabled = true;
            spinner.classList.remove('d-none');

            const formData = new FormData();
            formData.append('ajax_action', 'verify_answer');
            formData.append('username', username);
            formData.append('answer', answer);
            formData.append('csrf_token', csrfToken);

            fetch('login.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    if (data.success) {
                        securityResetToken = data.reset_token;
                        document.getElementById('securityStep2').classList.add('d-none');
                        document.getElementById('securityStep3').classList.remove('d-none');
                        updateSecurityDots(3);
                    } else {
                        showSecurityError('securityAnswerError', data.message);
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    showSecurityError('securityAnswerError', 'An error occurred. Please try again.');
                });
        }

        // Step 3: Reset password
        function resetPassword() {
            const newPwd = document.getElementById('new_password').value;
            const confirmPwd = document.getElementById('confirm_password').value;
            
            if (!newPwd || newPwd.length < 6) {
                showSecurityError('securityResetError', 'Password must be at least 6 characters.');
                return;
            }
            if (newPwd !== confirmPwd) {
                showSecurityError('securityResetError', 'Passwords do not match.');
                return;
            }
            hideAllSecurityErrors();

            const btn = document.getElementById('btnResetPassword');
            const spinner = document.getElementById('resetSpinner');
            btn.disabled = true;
            spinner.classList.remove('d-none');

            const formData = new FormData();
            formData.append('ajax_action', 'reset_password');
            formData.append('reset_token', securityResetToken);
            formData.append('new_password', newPwd);
            formData.append('confirm_password', confirmPwd);
            formData.append('csrf_token', csrfToken);

            fetch('login.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Password Reset',
                            text: data.message,
                            confirmButtonColor: '#0d6efd'
                        });
                        // Reset the modal state for next open
                        backToMethodChoice();
                    } else {
                        showSecurityError('securityResetError', data.message);
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    showSecurityError('securityResetError', 'An error occurred. Please try again.');
                });
        }

        // Toggle new password visibility
        function toggleNewPwd(btn) {
            const pwd = document.getElementById('new_password');
            const icon = btn.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        // Reset modal state when closed
        document.getElementById('forgotPasswordModal').addEventListener('hidden.bs.modal', function() {
            backToMethodChoice();
            document.getElementById('forgot_username').value = '';
            document.getElementById('forgot_reason').value = '';
        });

        // Allow Enter key on security inputs
        document.getElementById('security_username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); fetchSecurityQuestion(); }
        });
        document.getElementById('security_answer').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); verifySecurityAnswer(); }
        });
    </script>
</body>
</html>
