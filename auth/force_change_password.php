<?php
// auth/force_change_password.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings_helper.php';

// Verify that the user is logged in and actually needs to change their password
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
if (!isset($_SESSION['must_change_password']) || $_SESSION['must_change_password'] != 1) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Generate CSRF Token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = Database::getInstance()->getConnection();
$clinicLogoSetting = get_setting($pdo, 'clinic_logo', '');
$clinicNameSetting = get_setting($pdo, 'clinic_name', 'Barangay Sinalhan');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Force Password Update - Sinalhan Health Center</title>
    
    <!-- Offline-First Local Styles (Capstone compliance) -->
    <link href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="<?= BASE_URL ?>assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login.css">
</head>
<body class="d-flex align-items-center justify-content-center">

    <div class="login-container" style="max-width: 480px;">
        <div class="login-header">
            <?php if (!empty($clinicLogoSetting)): ?>
                <div class="login-logo" style="overflow: hidden; background: #ffffff; border: 2px solid var(--primary-color);">
                    <img src="<?= BASE_URL . $clinicLogoSetting ?>" alt="Logo" style="height: 100%; width: 100%; object-fit: contain; padding: 2px;">
                </div>
            <?php else: ?>
                <div class="login-logo" style="background-color: #f39c12;">
                    <i class="bi bi-shield-exclamation text-white"></i>
                </div>
            <?php endif; ?>
            <h1>Security Update</h1>
            <p>An administrator has reset your password. You must set a new secure password to continue.</p>
        </div>

        <form action="<?= BASE_URL ?>auth/force_change_password_process.php" method="POST" id="forcePasswordForm">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- New Password Field -->
            <div class="form-group mb-3">
                <label for="new_password" class="form-label font-weight-bold text-dark">New Password <span class="text-danger">*</span></label>
                <div class="input-group-custom">
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           class="form-control-custom" 
                           placeholder="Minimum 8 characters" 
                           required 
                           autofocus>
                    <i class="bi bi-lock input-icon"></i>
                </div>
                <small class="text-secondary" style="font-size: 11px;">Must include uppercase, lowercase, numbers, and special symbols.</small>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group mb-4">
                <label for="confirm_password" class="form-label font-weight-bold text-dark">Confirm New Password <span class="text-danger">*</span></label>
                <div class="input-group-custom">
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control-custom" 
                           placeholder="Confirm new password" 
                           required>
                    <i class="bi bi-lock-fill input-icon"></i>
                </div>
            </div>

            <button type="submit" class="login-btn bg-warning text-dark border-0">
                <span>Update & Continue</span>
                <i class="bi bi-shield-check-fill"></i>
            </button>
            
            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>auth/logout.php" class="text-decoration-none text-danger small"><i class="bi bi-box-arrow-left"></i> Cancel and Logout</a>
            </div>
        </form>

        <div class="login-footer">
            <p>&copy; 2026 Sinalhan Health Center</p>
            <p>Trimex Colleges Capstone Project</p>
        </div>
    </div>

    <!-- Local sweetalert JS (Offline-First compliant) -->
    <script src="<?= BASE_URL ?>assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

    <!-- Client-side validation -->
    <script>
        document.getElementById('forcePasswordForm').addEventListener('submit', function(e) {
            const pass = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;

            // Password complexity regex checks
            const hasUpper = /[A-Z]/.test(pass);
            const hasLower = /[a-z]/.test(pass);
            const hasDigit = /[0-9]/.test(pass);
            const hasSpecial = /[^A-Za-z0-9]/.test(pass);

            if (pass.length < 8 || !hasUpper || !hasLower || !hasDigit || !hasSpecial) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Weak Password',
                        text: 'New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
                        confirmButtonColor: '#0D7377'
                    });
                } else {
                    alert('Weak Password: New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
                }
                return;
            }

            if (pass !== confirm) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Mismatch',
                        text: 'Passwords do not match. Please verify.',
                        confirmButtonColor: '#0D7377'
                    });
                } else {
                    alert('Mismatch: Passwords do not match. Please verify.');
                }
            }
        });
    </script>

    <!-- Session Flash Alerts Rendering (SweetAlert2 popups) -->
    <?php require_once __DIR__ . '/../includes/alert.php'; ?>
</body>
</html>
