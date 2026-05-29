<?php
// appointments/archive_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin only
require_role(['admin']);

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

    if ($appId <= 0) {
        throw new Exception('Please specify a valid appointment ID to archive.');
    }

    $pdo = Database::getInstance()->getConnection();

    // Check target appointment record existence and active status
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, p.first_name, p.last_name, p.suffix 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.appointment_id = ? AND a.is_archived = 0
    ");
    $stmt->execute([$appId]);
    $app = $stmt->fetch();
    if (!$app) {
        throw new Exception('The appointment record does not exist or is already archived.');
    }

    // 3. Update Appointment Archive Status
    $updateStmt = $pdo->prepare("UPDATE appointments SET is_archived = 1 WHERE appointment_id = ?");
    $updateStmt->execute([$appId]);

    $patientFullName = $app['first_name'] . ($app['suffix'] ? ' ' . $app['suffix'] : '') . ' ' . $app['last_name'];

    // 4. Log Activity
    log_activity(
        $pdo,
        "Archived appointment #{$appId} for patient '{$patientFullName}'",
        'Appointment',
        $appId,
        "Scheduled date was: {$app['appointment_date']}"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Appointment Archived',
        'message' => "The appointment log for '{$patientFullName}' has been soft-deleted."
    ];

    header('Location: ' . BASE_URL . 'appointments/list.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Appointment archiving process failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Archive Appointment',
        'message' => $e->getMessage()
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'appointments/list.php')));
    if (!defined('TESTING')) exit;
}
