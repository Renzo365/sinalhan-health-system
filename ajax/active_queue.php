<?php
// ajax/active_queue.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

// Allow authenticated sessions to fetch active queue
require_role(['admin', 'staff', 'bhw']);

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();

    // Fetch serving tickets for today
    $servingStmt = $pdo->query("
        SELECT 
            q.queue_number, 
            p.first_name, 
            p.last_name, 
            p.suffix,
            st.service_name 
        FROM queue q 
        JOIN patients p ON q.patient_id = p.patient_id 
        LEFT JOIN service_types st ON q.service_id = st.service_id 
        WHERE q.is_archived = 0 AND q.queue_date = CURDATE() AND q.status = 'Serving'
        ORDER BY q.serving_time DESC
    ");
    $servingRaw = $servingStmt->fetchAll();
    
    $serving = [];
    foreach ($servingRaw as $s) {
        $serving[] = [
            'number' => str_pad($s['queue_number'], 3, '0', STR_PAD_LEFT),
            'patient_name' => htmlspecialchars($s['first_name'] . ($s['suffix'] ? ' ' . $s['suffix'] : '') . ' ' . $s['last_name']),
            'service_name' => htmlspecialchars($s['service_name'] ?? 'General Consultation')
        ];
    }

    // Fetch waiting tickets for today (limit to next 5)
    $waitingStmt = $pdo->query("
        SELECT 
            q.queue_number, 
            p.first_name, 
            p.last_name, 
            p.suffix,
            st.service_name 
        FROM queue q 
        JOIN patients p ON q.patient_id = p.patient_id 
        LEFT JOIN service_types st ON q.service_id = st.service_id 
        WHERE q.is_archived = 0 AND q.queue_date = CURDATE() AND q.status = 'Waiting'
        ORDER BY q.queue_number ASC
        LIMIT 5
    ");
    $waitingRaw = $waitingStmt->fetchAll();

    $waiting = [];
    foreach ($waitingRaw as $w) {
        $waiting[] = [
            'number' => str_pad($w['queue_number'], 3, '0', STR_PAD_LEFT),
            'patient_name' => htmlspecialchars($w['first_name'] . ($w['suffix'] ? ' ' . $w['suffix'] : '') . ' ' . $w['last_name']),
            'service_name' => htmlspecialchars($w['service_name'] ?? 'General Consultation')
        ];
    }

    echo json_encode([
        'success' => true,
        'serving' => $serving,
        'waiting' => $waiting
    ]);

} catch (Exception $e) {
    error_log("Failed to load active queue details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'System error loading queue details.'
    ]);
}
