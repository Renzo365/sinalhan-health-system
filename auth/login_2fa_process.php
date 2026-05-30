<?php
// auth/login_2fa_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/totp.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    if (!defined('TESTING')) exit;
}

try {
    // 1. Verify CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Security Error',
            'message' => 'CSRF verification failed. Request denied.'
        ];
        header('Location: ' . BASE_URL . 'auth/login.php');
        if (!defined('TESTING')) exit;
    }

    // Verify temp session exists
    if (!isset($_SESSION['temp_2fa_user_id'])) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        if (!defined('TESTING')) exit;
    }

    $userId = (int)$_SESSION['temp_2fa_user_id'];
    $code = trim($_POST['code'] ?? '');

    if (empty($code)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Code Required',
            'message' => 'Please enter the 6-digit authenticator code.'
        ];
        header('Location: ' . BASE_URL . 'auth/login_2fa.php');
        if (!defined('TESTING')) exit;
    }

    // 2. Fetch User Secret from DB
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT two_fa_secret, two_fa_enabled FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !$user['two_fa_secret'] || $user['two_fa_enabled'] != 1) {
        throw new Exception('Two-factor authentication is not active on this account.');
    }

    // 3. Verify TOTP Code
    if (TOTP::verifyCode($user['two_fa_secret'], $code)) {
        // Success: Promote session variables to full login status
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $_SESSION['temp_2fa_username'];
        $_SESSION['full_name'] = $_SESSION['temp_2fa_full_name'];
        $_SESSION['role'] = $_SESSION['temp_2fa_role'];
        $_SESSION['theme'] = $_SESSION['temp_2fa_theme'] ?? 'light';
        $_SESSION['font_size'] = $_SESSION['temp_2fa_font_size'] ?? 'normal';
        $_SESSION['login_time'] = time();

        // Clear temporary variables
        unset($_SESSION['temp_2fa_user_id']);
        unset($_SESSION['temp_2fa_username']);
        unset($_SESSION['temp_2fa_full_name']);
        unset($_SESSION['temp_2fa_role']);
        unset($_SESSION['temp_2fa_theme']);
        unset($_SESSION['temp_2fa_font_size']);

        // Update database log
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_login_ip = ? WHERE user_id = ?");
        $updateStmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $userId]);

        log_activity($pdo, 'Logged in (2FA Verified)', 'Auth', $userId, 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Authentication Verified',
            'message' => 'Two-factor code verified successfully.'
        ];

        header('Location: ' . BASE_URL . 'index.php');
        if (!defined('TESTING')) exit;

    } else {
        // Verification failed
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Verification Failed',
            'message' => 'The 6-digit authenticator code you entered is invalid or expired. Please try again.'
        ];
        header('Location: ' . BASE_URL . 'auth/login_2fa.php');
        if (!defined('TESTING')) exit;
    }

} catch (Exception $e) {
    error_log("2FA verification process error: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Authentication Error',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'auth/login.php');
    if (!defined('TESTING')) exit;
}
