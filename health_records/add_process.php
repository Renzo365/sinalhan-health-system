<?php
// health_records/add_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff (BHW has view-only access)
require_role(['admin', 'staff']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';
require_once __DIR__ . '/../includes/encryption.php';

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
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $visitDate = $_POST['visit_date'] ?? '';
    
    // Consultation Details
    $chiefComplaint = trim($_POST['chief_complaint'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '') ?: null;
    $treatment = trim($_POST['treatment'] ?? '') ?: null;
    $prescription = trim($_POST['prescription'] ?? '') ?: null;
    $notes = trim($_POST['notes'] ?? '') ?: null;
    
    // Vital Signs
    $bloodPressure = trim($_POST['blood_pressure'] ?? '') ?: null;
    $temperature = $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : null;
    $weightKg = $_POST['weight_kg'] !== '' ? (float)$_POST['weight_kg'] : null;
    $heightCm = $_POST['height_cm'] !== '' ? (float)$_POST['height_cm'] : null;
    $heartRate = $_POST['heart_rate'] !== '' ? (int)$_POST['heart_rate'] : null;
    $respiratoryRate = $_POST['respiratory_rate'] !== '' ? (int)$_POST['respiratory_rate'] : null;

    $pdo = Database::getInstance()->getConnection();

    // 3. Server-side Validation
    if ($patientId <= 0) {
        throw new Exception('Please select a valid patient.');
    }
    if ($serviceId <= 0) {
        throw new Exception('Please select a valid service category.');
    }
    if (empty($visitDate)) {
        throw new Exception('Please provide a valid visit date.');
    }
    if (empty($chiefComplaint)) {
        throw new Exception('Please fill in the Chief Complaint.');
    }

    if (strtotime($visitDate) > time()) {
        throw new Exception('Consultation visit date cannot be in the future.');
    }

    // Validate patient existence and active status
    $patientStmt = $pdo->prepare("SELECT patient_id, first_name, last_name, suffix FROM patients WHERE patient_id = ? AND is_archived = 0");
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch();
    if (!$patient) {
        throw new Exception('The selected patient profile is either archived or does not exist.');
    }

    // Validate service category existence and active status
    $serviceStmt = $pdo->prepare("SELECT service_id, service_name FROM service_types WHERE service_id = ? AND is_active = 1");
    $serviceStmt->execute([$serviceId]);
    $serviceExists = $serviceStmt->fetch();
    if (!$serviceExists) {
        throw new Exception('The selected service category is either deactivated or does not exist.');
    }

    // Validate Vitals formats and ranges
    if ($bloodPressure && !preg_match('/^\d{2,3}\/\d{2,3}$/', $bloodPressure)) {
        throw new Exception('Invalid blood pressure format. Must match "Systolic/Diastolic" (e.g. 120/80).');
    }
    if ($temperature !== null && ($temperature < 30.0 || $temperature > 45.0)) {
        throw new Exception('Temperature reading must be between 30.0°C and 45.0°C.');
    }
    if ($weightKg !== null && ($weightKg < 1.0 || $weightKg > 300.0)) {
        throw new Exception('Weight reading must be between 1.0kg and 300.0kg.');
    }
    if ($heightCm !== null && ($heightCm < 20.0 || $heightCm > 250.0)) {
        throw new Exception('Height reading must be between 20.0cm and 250.0cm.');
    }
    if ($heartRate !== null && ($heartRate < 20 || $heartRate > 250)) {
        throw new Exception('Heart rate must be between 20bpm and 250bpm.');
    }
    if ($respiratoryRate !== null && ($respiratoryRate < 5 || $respiratoryRate > 100)) {
        throw new Exception('Respiratory rate must be between 5cpm and 100cpm.');
    }

    // Encrypt clinical details for database storage (HIPAA Compliance)
    $chiefComplaint = encrypt_data($chiefComplaint);
    if ($diagnosis) {
        $diagnosis = encrypt_data($diagnosis);
    }
    if ($treatment) {
        $treatment = encrypt_data($treatment);
    }
    if ($prescription) {
        $prescription = encrypt_data($prescription);
    }
    if ($notes) {
        $notes = encrypt_data($notes);
    }

    // 4. Begin Database Transaction
    $pdo->beginTransaction();

    // Insert Health Record
    $hrStmt = $pdo->prepare("
        INSERT INTO health_records (
            patient_id, service_id, visit_date, chief_complaint, diagnosis, 
            treatment, prescription, notes, attending_staff, is_archived
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $hrStmt->execute([
        $patientId,
        $serviceId,
        $visitDate,
        $chiefComplaint,
        $diagnosis,
        $treatment,
        $prescription,
        $notes,
        $_SESSION['user_id']
    ]);

    $newRecordId = $pdo->lastInsertId();

    // Insert Vital Signs
    $vitalsStmt = $pdo->prepare("
        INSERT INTO vital_signs (
            record_id, blood_pressure, temperature, weight_kg, height_cm, 
            heart_rate, respiratory_rate
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $vitalsStmt->execute([
        $newRecordId,
        $bloodPressure,
        $temperature,
        $weightKg,
        $heightCm,
        $heartRate,
        $respiratoryRate
    ]);

    // Log Activity
    $patientFullName = $patient['first_name'] . ($patient['suffix'] ? ' ' . $patient['suffix'] : '') . ' ' . $patient['last_name'];
    log_activity(
        $pdo, 
        "Added health record for patient '{$patientFullName}'", 
        'Health Records', 
        $newRecordId, 
        "Service: {$serviceExists['service_name']} | Visit Date: {$visitDate}"
    );

    // Commit Transaction
    $pdo->commit();

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Consultation Saved',
        'message' => "The consultation log for '{$patientFullName}' has been recorded successfully."
    ];

    // Redirect - Prefer patient's view page to see the new record under the consultations tab
    header('Location: ' . BASE_URL . 'patients/view.php?id=' . $patientId);
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Health records creation failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Failed to Save Record',
        'message' => $e->getMessage()
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'health_records/list.php')));
    if (!defined('TESTING')) exit;
}
