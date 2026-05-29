<?php
// auth/login_2fa.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/app.php';

// Verify that user passed the first stage of login
if (!isset($_SESSION['temp_2fa_user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Generate CSRF Token for Form Security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - Sinalhan Health Center</title>
    
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
            <div class="login-logo" style="background-color: var(--primary-light);">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1>Two-Factor Auth</h1>
            <p>Enter the 6-digit code from your authenticator app.</p>
        </div>

        <form action="<?= BASE_URL ?>auth/login_2fa_process.php" method="POST" id="login2faForm">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- 2FA Code Field -->
            <div class="form-group">
                <label for="code" class="form-label">Authenticator Code</label>
                <div class="input-group-custom">
                    <input type="text" 
                           id="code" 
                           name="code" 
                           class="form-control-custom text-center" 
                           placeholder="000000" 
                           required 
                           maxlength="6"
                           pattern="\d{6}"
                           autocomplete="one-time-code" 
                           autofocus
                           style="letter-spacing: 6px; font-size: 20px; font-weight: 700;">
                    <i class="bi bi-key input-icon"></i>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-btn">
                <span>Verify Code</span>
                <i class="bi bi-check-circle-fill"></i>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>auth/logout.php" class="text-decoration-none small" style="color: var(--primary-light); font-weight: 600;">
                <i class="bi bi-arrow-left"></i> Back to Login
            </a>
        </div>

        <div class="login-footer">
            <p>&copy; 2026 Sinalhan Health Center</p>
        </div>
    </div>

    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

    <!-- Session Flash Alerts Rendering -->
    <?php require_once __DIR__ . '/../includes/alert.php'; ?>
</body>
</html>
