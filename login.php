<?php
/**
 * CampusCare - Login Page
 * Handles user authentication with form validation and SweetAlert2 feedback
 */

require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . getDashboardUrl($_SESSION['user_role']));
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card animate-fade-in">
            <!-- Logo -->
            <div class="login-logo">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-1" style="color: #1a2332;"><?php echo APP_NAME; ?></h4>
                <p class="text-muted small mb-0"><?php echo APP_TAGLINE; ?></p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center py-2 px-3" role="alert" style="font-size: 0.85rem; border-radius: 8px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo e($error); ?></div>
            </div>
            <?php
endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <?php csrfField(); ?>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="position-relative">
                        <i class="bi bi-person position-absolute text-muted" style="left:12px;top:50%;transform:translateY(-50%);"></i>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your username" required autofocus
                               style="padding-left: 38px;"
                               value="<?php echo e($username ?? ''); ?>">
                    </div>
                    <div class="invalid-feedback">Please enter your username.</div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="position-relative">
                        <i class="bi bi-lock position-absolute text-muted" style="left:12px;top:50%;transform:translateY(-50%);"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required
                               style="padding-left: 38px; padding-right: 45px;">
                        <button class="btn btn-link position-absolute text-muted p-0" type="button" id="togglePassword" style="right:12px;top:50%;transform:translateY(-50%);text-decoration:none;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="<?php echo BASE_URL; ?>/index.php" class="text-muted small text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>Back to Public Page
                </a>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/node_modules/sweetalert2/dist/sweetalert2.all.min.js"></script>
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
    </script>
</body>
</html>
