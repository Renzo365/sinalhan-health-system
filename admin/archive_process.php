<?php
// admin/archive_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Invalid Request',
        'message' => 'Direct access is not allowed.'
    ];
    header('Location: ' . BASE_URL . 'admin/archived_records.php');
    if (!defined('TESTING')) exit;
}

// 1. Verify CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Security Error',
        'message' => 'CSRF verification failed. Request denied.'
    ];
    header('Location: ' . BASE_URL . 'admin/archived_records.php');
    if (!defined('TESTING')) exit;
}

$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? '';
$recordId = (int)($_POST['record_id'] ?? 0);

if ($action !== 'restore' || !$recordId || !in_array($type, ['patient', 'appointment', 'queue', 'health_record'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Invalid Request Parameters',
        'message' => 'The restore action cannot be processed.'
    ];
    header('Location: ' . BASE_URL . 'admin/archived_records.php');
    if (!defined('TESTING')) exit;
}

$pdo = Database::getInstance()->getConnection();

try {
    switch ($type) {
        case 'patient':
            // Fetch name for detailed audit logging
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE patient_id = ?");
            $stmt->execute([$recordId]);
            $p = $stmt->fetch();
            if (!$p) {
                throw new Exception('Patient record not found.');
            }
            $fullName = $p['first_name'] . ' ' . $p['last_name'];

            // Restore patient
            $restoreStmt = $pdo->prepare("UPDATE patients SET is_archived = 0 WHERE patient_id = ?");
            $restoreStmt->execute([$recordId]);

            log_activity($pdo, "Restored patient record '{$fullName}'", 'Patient Records', $recordId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Patient Restored',
                'message' => "The record for patient '{$fullName}' has been successfully restored."
            ];
            break;

        case 'appointment':
            // Fetch appointment details for detailed audit logging
            $stmt = $pdo->prepare("
                SELECT p.first_name, p.last_name, a.appointment_date 
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                WHERE a.appointment_id = ?
            ");
            $stmt->execute([$recordId]);
            $a = $stmt->fetch();
            if (!$a) {
                throw new Exception('Appointment record not found.');
            }
            $pName = $a['first_name'] . ' ' . $a['last_name'];
            $appDate = date('M d, Y', strtotime($a['appointment_date']));

            // Restore appointment
            $restoreStmt = $pdo->prepare("UPDATE appointments SET is_archived = 0 WHERE appointment_id = ?");
            $restoreStmt->execute([$recordId]);

            log_activity($pdo, "Restored appointment for '{$pName}' on {$appDate}", 'Appointment', $recordId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Appointment Restored',
                'message' => "The appointment for '{$pName}' on {$appDate} has been successfully restored."
            ];
            break;

        case 'queue':
            // Fetch queue details for detailed audit logging
            $stmt = $pdo->prepare("
                SELECT q.queue_number, q.queue_date, p.first_name, p.last_name 
                FROM queue q
                JOIN patients p ON q.patient_id = p.patient_id
                WHERE q.queue_id = ?
            ");
            $stmt->execute([$recordId]);
            $q = $stmt->fetch();
            if (!$q) {
                throw new Exception('Queue ticket record not found.');
            }
            $pName = $q['first_name'] . ' ' . $q['last_name'];
            $ticket = str_pad($q['queue_number'], 3, '0', STR_PAD_LEFT);
            $qDate = date('M d, Y', strtotime($q['queue_date']));

            // Restore queue ticket
            $restoreStmt = $pdo->prepare("UPDATE queue SET is_archived = 0 WHERE queue_id = ?");
            $restoreStmt->execute([$recordId]);

            log_activity($pdo, "Restored queue ticket #{$ticket} for '{$pName}' on {$qDate}", 'Queue', $recordId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Queue Ticket Restored',
                'message' => "Queue ticket #{$ticket} for '{$pName}' has been successfully restored."
            ];
            break;

        case 'health_record':
            // Fetch patient and visit date for logging
            $stmt = $pdo->prepare("
                SELECT p.first_name, p.last_name, hr.visit_date 
                FROM health_records hr
                JOIN patients p ON hr.patient_id = p.patient_id
                WHERE hr.record_id = ?
            ");
            $stmt->execute([$recordId]);
            $hr = $stmt->fetch();
            if (!$hr) {
                throw new Exception('Consultation record not found.');
            }
            $pName = $hr['first_name'] . ' ' . $hr['last_name'];
            $visitDate = date('M d, Y', strtotime($hr['visit_date']));

            // Restore consultation
            $restoreStmt = $pdo->prepare("UPDATE health_records SET is_archived = 0 WHERE record_id = ?");
            $restoreStmt->execute([$recordId]);

            log_activity($pdo, "Restored health record #{$recordId} for '{$pName}' (Visit Date: {$visitDate})", 'Health Records', $recordId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Consultation Record Restored',
                'message' => "The consultation record for '{$pName}' from {$visitDate} has been successfully restored."
            ];
            break;
    }
    
    header('Location: ' . BASE_URL . 'admin/archived_records.php');
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Restoration Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'admin/archived_records.php');
    if (!defined('TESTING')) exit;
}
