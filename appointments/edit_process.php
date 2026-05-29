<?php
// appointments/edit_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff (BHW restricted from editing)
require_role(['admin', 'staff']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'appointments/list.php');
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
        header('Location: ' . BASE_URL . 'appointments/list.php');
        if (!defined('TESTING')) exit;
    }

    // 2. Extract and Sanitize Inputs
    $appId = (int)($_POST['appointment_id'] ?? 0);
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentTime = trim($_POST['appointment_time'] ?? '') ?: null;
    $status = $_POST['status'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '') ?: null;

    $pdo = Database::getInstance()->getConnection();

    // 3. Server-side Validation
    if ($appId <= 0) {
        throw new Exception('Please specify a valid appointment ID.');
    }
    if ($serviceId <= 0) {
        throw new Exception('Please select a valid service type.');
    }
    if (empty($appointmentDate)) {
        throw new Exception('Please select an appointment date.');
    }
    if (empty($reason)) {
        throw new Exception('Please enter a reason for the visit.');
    }
    if (!in_array($status, ['Scheduled', 'Completed', 'Cancelled', 'No-Show'])) {
        throw new Exception('Invalid appointment status selected.');
    }

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
        throw new Exception('The appointment record does not exist or has been archived.');
    }

    // Check service category existence
    $serviceStmt = $pdo->prepare("SELECT service_name FROM service_types WHERE service_id = ?");
    $serviceStmt->execute([$serviceId]);
    $serviceExists = $serviceStmt->fetch();
    if (!$serviceExists) {
        throw new Exception('The selected service category does not exist.');
    }

    // 4. Update Appointment in Database
    $updateStmt = $pdo->prepare("
        UPDATE appointments SET
            service_id = ?,
            appointment_date = ?,
            appointment_time = ?,
            status = ?,
            reason = ?,
            notes = ?
        WHERE appointment_id = ?
    ");
    $updateStmt->execute([
        $serviceId,
        $appointmentDate,
        $appointmentTime,
        $status,
        $reason,
        $notes,
        $appId
    ]);

    $patientFullName = $app['first_name'] . ($app['suffix'] ? ' ' . $app['suffix'] : '') . ' ' . $app['last_name'];
    $oldStatus = $app['old_status'];

    // 5. Log Activity
    $logMsg = "Updated appointment #{$appId} for patient '{$patientFullName}'";
    if ($oldStatus !== $status) {
        $logMsg .= " (Status: {$oldStatus} -> {$status})";
    }
    log_activity(
        $pdo,
        $logMsg,
        'Appointment',
        $appId,
        "Date: {$appointmentDate} | Service: {$serviceExists['service_name']}"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Appointment Updated',
        'message' => "Successfully updated check-up details for '{$patientFullName}'."
    ];

    header('Location: ' . BASE_URL . 'appointments/list.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Appointment editing process failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Update Appointment',
        'message' => $e->getMessage()
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'appointments/list.php')));
    if (!defined('TESTING')) exit;
}
