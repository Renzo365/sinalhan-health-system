<?php
// ajax/search_patients.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$searchTerm = trim($_GET['q'] ?? '');

try {
    $pdo = Database::getInstance()->getConnection();
    
    if ($searchTerm !== '') {
        // Query matching patient first name, middle name, last name, or combined string
        $stmt = $pdo->prepare("
            SELECT patient_id, first_name, middle_name, last_name, suffix, birthdate, sex, purok, contact_number
            FROM patients 
            WHERE is_archived = 0 
              AND (
                LOWER(first_name) LIKE LOWER(?) 
                OR LOWER(last_name) LIKE LOWER(?)
                OR LOWER(middle_name) LIKE LOWER(?)
                OR CONCAT(LOWER(first_name), ' ', LOWER(last_name)) LIKE LOWER(?)
                OR CONCAT(LOWER(last_name), ', ', LOWER(first_name)) LIKE LOWER(?)
              )
            ORDER BY last_name ASC, first_name ASC
            LIMIT 30
        ");
        $likeTerm = '%' . $searchTerm . '%';
        $stmt->execute([$likeTerm, $likeTerm, $likeTerm, $likeTerm, $likeTerm]);
    } else {
        // Return first 15 patients by default if search query is empty
        $stmt = $pdo->query("
            SELECT patient_id, first_name, middle_name, last_name, suffix, birthdate, sex, purok, contact_number
            FROM patients 
            WHERE is_archived = 0 
            ORDER BY last_name ASC, first_name ASC
            LIMIT 15
        ");
    }
    
    $results = [];
    foreach ($stmt->fetchAll() as $pat) {
        $dob = new DateTime($pat['birthdate']);
        $age = (new DateTime())->diff($dob)->y;
        $fullName = $pat['last_name'] . ', ' . $pat['first_name'] . ($pat['middle_name'] ? ' ' . substr($pat['middle_name'], 0, 1) . '.' : '') . ($pat['suffix'] ? ' ' . $pat['suffix'] : '');
        
        $results[] = [
            'id' => $pat['patient_id'],
            'text' => $fullName . " (" . $age . " yrs, born " . $pat['birthdate'] . ")",
            'first_name' => $pat['first_name'],
            'last_name' => $pat['last_name'],
            'middle_name' => $pat['middle_name'],
            'suffix' => $pat['suffix'],
            'age' => $age,
            'birthdate' => date('Y-m-d', strtotime($pat['birthdate'])),
            'sex' => $pat['sex'],
            'purok' => $pat['purok'],
            'contact_number' => $pat['contact_number'] ?: 'N/A'
        ];
    }
    
    echo json_encode(['results' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Patient search AJAX error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to query database.']);
}
