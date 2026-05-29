<?php
// auth/two_fa_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';
require_once __DIR__ . '/../includes/totp.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

require_role(['admin', 'staff', 'bhw']);

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/two_fa.php');
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
        header('Location: ' . BASE_URL . 'auth/two_fa.php');
        if (!defined('TESTING')) exit;
    }

    $action = $_POST['action'] ?? '';
    $code = trim($_POST['code'] ?? '');
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($userId <= 0) {
        throw new Exception('Invalid user session.');
    }

    if (empty($code)) {
        throw new Exception('Please enter the 6-digit verification code.');
    }

    $pdo = Database::getInstance()->getConnection();

    // Fetch user details
    $stmt = $pdo->prepare("SELECT two_fa_secret, two_fa_enabled, username FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('User not found.');
    }

    if ($action === 'enable') {
        // Enforce temp secret presence
        if (empty($_SESSION['temp_2fa_secret'])) {
            throw new Exception('No pending 2FA secret found. Please restart configuration.');
        }

        $secret = $_SESSION['temp_2fa_secret'];

        // Verify the code
        if (TOTP::verifyCode($secret, $code)) {
            // Save to database
            $updateStmt = $pdo->prepare("UPDATE users SET two_fa_secret = ?, two_fa_enabled = 1 WHERE user_id = ?");
            $updateStmt->execute([$secret, $userId]);

            unset($_SESSION['temp_2fa_secret']);
            log_activity($pdo, "Enabled Two-Factor Authentication", 'Auth', $userId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => '2FA Enabled',
                'message' => 'Two-factor authentication has been successfully set up and enabled.'
            ];
            header('Location: ' . BASE_URL . 'auth/profile.php');
            if (!defined('TESTING')) exit;

        } else {
            throw new Exception('Invalid verification code. Please scan the QR code and enter the correct code.');
        }

    } elseif ($action === 'disable') {
        if (empty($user['two_fa_secret'])) {
            throw new Exception('Two-factor authentication is not active on this account.');
        }

        $secret = $user['two_fa_secret'];

        // Verify the code
        if (TOTP::verifyCode($secret, $code)) {
            // Disable 2FA
            $updateStmt = $pdo->prepare("UPDATE users SET two_fa_secret = NULL, two_fa_enabled = 0 WHERE user_id = ?");
            $updateStmt->execute([$userId]);

            log_activity($pdo, "Disabled Two-Factor Authentication", 'Auth', $userId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => '2FA Disabled',
                'message' => 'Two-factor authentication has been disabled for your account.'
            ];
            header('Location: ' . BASE_URL . 'auth/profile.php');
            if (!defined('TESTING')) exit;

        } else {
            throw new Exception('Invalid verification code. Could not disable 2FA.');
        }

    } else {
        throw new Exception('Invalid action parameter.');
    }

} catch (Exception $e) {
    error_log("2FA process exception: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => '2FA Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'auth/two_fa.php');
    if (!defined('TESTING')) exit;
}
