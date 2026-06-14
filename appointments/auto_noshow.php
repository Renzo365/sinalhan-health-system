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
    
    // Begin transaction to ensure database atomicity/consistency
    $pdo->beginTransaction();
    
    $todayStr = date('Y-m-d');
    
    // Update query: Target all active (not archived) scheduled bookings whose dates have passed
    $updateStmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'No-Show' 
        WHERE appointment_date < ? 
          AND status = 'Scheduled' 
          AND is_archived = 0
    ");
    $updateStmt->execute([$todayStr]);
    $updatedCount = $updateStmt->rowCount();
    
    if ($updatedCount > 0) {
        // Log this action to the activity audit logs for system tracking
        $logStmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, module, details) 
            VALUES (?, 'Update', 'Appointments', ?)
        ");
        $logStmt->execute([
            $_SESSION['user_id'], 
            "Batch updated $updatedCount overdue appointments to 'No-Show'"
        ]);
        
        $pdo->commit();
        $_SESSION['success_msg'] = "Successfully resolved $updatedCount past-due appointments as 'No-Show'.";
    } else {
        // Rollback or commit is fine since nothing changed, we commit
        $pdo->commit();
        $_SESSION['info_msg'] = "No past-due scheduled appointments were found to resolve.";
    }
} catch (Exception $e) {
    // If anything fails, rollback the transaction to prevent half-finished updates
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Batch auto no-show update failed: " . $e->getMessage());
    $_SESSION['error_msg'] = "A database error occurred during batch update: " . $e->getMessage();
}

// 4. Redirect the user back to the referral page (Dashboard or Appointments list)
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/dashboard.php');
header('Location: ' . $redirectUrl);
exit;
