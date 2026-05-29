<?php
// auth/login.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings_helper.php';

// If user is already logged in, redirect them to the index page
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    if (!defined('TESTING')) exit;
}

// Generate CSRF Token for Form Security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch settings database values
$loginPdo = Database::getInstance()->getConnection();
$clinicLogoSetting = get_setting($loginPdo, 'clinic_logo', '');
$clinicNameSetting = get_setting($loginPdo, 'clinic_name', 'Barangay Sinalhan');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sinalhan Health Center</title>
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Login Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login.css">
</head>
<body class="d-flex align-items-center justify-content-center">

    <div class="login-container">
        <div class="login-header">
            <?php if (!empty($clinicLogoSetting)): ?>
                <div class="login-logo" style="overflow: hidden; background: #ffffff; border: 2px solid var(--primary-color);">
                    <img src="<?= BASE_URL . $clinicLogoSetting ?>" alt="Logo" style="height: 100%; width: 100%; object-fit: contain; padding: 2px;">
                </div>
            <?php else: ?>
                <div class="login-logo">
                    <i class="bi bi-hospital"></i>
                </div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($clinicNameSetting) ?></h1>
            <p>Patient Management System</p>
        </div>

        <form action="<?= BASE_URL ?>auth/login_process.php" method="POST" id="loginForm">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Username Field -->
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-group-custom">
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control-custom" 
                           placeholder="Enter your username" 
                           required 
                           autocomplete="username" 
                           autofocus>
                    <i class="bi bi-person input-icon"></i>
                </div>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group-custom">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control-custom" 
                           placeholder="Enter your password" 
                           required 
                           autocomplete="current-password">
                    <i class="bi bi-lock input-icon"></i>
                    <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-btn">
                <span>Login</span>
                <i class="bi bi-box-arrow-in-right"></i>
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; 2026 Sinalhan Health Center</p>
            <p>Trimex Colleges Capstone Project</p>
        </div>
    </div>

    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

    <!-- Native JavaScript for Form validation and UX enhancements -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');
            const loginForm = document.querySelector('#loginForm');

            // Password visibility toggle logic
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Toggle the eye icon
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });

            // Basic client-side validation
            loginForm.addEventListener('submit', function(e) {
                const usernameInput = document.querySelector('#username').value.trim();
                const passwordInput = document.querySelector('#password').value;

                if (!usernameInput || !passwordInput) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Input',
                        text: 'Please fill in both fields before submitting.',
                        confirmButtonColor: '#0D7377'
                    });
                }
            });
        });
    </script>

    <!-- Session Flash Alerts Rendering -->
    <?php require_once __DIR__ . '/../includes/alert.php'; ?>
</body>
</html>
