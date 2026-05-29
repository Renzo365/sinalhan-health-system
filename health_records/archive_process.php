<?php
// health_records/archive_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin only
require_role(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'health_records/list.php');
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
        header('Location: ' . BASE_URL . 'health_records/list.php');
        if (!defined('TESTING')) exit;
    }

    // 2. Extract and Sanitize Inputs
    $recordId = (int)($_POST['record_id'] ?? 0);

    if ($recordId <= 0) {
        throw new Exception('Please specify a valid record ID to archive.');
    }

    $pdo = Database::getInstance()->getConnection();

    // Fetch existing health record
    $hrStmt = $pdo->prepare("
        SELECT hr.record_id, hr.patient_id, p.first_name, p.last_name, p.suffix, hr.visit_date
        FROM health_records hr 
        INNER JOIN patients p ON hr.patient_id = p.patient_id 
        WHERE hr.record_id = ? AND hr.is_archived = 0
    ");
    $hrStmt->execute([$recordId]);
    $record = $hrStmt->fetch();
    
    if (!$record) {
        throw new Exception('The consultation record does not exist or is already archived.');
    }

    // 3. Update Record Archive Status
    $updateStmt = $pdo->prepare("UPDATE health_records SET is_archived = 1 WHERE record_id = ?");
    $updateStmt->execute([$recordId]);

    // Log Activity
    $patientFullName = $record['first_name'] . ($record['suffix'] ? ' ' . $record['suffix'] : '') . ' ' . $record['last_name'];
    log_activity(
        $pdo, 
        "Archived health record #{$recordId} for patient '{$patientFullName}'", 
        'Health Records', 
        $recordId, 
        "Visit Date was: {$record['visit_date']}"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Record Archived',
        'message' => "The consultation log for '{$patientFullName}' has been soft-deleted."
    ];

    header('Location: ' . BASE_URL . 'health_records/list.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Health records archiving failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Archive Record',
        'message' => $e->getMessage()
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'health_records/list.php')));
    if (!defined('TESTING')) exit;
}
