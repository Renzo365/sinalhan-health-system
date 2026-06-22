<?php
// appointments/add_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';
require_once __DIR__ . '/../includes/notification_helper.php';

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

    // Check for double-booking / schedule conflict (Same patient, same date, same time)
    $conflictSql = "
        SELECT COUNT(*) FROM appointments 
        WHERE patient_id = ? 
          AND appointment_date = ? 
          AND is_archived = 0 
          AND status != 'Cancelled'
    ";
    $conflictParams = [$patientId, $appointmentDate];
    if ($appointmentTime) {
        $formattedTime = date('H:i:00', strtotime($appointmentTime));
        $conflictSql .= " AND appointment_time = ? ";
        $conflictParams[] = $formattedTime;
    } else {
        $conflictSql .= " AND appointment_time IS NULL ";
    }
    
    $conflictStmt = $pdo->prepare($conflictSql);
    $conflictStmt->execute($conflictParams);
    if ((int)$conflictStmt->fetchColumn() > 0) {
        throw new Exception('The patient already has a scheduled appointment at this date and time slot.');
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

    // Idempotency Guard: Prevent duplicate submissions within a 30-second window
    $recentStmt = $pdo->prepare("
        SELECT 1 FROM appointments 
        WHERE patient_id = ? 
          AND service_id = ? 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
    ");
    $recentStmt->execute([$patientId, $serviceId]);
    if ($recentStmt->fetch()) {
        throw new Exception('A similar appointment was just scheduled. Please wait before submitting again.');
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

    // Trigger Notification
    add_notification($pdo, null, 'New Appointment', "New appointment booked for '{$patientFullName}' on {$appointmentDate} ({$serviceExists['service_name']})", 'info');

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Appointment Booked',
        'message' => "Successfully scheduled check-up for '{$patientFullName}'."
    ];

    // Redirect to the appointments directory list page
    header('Location: ' . BASE_URL . 'appointments/list.php');
    if (!defined('TESTING')) exit;

} catch (PDOException $e) {
    error_log("Appointment scheduling database failure: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Book Appointment',
        'message' => 'A system database error occurred. Please contact the administrator.'
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'appointments/list.php')));
    if (!defined('TESTING')) exit;
} catch (Exception $e) {
    error_log("Appointment scheduling failure: " . $e->getMessage());
    $_SESSION['old_inputs'] = $_POST;
    $_SESSION['old_inputs_flash'] = true;
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Book Appointment',
        'message' => $e->getMessage()
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'appointments/list.php')));
    if (!defined('TESTING')) exit;
}
