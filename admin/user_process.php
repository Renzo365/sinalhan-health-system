<?php
// admin/user_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Invalid Request',
        'message' => 'Direct access is not allowed.'
    ];
    header('Location: ' . BASE_URL . 'admin/users.php');
    if (!defined('TESTING')) exit;
}

// Extract action parameter
$action = $_POST['action'] ?? '';

// 1. Verify CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Security Error',
        'message' => 'CSRF verification failed. Request denied.'
    ];
    header('Location: ' . BASE_URL . 'admin/users.php');
    if (!defined('TESTING')) exit;
}

$pdo = Database::getInstance()->getConnection();

try {
    switch ($action) {
        case 'add':
            // Add user processing
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '') ?: null;
            $contact = trim($_POST['contact_number'] ?? '') ?: null;
            $role = $_POST['role'] ?? 'staff';

            // Server-side validation
            if (empty($username) || empty($password) || empty($firstName) || empty($lastName) || empty($role)) {
                throw new Exception('Please fill in all required fields.');
            }

            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
                throw new Exception('Username must be 3-30 characters and alphanumeric or underscore.');
            }

            if (strlen($password) < 8 || 
                !preg_match('/[A-Z]/', $password) || 
                !preg_match('/[a-z]/', $password) || 
                !preg_match('/[0-9]/', $password) || 
                !preg_match('/[^A-Za-z0-9]/', $password)) {
                throw new Exception('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
            }

            if (!in_array($role, ['staff', 'bhw'])) {
                throw new Exception('Invalid user role selected. Creating additional administrator accounts is not permitted.');
            }

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format.');
            }

            // Check if username already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $checkStmt->execute([$username]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Username '{$username}' is already taken.");
            }

            // Insert new user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, first_name, last_name, email, contact_number, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $insertStmt->execute([$username, $passwordHash, $firstName, $lastName, $email, $contact, $role]);
            $newUserId = $pdo->lastInsertId();

            log_activity($pdo, "Created user account '{$username}'", 'Admin', $newUserId, "Role: {$role}");

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'User Created',
                'message' => "Account '{$username}' has been successfully created."
            ];
            header('Location: ' . BASE_URL . 'admin/users.php');
            if (!defined('TESTING')) exit;
            break;

        case 'edit':
            // Edit user processing
            $userId = (int)($_POST['user_id'] ?? 0);
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '') ?: null;
            $contact = trim($_POST['contact_number'] ?? '') ?: null;
            $role = $_POST['role'] ?? '';

            if (!$userId) {
                throw new Exception('Invalid user ID.');
            }

            if (empty($firstName) || empty($lastName) || empty($role)) {
                throw new Exception('Please fill in all required fields.');
            }

            if (!in_array($role, ['admin', 'staff', 'bhw'])) {
                throw new Exception('Invalid user role selected.');
            }

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format.');
            }

            // Fetch current user details to check constraints
            $userStmt = $pdo->prepare("SELECT role, is_active, username FROM users WHERE user_id = ? AND is_archived = 0");
            $userStmt->execute([$userId]);
            $currentUser = $userStmt->fetch();

            if (!$currentUser) {
                throw new Exception('User not found.');
            }

            // Security constraint: Cannot promote a user to admin if they are not already an admin
            if ($role === 'admin' && $currentUser['role'] !== 'admin') {
                throw new Exception('Promoting accounts to Administrator is not permitted.');
            }

            // Security constraint: Admin cannot change their own role to something else
            if ($userId === (int)$_SESSION['user_id'] && $role !== 'admin') {
                throw new Exception('You cannot change your own Administrator role.');
            }

            // Update user details
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, contact_number = ?, role = ?
                WHERE user_id = ?
            ");
            $updateStmt->execute([$firstName, $lastName, $email, $contact, $role, $userId]);

            log_activity($pdo, "Updated user account '{$currentUser['username']}'", 'Admin', $userId, "New Role: {$role}");

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Profile Updated',
                'message' => "User account details updated successfully."
            ];
            header('Location: ' . BASE_URL . 'admin/users.php');
            if (!defined('TESTING')) exit;
            break;

        case 'toggle_status':
            // Toggle active status (deactivate/activate user)
            $userId = (int)($_POST['user_id'] ?? 0);
            $status = (int)($_POST['status'] ?? 0); // 1 = activate, 0 = deactivate

            if (!$userId) {
                throw new Exception('Invalid user ID.');
            }

            // Fetch username and safety checks
            $userStmt = $pdo->prepare("SELECT username, is_active FROM users WHERE user_id = ? AND is_archived = 0");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();

            if (!$user) {
                throw new Exception('User not found.');
            }

            // Security constraint: Admin cannot deactivate themselves!
            if ($userId === (int)$_SESSION['user_id'] && $status === 0) {
                throw new Exception('You cannot deactivate your own Administrator account.');
            }

            $updateStmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $updateStmt->execute([$status, $userId]);

            $statusText = $status === 1 ? 'Activated' : 'Deactivated';
            log_activity($pdo, "{$statusText} user account '{$user['username']}'", 'Admin', $userId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => "Account {$statusText}",
                'message' => "The account '{$user['username']}' is now {$statusText}."
            ];
            header('Location: ' . BASE_URL . 'admin/users.php');
            if (!defined('TESTING')) exit;
            break;

        case 'reset_password':
            // Reset user password
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!$userId) {
                throw new Exception('Invalid user ID.');
            }

            if (empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('Password fields cannot be empty.');
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

            // Fetch username
            $userStmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ? AND is_archived = 0");
            $userStmt->execute([$userId]);
            $username = $userStmt->fetchColumn();

            if (!$username) {
                throw new Exception('User not found.');
            }

            // Update user password hash
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $updateStmt->execute([$newPasswordHash, $userId]);

            log_activity($pdo, "Reset password for user '{$username}'", 'Admin', $userId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Password Reset',
                'message' => "Password for user '{$username}' was reset successfully."
            ];
            header('Location: ' . BASE_URL . 'admin/users.php');
            if (!defined('TESTING')) exit;
            break;

        default:
            throw new Exception('Invalid action parameter.');
    }
} catch (Exception $e) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Operation Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/users.php'));
    if (!defined('TESTING')) exit;
}
