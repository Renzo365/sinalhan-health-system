<?php
// auth/force_change_password_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/force_change_password.php');
    exit;
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
        header('Location: ' . BASE_URL . 'auth/force_change_password.php');
        exit;
    }

    // 2. Extract and Sanitize Inputs
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $userId = (int)($_SESSION['user_id'] ?? 0);

    // 3. Validation
    if ($userId <= 0 || !isset($_SESSION['must_change_password']) || $_SESSION['must_change_password'] != 1) {
        throw new Exception('Invalid user session or security state.');
    }

    if (empty($newPassword) || empty($confirmPassword)) {
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
        throw new Exception('Passwords do not match.');
    }

    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/log_activity.php';
    $pdo = Database::getInstance()->getConnection();

    // Verify user is active and exists
    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('User account not found or is deactivated.');
    }

    // Verify user is not choosing the same password
    if (password_verify($newPassword, $user['password_hash'])) {
        throw new Exception('New password cannot be the same as your current temporary password.');
    }

    // 4. Update password and clear the flag
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?");
    $updateStmt->execute([$newHash, $userId]);

    // Update session state
    $_SESSION['must_change_password'] = 0;

    // Log Activity
    log_activity($pdo, "Forced password reset completed", 'Auth', $userId);

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Password Updated',
        'message' => 'Your account password has been updated successfully.'
    ];

    header('Location: ' . BASE_URL . 'index.php');
    exit;

} catch (Exception $e) {
    error_log("Forced password change failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'auth/force_change_password.php');
    exit;
}
