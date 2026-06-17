<?php
// queue/add_process.php
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

    // Idempotency Guard: Prevent duplicate submissions within a 30-second window
    $recentStmt = $pdo->prepare("
        SELECT 1 FROM queue 
        WHERE patient_id = ? 
          AND service_id = ? 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
    ");
    $recentStmt->execute([$patientId, $serviceId]);
    if ($recentStmt->fetch()) {
        throw new Exception('A queue ticket for this service was just assigned to the patient. Please wait before submitting again.');
    }

    // 4. Generate Daily Queue Number & Save via Stored Procedure
    $stmt = $pdo->prepare("CALL sp_assign_queue_ticket(?, ?, ?, @ticket_str, @q_num)");
    $stmt->execute([$patientId, $serviceId, $_SESSION['user_id']]);

    // Fetch the OUT parameter results from MySQL user session variables
    $output = $pdo->query("SELECT @ticket_str AS ticket, @q_num AS num")->fetch();
    $ticketStr = $output['ticket'];
    $newQueueNumber = (int)$output['num'];

    // Retrieve patient details to format UI notifications and activity logging
    $patientStmt = $pdo->prepare("SELECT first_name, last_name, suffix FROM patients WHERE patient_id = ?");
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch();
    $patientFullName = $patient['first_name'] . ($patient['suffix'] ? ' ' . $patient['suffix'] : '') . ' ' . $patient['last_name'];

    // Retrieve service name
    $serviceStmt = $pdo->prepare("SELECT service_name FROM service_types WHERE service_id = ?");
    $serviceStmt->execute([$serviceId]);
    $serviceExists = $serviceStmt->fetch();
    if (!$serviceExists) {
        throw new Exception('The selected service category does not exist.');
    }

    // Log Activity (sp_log_activity is automatically called inside log_activity)
    log_activity(
        $pdo,
        "Assigned queue ticket #{$ticketStr} to patient '{$patientFullName}'",
        'Queue',
        null,
        "Service: {$serviceExists['service_name']}"
    );

    // Trigger Notification
    add_notification($pdo, null, 'Ticket Assigned', "Ticket #{$ticketStr} assigned to '{$patientFullName}' ({$serviceExists['service_name']})", 'info');

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
