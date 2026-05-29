<?php
// queue/archive_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin only
require_role(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

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

    if ($queueId <= 0) {
        throw new Exception('Please specify a valid queue ticket ID to archive.');
    }

    $pdo = Database::getInstance()->getConnection();

    // Check target queue ticket existence and active status
    $stmt = $pdo->prepare("
        SELECT q.queue_id, q.queue_number, p.first_name, p.last_name, p.suffix, q.queue_date 
        FROM queue q
        JOIN patients p ON q.patient_id = p.patient_id
        WHERE q.queue_id = ? AND q.is_archived = 0
    ");
    $stmt->execute([$queueId]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        throw new Exception('The queue ticket does not exist or is already archived.');
    }

    // 3. Update Queue Ticket Archive Status
    $updateStmt = $pdo->prepare("UPDATE queue SET is_archived = 1 WHERE queue_id = ?");
    $updateStmt->execute([$queueId]);

    $patientFullName = $ticket['first_name'] . ($ticket['suffix'] ? ' ' . $ticket['suffix'] : '') . ' ' . $ticket['last_name'];
    $ticketStr = str_pad($ticket['queue_number'], 3, '0', STR_PAD_LEFT);

    // 4. Log Activity
    log_activity(
        $pdo,
        "Archived queue ticket #{$ticketStr} for patient '{$patientFullName}'",
        'Queue',
        $queueId,
        "Date of queue was: {$ticket['queue_date']}"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Ticket Archived',
        'message' => "Queue ticket #{$ticketStr} has been soft-deleted."
    ];

    header('Location: ' . BASE_URL . 'queue/manage.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Queue ticket archiving failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Archive Ticket',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'queue/manage.php');
    if (!defined('TESTING')) exit;
}
