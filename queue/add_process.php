<?php
// queue/add_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'queue/assign.php');
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
        header('Location: ' . BASE_URL . 'queue/assign.php');
        if (!defined('TESTING')) exit;
    }

    // 2. Extract and Sanitize Inputs
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $serviceId = (int)($_POST['service_id'] ?? 0);

    $pdo = Database::getInstance()->getConnection();

    // 3. Server-side Validation
    if ($patientId <= 0) {
        throw new Exception('Please select a valid patient.');
    }
    if ($serviceId <= 0) {
        throw new Exception('Please select a valid service category.');
    }

    // Validate active patient
    $patientStmt = $pdo->prepare("SELECT patient_id, first_name, last_name, suffix FROM patients WHERE patient_id = ? AND is_archived = 0");
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch();
    if (!$patient) {
        throw new Exception('The selected patient profile is either archived or does not exist.');
    }

    // Validate active service category
    $serviceStmt = $pdo->prepare("SELECT service_id, service_name FROM service_types WHERE service_id = ? AND is_active = 1");
    $serviceStmt->execute([$serviceId]);
    $serviceExists = $serviceStmt->fetch();
    if (!$serviceExists) {
        throw new Exception('The selected service category is either deactivated or does not exist.');
    }

    // 4. Generate Daily Queue Number & Save (Database Transaction)
    $pdo->beginTransaction();

    // Query daily sequence MAX value with write lock
    $seqStmt = $pdo->query("SELECT COALESCE(MAX(queue_number), 0) + 1 AS next_num FROM queue WHERE queue_date = CURDATE() FOR UPDATE");
    $seqRow = $seqStmt->fetch();
    $newQueueNumber = (int)$seqRow['next_num'];

    // Insert Queue Ticket
    $insertStmt = $pdo->prepare("
        INSERT INTO queue (
            patient_id, service_id, queue_date, queue_number, status, assigned_by, is_archived
        ) VALUES (?, ?, CURDATE(), ?, 'Waiting', ?, 0)
    ");
    $insertStmt->execute([
        $patientId,
        $serviceId,
        $newQueueNumber,
        $_SESSION['user_id']
    ]);

    $newQueueId = $pdo->lastInsertId();
    $patientFullName = $patient['first_name'] . ($patient['suffix'] ? ' ' . $patient['suffix'] : '') . ' ' . $patient['last_name'];
    $ticketStr = str_pad($newQueueNumber, 3, '0', STR_PAD_LEFT);

    // Log Activity
    log_activity(
        $pdo,
        "Assigned queue ticket #{$ticketStr} to patient '{$patientFullName}'",
        'Queue',
        $newQueueId,
        "Service: {$serviceExists['service_name']}"
    );

    // Commit Transaction
    $pdo->commit();

    // Set Printable Queue Ticket Session parameters
    $_SESSION['print_ticket'] = [
        'number' => $ticketStr,
        'patient_name' => $patientFullName,
        'service_name' => $serviceExists['service_name'],
        'date' => date('Y-m-d'),
        'time' => date('h:i A')
    ];

    // Alert details
    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Ticket Assigned',
        'message' => "Ticket #{$ticketStr} generated for '{$patientFullName}'."
    ];

    // Prefer redirect to the patient profile tab
    header('Location: ' . BASE_URL . 'patients/view.php?id=' . $patientId);
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Queue ticket assignment failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Assignment Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'queue/assign.php')));
    if (!defined('TESTING')) exit;
}
