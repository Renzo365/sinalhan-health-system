<?php
// auth/profile_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/profile.php');
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
        header('Location: ' . BASE_URL . 'auth/profile.php');
        if (!defined('TESTING')) exit;
    }

    // 2. Extract and Sanitize Inputs
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '') ?: null;
    $contactNumber = trim($_POST['contact_number'] ?? '') ?: null;

    $userId = (int)($_SESSION['user_id'] ?? 0);

    // 3. Server-side Validation
    if ($userId <= 0) {
        throw new Exception('Invalid user session.');
    }
    if (empty($firstName) || empty($lastName)) {
        throw new Exception('First name and Last name are required fields.');
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }
    if ($contactNumber && !preg_match('/^(09\d{9}|(\+639)\d{9})$/', $contactNumber)) {
        throw new Exception('Invalid contact number format. Use 09XXXXXXXXX.');
    }

    $pdo = Database::getInstance()->getConnection();

    // 4. Update Database
    $updateStmt = $pdo->prepare("
        UPDATE users SET
            first_name = ?,
            last_name = ?,
            email = ?,
            contact_number = ?
        WHERE user_id = ? AND is_active = 1
    ");
    $updateStmt->execute([
        $firstName,
        $lastName,
        $email,
        $contactNumber,
        $userId
    ]);

    // 5. Update Session values
    $_SESSION['full_name'] = $firstName . ' ' . $lastName;

    // Log Activity
    log_activity($pdo, "Updated profile details", 'Auth', $userId, "Email: {$email} | Phone: {$contactNumber}");

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Profile Updated',
        'message' => 'Your personal profile details have been saved successfully.'
    ];

    header('Location: ' . BASE_URL . 'auth/profile.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Profile update process failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'auth/profile.php');
    if (!defined('TESTING')) exit;
}
