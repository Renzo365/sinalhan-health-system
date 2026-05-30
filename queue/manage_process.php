<?php
// queue/manage_process.php
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
    header('Location: ' . BASE_URL . 'queue/manage.php');
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
        header('Location: ' . BASE_URL . 'queue/manage.php');
        if (!defined('TESTING')) exit;
    }

    // 2. Extract and Sanitize Inputs
    $queueId = (int)($_POST['queue_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $pdo = Database::getInstance()->getConnection();

    // 3. Server-side Validation
    if ($queueId <= 0) {
        throw new Exception('Please specify a valid queue ticket ID.');
    }
    if (!in_array($action, ['serve', 'complete', 'noshow'])) {
        throw new Exception('Invalid queue action requested.');
    }

    // Check target queue ticket existence and active status
    $stmt = $pdo->prepare("
        SELECT q.queue_id, q.queue_number, q.status, p.first_name, p.last_name, p.suffix 
        FROM queue q
        JOIN patients p ON q.patient_id = p.patient_id
        WHERE q.queue_id = ? AND q.is_archived = 0
    ");
    $stmt->execute([$queueId]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        throw new Exception('The queue ticket does not exist or has been archived.');
    }

    $patientFullName = $ticket['first_name'] . ($ticket['suffix'] ? ' ' . $ticket['suffix'] : '') . ' ' . $ticket['last_name'];
    $ticketStr = str_pad($ticket['queue_number'], 3, '0', STR_PAD_LEFT);

    // 4. Update Queue Ticket Status in Database
    if ($action === 'serve') {
        if ($ticket['status'] !== 'Waiting') {
            throw new Exception('Only waiting tickets can be called for serving.');
        }

        $updateStmt = $pdo->prepare("UPDATE queue SET status = 'Serving', serving_time = CURRENT_TIMESTAMP WHERE queue_id = ?");
        $updateStmt->execute([$queueId]);

        log_activity($pdo, "Started serving queue ticket #{$ticketStr} for patient '{$patientFullName}'", 'Queue', $queueId);
        
        add_notification($pdo, null, 'Serving Patient', "Ticket #{$ticketStr} ('{$patientFullName}') is now serving.", 'success');

        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Patient Called',
            'message' => "Ticket #{$ticketStr} ('{$patientFullName}') is now being served."
        ];
    } elseif ($action === 'complete') {
        if ($ticket['status'] !== 'Serving') {
            throw new Exception('Only tickets currently being served can be completed.');
        }

        $updateStmt = $pdo->prepare("UPDATE queue SET status = 'Served', completed_time = CURRENT_TIMESTAMP WHERE queue_id = ?");
        $updateStmt->execute([$queueId]);

        log_activity($pdo, "Completed queue service for ticket #{$ticketStr} for patient '{$patientFullName}'", 'Queue', $queueId);

        add_notification($pdo, null, 'Service Completed', "Ticket #{$ticketStr} ('{$patientFullName}') completed successfully.", 'info');

        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Service Completed',
            'message' => "Successfully completed service for Ticket #{$ticketStr}."
        ];
    } elseif ($action === 'noshow') {
        if ($ticket['status'] !== 'Serving') {
            throw new Exception('Only tickets currently being served can be marked as No-Show.');
        }

        $updateStmt = $pdo->prepare("UPDATE queue SET status = 'No-Show' WHERE queue_id = ?");
        $updateStmt->execute([$queueId]);

        log_activity($pdo, "Marked queue ticket #{$ticketStr} for patient '{$patientFullName}' as No-Show", 'Queue', $queueId);

        add_notification($pdo, null, 'Patient No-Show', "Ticket #{$ticketStr} ('{$patientFullName}') was marked as No-Show.", 'warning');

        $_SESSION['alert'] = [
            'type' => 'info',
            'title' => 'Patient No-Show',
            'message' => "Ticket #{$ticketStr} marked as No-Show."
        ];
    }

    header('Location: ' . BASE_URL . 'queue/manage.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Queue management process failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Process Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'queue/manage.php');
    if (!defined('TESTING')) exit;
}
