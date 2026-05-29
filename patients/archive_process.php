<?php
// patients/archive_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access for soft-deleting patient profiles
require_role(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'patients/list.php');
    exit;
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
        header('Location: ' . BASE_URL . 'patients/list.php');
        exit;
    }

    // 2. Extract and Validate Input
    $patientId = (int)($_POST['patient_id'] ?? 0);
    if (!$patientId) {
        throw new Exception('Invalid patient ID.');
    }

    $pdo = Database::getInstance()->getConnection();

    // Check if patient exists
    $checkStmt = $pdo->prepare("SELECT first_name, last_name, is_archived FROM patients WHERE patient_id = ?");
    $checkStmt->execute([$patientId]);
    $patient = $checkStmt->fetch();

    if (!$patient) {
        throw new Exception('Patient record not found.');
    }

    if ($patient['is_archived'] == 1) {
        throw new Exception('Patient record is already archived.');
    }

    // 3. Set soft-delete status (is_archived = 1)
    $archiveStmt = $pdo->prepare("UPDATE patients SET is_archived = 1 WHERE patient_id = ?");
    $archiveStmt->execute([$patientId]);

    $fullName = $patient['first_name'] . ' ' . $patient['last_name'];

    // 4. Log activity
    log_activity($pdo, "Archived patient record '{$fullName}'", 'Patient Records', $patientId);

    // Flash success alert
    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Record Archived',
        'message' => "The record for patient '{$fullName}' has been successfully archived."
    ];
    header('Location: ' . BASE_URL . 'patients/list.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Patient archiving failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Archive Operation Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'patients/list.php');
    if (!defined('TESTING')) exit;
}
