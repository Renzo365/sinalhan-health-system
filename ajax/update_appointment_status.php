<?php
/**
 * ajax/update_appointment_status.php
 * 
 * AJAX endpoint to dynamically update the status of an appointment.
 * Verifies CSRF token, user authentication, role check, and performs 
 * the update, logs activity, and triggers notifications.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

header('Content-Type: application/json');

// 1. Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// 2. Allowed roles: admin, staff, bhw
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['admin', 'staff', 'bhw'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Appropriate privileges required.']);
    exit;
}

// 3. Verify CSRF Token (sent via request headers or post payload)
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF verification failed. Request denied.']);
    exit;
}

// 4. Extract and Validate Input Parameters
$appId = (int)($_POST['appointment_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if ($appId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

if (!in_array($status, ['Scheduled', 'Completed', 'Cancelled', 'No-Show'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status option selected.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';
require_once __DIR__ . '/../includes/notification_helper.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check target appointment record existence and active status
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.patient_id, a.status AS old_status, p.first_name, p.last_name, p.suffix 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.appointment_id = ? AND a.is_archived = 0
    ");
    $stmt->execute([$appId]);
    $app = $stmt->fetch();
    
    if (!$app) {
        http_response_code(404);
        echo json_encode(['error' => 'The appointment record does not exist or has been archived.']);
        exit;
    }
    
    $oldStatus = $app['old_status'];
    
    if ($oldStatus === $status) {
        echo json_encode(['success' => true, 'message' => 'Status is already set to ' . $status]);
        exit;
    }
    
    // Update status in database
    $updateStmt = $pdo->prepare("
        UPDATE appointments 
        SET status = ? 
        WHERE appointment_id = ?
    ");
    $updateStmt->execute([$status, $appId]);
    
    $patientFullName = $app['first_name'] . ($app['suffix'] ? ' ' . $app['suffix'] : '') . ' ' . $app['last_name'];
    
    // Log Activity to activity log
    log_activity(
        $pdo,
        "Updated appointment #{$appId} for patient '{$patientFullName}' (Status: {$oldStatus} -> {$status})",
        'Appointment',
        $appId,
        "Status changed dynamically via inline dropdown"
    );
    
    // Trigger System-wide Notification on status change
    $notifType = ($status === 'Cancelled' || $status === 'No-Show') ? 'danger' : (($status === 'Completed') ? 'success' : 'info');
    add_notification(
        $pdo, 
        null, 
        'Appointment Status Change', 
        "Appointment for '{$patientFullName}' status changed to '{$status}' (was '{$oldStatus}')", 
        $notifType
    );
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully updated status to '{$status}'."
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("AJAX Appointment status editing process failure: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
}
