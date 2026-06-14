<?php
/**
 * ajax/check_appointment_conflict.php
 * 
 * AJAX endpoint to check if a patient already has a scheduled appointment 
 * at the same date and time slot.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');

// Only allow authenticated clinic users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$patientId = (int)($_POST['patient_id'] ?? 0);
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$appointmentTime = trim($_POST['appointment_time'] ?? '') ?: null;
$appId = (int)($_POST['appointment_id'] ?? 0);

// If minimum fields are missing, return no conflict safely
if ($patientId <= 0 || empty($appointmentDate)) {
    echo json_encode(['conflict' => false]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check conflict: Same patient, same date, same time (if time is specified)
    // Exclude the current appointment ID when updating
    $sql = "
        SELECT COUNT(*) 
        FROM appointments a
        WHERE a.patient_id = ? 
          AND a.appointment_date = ? 
          AND a.is_archived = 0 
          AND a.status != 'Cancelled'
    ";
    
    $params = [$patientId, $appointmentDate];
    
    if ($appointmentTime) {
        // Convert to standard 24h format for database comparison
        $formattedTime = date('H:i:00', strtotime($appointmentTime));
        $sql .= " AND a.appointment_time = ? ";
        $params[] = $formattedTime;
    } else {
        $sql .= " AND a.appointment_time IS NULL ";
    }
    
    if ($appId > 0) {
        $sql .= " AND a.appointment_id != ? ";
        $params[] = $appId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();
    
    if ($count > 0) {
        // Fetch details of the conflicting appointment
        $detailsSql = "
            SELECT a.appointment_time, st.service_name 
            FROM appointments a
            LEFT JOIN service_types st ON a.service_id = st.service_id
            WHERE a.patient_id = ? 
              AND a.appointment_date = ? 
              AND a.is_archived = 0 
              AND a.status != 'Cancelled'
        ";
        $detailsParams = [$patientId, $appointmentDate];
        if ($appointmentTime) {
            $detailsSql .= " AND a.appointment_time = ? ";
            $detailsParams[] = $formattedTime;
        } else {
            $detailsSql .= " AND a.appointment_time IS NULL ";
        }
        if ($appId > 0) {
            $detailsSql .= " AND a.appointment_id != ? ";
            $detailsParams[] = $appId;
        }
        
        $detailsStmt = $pdo->prepare($detailsSql);
        $detailsStmt->execute($detailsParams);
        $conflictApp = $detailsStmt->fetch();
        
        $timeStr = $conflictApp['appointment_time'] ? date('h:i A', strtotime($conflictApp['appointment_time'])) : 'no time slot';
        $srvName = $conflictApp['service_name'] ?? 'General Consultation';
        
        echo json_encode([
            'conflict' => true,
            'message' => "Patient already has a '" . htmlspecialchars($srvName) . "' appointment scheduled on this date at " . $timeStr . "."
        ]);
    } else {
        echo json_encode(['conflict' => false]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Failed to check appointment conflict AJAX error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to query database.']);
}
