<?php
/**
 * appointments/auto_noshow.php
 * 
 * Batch process script to mark all overdue scheduled appointments as "No-Show".
 * Accessible only by administrators with valid CSRF tokens.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only permission for bulk transition actions
require_role(['admin']);

// 1. Verify Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_msg'] = "Invalid request method.";
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

// 2. Validate Security CSRF Token to protect against cross-site scripting/request forgery
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    $_SESSION['error_msg'] = "Security validation failed. CSRF token mismatch.";
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

// 3. Connect to Database and Execute Batch Transition
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Execute the stored procedure to resolve overdue appointments and log changes
    $stmt = $pdo->prepare("CALL sp_resolve_overdue_appointments(?, @resolved_count)");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Fetch output variable of updated appointments count
    $resolvedCount = (int)$pdo->query("SELECT @resolved_count")->fetchColumn();
    
    if ($resolvedCount > 0) {
        $_SESSION['success_msg'] = "Successfully resolved $resolvedCount past-due appointments as 'No-Show'.";
    } else {
        $_SESSION['info_msg'] = "No past-due scheduled appointments were found to resolve.";
    }
} catch (Exception $e) {
    error_log("Batch auto no-show update failed: " . $e->getMessage());
    $_SESSION['error_msg'] = "A database error occurred during batch update: " . $e->getMessage();
}

// 4. Redirect the user back to the referral page (Dashboard or Appointments list)
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/dashboard.php');
header('Location: ' . $redirectUrl);
exit;
