<?php
// appointments/add_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

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
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentTime = trim($_POST['appointment_time'] ?? '') ?: null;
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '') ?: null;

    $pdo = Database::getInstance()->getConnection();

    // 3. Server-side Validation
    if ($patientId <= 0) {
        throw new Exception('Please select a valid patient.');
    }
    if ($serviceId <= 0) {
        throw new Exception('Please select a valid service type.');
    }
    if (empty($appointmentDate)) {
        throw new Exception('Please select an appointment date.');
    }
    if (empty($reason)) {
        throw new Exception('Please enter a reason for the check-up.');
    }

    // Date check: cannot schedule in the past
    $appTimestamp = strtotime($appointmentDate . ' 00:00:00');
    $todayTimestamp = strtotime(date('Y-m-d') . ' 00:00:00');
    if ($appTimestamp < $todayTimestamp) {
        throw new Exception('Appointment date cannot be in the past.');
    }

    // Check patient existence and active status
    $patientStmt = $pdo->prepare("SELECT patient_id, first_name, last_name, suffix FROM patients WHERE patient_id = ? AND is_archived = 0");
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch();
    if (!$patient) {
        throw new Exception('The selected patient profile is either archived or does not exist.');
    }

    // Check service existence and active status
    $serviceStmt = $pdo->prepare("SELECT service_id, service_name FROM service_types WHERE service_id = ? AND is_active = 1");
    $serviceStmt->execute([$serviceId]);
    $serviceExists = $serviceStmt->fetch();
    if (!$serviceExists) {
        throw new Exception('The selected service category is either deactivated or does not exist.');
    }

    // 4. Save Appointment to Database
    $insertStmt = $pdo->prepare("
        INSERT INTO appointments (
            patient_id, service_id, appointment_date, appointment_time, 
            status, reason, notes, created_by, is_archived
        ) VALUES (?, ?, ?, ?, 'Scheduled', ?, ?, ?, 0)
    ");
    $insertStmt->execute([
        $patientId,
        $serviceId,
        $appointmentDate,
        $appointmentTime,
        $reason,
        $notes,
        $_SESSION['user_id']
    ]);

    $newAppId = $pdo->lastInsertId();
    $patientFullName = $patient['first_name'] . ($patient['suffix'] ? ' ' . $patient['suffix'] : '') . ' ' . $patient['last_name'];

    // 5. Log Activity
    log_activity(
        $pdo,
        "Scheduled appointment for patient '{$patientFullName}'",
        'Appointment',
        $newAppId,
        "Date: {$appointmentDate} | Service: {$serviceExists['service_name']}"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Appointment Booked',
        'message' => "Successfully scheduled check-up for '{$patientFullName}'."
    ];

    // Prefer redirect to the patient profile tab
    header('Location: ' . BASE_URL . 'patients/view.php?id=' . $patientId);
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Appointment scheduling failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Book Appointment',
        'message' => $e->getMessage()
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'appointments/list.php')));
    if (!defined('TESTING')) exit;
}
