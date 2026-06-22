<?php
// ajax/check_duplicate.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$firstName = trim($_GET['first_name'] ?? '');
$lastName = trim($_GET['last_name'] ?? '');
$birthdate = $_GET['birthdate'] ?? '';

if (empty($firstName) || empty($lastName) || empty($birthdate)) {
    echo json_encode(['hasDuplicate' => false, 'matches' => []]);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Case-insensitive name match check on all records (including archived)
    $stmt = $pdo->prepare("
        SELECT patient_id, first_name, middle_name, last_name, suffix, birthdate, sex, purok, contact_number, is_archived 
        FROM patients 
        WHERE LOWER(first_name) = LOWER(?) 
          AND LOWER(last_name) = LOWER(?)
    ");
    $stmt->execute([$firstName, $lastName]);
    $duplicates = $stmt->fetchAll();

    echo json_encode([
        'hasDuplicate' => count($duplicates) > 0,
        'matches' => $duplicates
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Duplicate patient check error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to query database.']);
}
