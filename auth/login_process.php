<?php
// auth/login_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

try {
    // 1. Validate CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Security Error',
            'message' => 'CSRF validation failed. Invalid request source.'
        ];
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }

    // 2. Extract and Sanitize Inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check for empty fields
    if (empty($username) || empty($password)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Missing Fields',
            'message' => 'Please enter both username and password.'
        ];
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }

    // 3. Query User from Database
    $pdo = Database::getInstance()->getConnection();

    // Auto-create login_attempts table if it does not exist (Defensive database upgrade)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(100) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ip_username (ip_address, username),
            KEY idx_attempted_at (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // Prune attempts older than 24 hours to prevent table bloat
    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY");

    // Check rate limit: max 5 failed attempts in last 15 minutes
    $rateLimitStmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts 
        WHERE ip_address = ? AND username = ? AND attempted_at > NOW() - INTERVAL 15 MINUTE
    ");
    $rateLimitStmt->execute([$ipAddress, $username]);
    $failedAttempts = (int)$rateLimitStmt->fetchColumn();

    if ($failedAttempts >= 5) {
        throw new Exception('Too many failed login attempts. Your account/IP is temporarily locked. Please try again after 15 minutes.');
    }

    $stmt = $pdo->prepare("
        SELECT user_id, username, password_hash, first_name, last_name, role, is_active, is_archived, two_fa_enabled, two_fa_secret, theme, font_size, must_change_password 
        FROM users 
        WHERE username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // 4. Verify Credentials
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // 5. Check if user account is deactivated or archived
        if ($user['is_archived'] == 1) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Account Archived',
                'message' => 'Your account has been archived. Please contact the administrator.'
            ];
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit;
        }

        if ($user['is_active'] == 0) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Account Deactivated',
                'message' => 'Your account is deactivated. Please contact the administrator.'
            ];
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit;
        }

        // Intercept for Two-Factor Verification
        if ($user['two_fa_enabled'] == 1 && !empty($user['two_fa_secret'])) {
            $_SESSION['temp_2fa_user_id'] = $user['user_id'];
            $_SESSION['temp_2fa_username'] = $user['username'];
            $_SESSION['temp_2fa_full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['temp_2fa_role'] = $user['role'];
            $_SESSION['temp_2fa_theme'] = $user['theme'];
            $_SESSION['temp_2fa_font_size'] = $user['font_size'];
            $_SESSION['temp_2fa_must_change_password'] = $user['must_change_password'];

            $_SESSION['alert'] = [
                'type' => 'info',
                'title' => '2FA Code Required',
                'message' => 'Please enter the 6-digit code from your authenticator app.'
            ];
            header('Location: ' . BASE_URL . 'auth/login_2fa.php');
            exit;
        }

        // Clear failed login attempts on successful login
        $clearAttemptsStmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND username = ?");
        $clearAttemptsStmt->execute([$ipAddress, $username]);

        // 6. Successful Login: Set session and regenerate ID (prevents session fixation)
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['theme'] = $user['theme'];
        $_SESSION['font_size'] = $user['font_size'];
        $_SESSION['must_change_password'] = $user['must_change_password'];
        $_SESSION['login_time'] = time();

        // 7. Update last login details in users table
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET last_login = NOW(), last_login_ip = ? 
            WHERE user_id = ?
        ");
        $updateStmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $user['user_id']]);

        // 8. Log the login activity
        log_activity($pdo, 'Logged in', 'Auth', $user['user_id'], 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

        // Check if forced password reset is active
        if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Password Reset Required',
                'message' => 'An administrator has reset your password. Please set a new secure password to continue.'
            ];
            header('Location: ' . BASE_URL . 'auth/force_change_password.php');
            exit;
        } else {
            // Success Alert Flash
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Welcome Back!',
                'message' => 'Logged in successfully as ' . htmlspecialchars($user['first_name'] ?? 'User') . '.'
            ];

            // 9. Redirect to landing page (index.php will route them to correct role dashboard)
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }

    } else {
        // Log failed login attempt
        $logAttemptStmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
        $logAttemptStmt->execute([$ipAddress, $username]);

        // Invalid credentials
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Login Failed',
            'message' => 'Invalid username or password.'
        ];
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Login processing exception: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Authentication Error',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
