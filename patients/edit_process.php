<?php
// patients/edit_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff (BHW is excluded from editing)
require_role(['admin', 'staff']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';
require_once __DIR__ . '/../includes/encryption.php';

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

    // 2. Extract and Sanitize Inputs
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '') ?: null;
    $lastName = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '') ?: null;
    $birthdate = $_POST['birthdate'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $civilStatus = $_POST['civil_status'] ?? 'Single';
    $contactNumber = trim($_POST['contact_number'] ?? '') ?: null;
    $purok = $_POST['purok'] ?? '';
    $address = trim($_POST['address'] ?? '') ?: null;
    $emergencyName = trim($_POST['emergency_contact_name'] ?? '') ?: null;
    $emergencyNumber = trim($_POST['emergency_contact_number'] ?? '') ?: null;
    $medicalHistory = trim($_POST['medical_history'] ?? '') ?: null;
    $allergies = trim($_POST['allergies'] ?? '') ?: null;

    if ($medicalHistory) {
        $medicalHistory = encrypt_data($medicalHistory);
    }
    if ($allergies) {
        $allergies = encrypt_data($allergies);
    }

    if (!$patientId) {
        throw new Exception('Invalid patient ID.');
    }

    // 3. Server-side Validation
    if (empty($firstName) || empty($lastName) || empty($birthdate) || empty($sex) || empty($purok)) {
        throw new Exception('Please fill in all required fields marked with an asterisk (*).');
    }

    if (strtotime($birthdate) > time()) {
        throw new Exception('Birthdate cannot be a future date.');
    }

    if (!in_array($sex, ['Male', 'Female'])) {
        throw new Exception('Invalid sex value selected.');
    }

    if (!in_array($civilStatus, ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])) {
        throw new Exception('Invalid civil status value.');
    }

    if ($contactNumber && !preg_match('/^(09\d{9}|(\+639)\d{9})$/', $contactNumber)) {
        throw new Exception('Invalid contact number format. Use 09XXXXXXXXX.');
    }

    if ($emergencyNumber && !preg_match('/^(09\d{9}|(\+639)\d{9})$/', $emergencyNumber)) {
        throw new Exception('Invalid emergency contact number format. Use 09XXXXXXXXX.');
    }

    $pdo = Database::getInstance()->getConnection();

    // Check if patient exists
    $checkStmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE patient_id = ? AND is_archived = 0");
    $checkStmt->execute([$patientId]);
    $currentPatient = $checkStmt->fetch();

    if (!$currentPatient) {
        throw new Exception('Patient profile not found.');
    }

    // 4. Update Database
    $updateStmt = $pdo->prepare("
        UPDATE patients 
        SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, birthdate = ?, sex = ?, civil_status = ?, 
            contact_number = ?, address = ?, purok = ?, emergency_contact_name = ?, emergency_contact_number = ?, 
            medical_history = ?, allergies = ?
        WHERE patient_id = ?
    ");
    
    $updateStmt->execute([
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $birthdate,
        $sex,
        $civilStatus,
        $contactNumber,
        $address,
        $purok,
        $emergencyName,
        $emergencyNumber,
        $medicalHistory,
        $allergies,
        $patientId
    ]);

    $fullName = $firstName . ($suffix ? ' ' . $suffix : '') . ' ' . $lastName;
    $oldName = $currentPatient['first_name'] . ' ' . $currentPatient['last_name'];

    // 5. Log activity
    log_activity($pdo, "Updated patient record '{$fullName}'", 'Patient Records', $patientId, "Original Name: {$oldName}");

    // Flash success alert
    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Profile Updated',
        'message' => "The record for '{$fullName}' has been updated successfully."
    ];
    header('Location: ' . BASE_URL . 'patients/view.php?id=' . $patientId);
    if (!defined('TESTING')) exit;

} catch (Exception $e) {
    error_log("Patient profile update failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'patients/list.php'));
    if (!defined('TESTING')) exit;
}
