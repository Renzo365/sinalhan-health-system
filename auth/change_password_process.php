<?php
// auth/change_password_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/change_password.php');
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
        header('Location: ' . BASE_URL . 'auth/change_password.php');
        if (!defined('TESTING')) exit;
    }

    // 2. Extract and Sanitize Inputs
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $userId = (int)($_SESSION['user_id'] ?? 0);

    // 3. Server-side Validation
    if ($userId <= 0) {
        throw new Exception('Invalid user session.');
    }
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('Please fill in all required fields.');
    }
    if (strlen($newPassword) < 8 || 
        !preg_match('/[A-Z]/', $newPassword) || 
        !preg_match('/[a-z]/', $newPassword) || 
        !preg_match('/[0-9]/', $newPassword) || 
        !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        throw new Exception('New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
    }
    if ($newPassword !== $confirmPassword) {
        throw new Exception('New password and confirm password do not match.');
    }

    $pdo = Database::getInstance()->getConnection();

    // Fetch user details for password checking
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('User account not found.');
    }

    // Verify current password hash
    if (!password_verify($currentPassword, $user['password_hash'])) {
        throw new Exception('The current password you entered is incorrect.');
    }

    // 4. Update Password in Database
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $updateStmt->execute([$newHash, $userId]);

    // Log Activity
    log_activity($pdo, "Changed account password", 'Auth', $userId);

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Password Updated',
        'message' => 'Your account password has been updated successfully.'
    ];

    header('Location: ' . BASE_URL . 'auth/profile.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Password settings update failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'auth/change_password.php');
    if (!defined('TESTING')) exit;
}
