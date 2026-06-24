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
    
    <!-- 
      Offline-First Localization (Capstone Defense Documentation):
      External CDN links and hosted Google Fonts have been replaced with local assets (assets/vendor and assets/fonts) 
      to ensure the portal is fully operational on intranet installations without any active internet connection.
    -->
    <!-- Local Inter Web Fonts and Main Stylesheet (Offline-First compliant) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <!-- Local Bootstrap 5 CSS -->
    <link href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Local Bootstrap Icons CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
    
    <!-- Local SweetAlert2 CSS -->
    <link href="<?= BASE_URL ?>assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Login Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login.css">
</head>
<body class="d-flex align-items-center justify-content-center">

    <div class="login-container">
        <div class="login-header">
            <div class="login-logo" style="background-color: var(--primary-light);">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1>Security PIN Required</h1>
            <p>Please enter your 6-digit Security PIN to verify your identity.</p>
        </div>

        <form action="<?= BASE_URL ?>auth/login_2fa_process.php" method="POST" id="login2faForm">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- 2FA Code Field -->
            <div class="form-group">
                <label for="code" class="form-label">Security PIN</label>
                <div class="input-group-custom">
                    <input type="password" 
                           id="code" 
                           name="code" 
                           class="form-control-custom text-center" 
                           placeholder="••••••" 
                           required 
                           maxlength="6"
                           pattern="\d{6}"
                           inputmode="numeric"
                           autofocus
                           style="letter-spacing: 10px; font-size: 24px; font-weight: 700;">
                    <i class="bi bi-lock input-icon"></i>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-btn">
                <span>Confirm PIN</span>
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

    <!-- Local SweetAlert2 JS -->
    <script src="<?= BASE_URL ?>assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

    <!-- Session Flash Alerts Rendering -->
    <?php require_once __DIR__ . '/../includes/alert.php'; ?>
</body>
</html>
