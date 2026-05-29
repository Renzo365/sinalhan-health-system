<?php
// auth/logout.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

try {
    if (isset($_SESSION['user_id'])) {
        $pdo = Database::getInstance()->getConnection();
        log_activity($pdo, 'Logged out', 'Auth', $_SESSION['user_id']);
    }
} catch (Exception $e) {
    error_log("Logout activity logging failed: " . $e->getMessage());
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a brief session to show logout feedback
session_start();
$_SESSION['alert'] = [
    'type' => 'success',
    'title' => 'Logged Out',
    'message' => 'You have successfully logged out.'
];

// Redirect to login page
require_once __DIR__ . '/../config/app.php';
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
