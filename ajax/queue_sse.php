<?php
// ajax/queue_sse.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering on Nginx/IIS if present

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allow authenticated sessions to fetch active queue
require_role(['admin', 'staff', 'bhw']);

require_once __DIR__ . '/../config/database.php';

// Turn off PHP time limit and output buffering
set_time_limit(0);
if (!defined('TESTING')) {
    while (ob_get_level()) {
        ob_end_clean();
    }
}

// Close session writing to prevent blocking concurrent user requests
session_write_close();

$pdo = Database::getInstance()->getConnection();

$lastStateHash = '';
$maxCycles = defined('TESTING') ? 1 : 30; // Limit execution time to 30 seconds to refresh connection periodically

for ($cycle = 0; $cycle < $maxCycles; $cycle++) {
    if (connection_aborted()) {
        break;
    }

    try {
        // Query to check for any queue ticket changes today
        $stateStmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_count, 
                COALESCE(SUM(queue_number), 0) AS total_sum, 
                MAX(serving_time) AS max_serving, 
                MAX(completed_time) AS max_completed 
            FROM queue 
            WHERE queue_date = CURDATE() AND is_archived = 0
        ");
        $state = $stateStmt->fetch();

        // Create a unique hash representing the current queue state
        $currentStateHash = md5(implode('|', [
            $state['total_count'],
            $state['total_sum'],
            $state['max_serving'] ?? 'null',
            $state['max_completed'] ?? 'null'
        ]));

        // If the state has changed, query full active queue data and push it
        if ($currentStateHash !== $lastStateHash) {
            $lastStateHash = $currentStateHash;

            // 1. Fetch serving tickets for today
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

            // 2. Fetch waiting tickets for today (limit to next 5)
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

            // Send SSE message block
            $payload = json_encode([
                'success' => true,
                'serving' => $serving,
                'waiting' => $waiting
            ]);

            echo "data: {$payload}\n\n";
            if (!defined('TESTING')) {
                @ob_flush();
                @flush();
            }
        }

    } catch (Exception $e) {
        error_log("SSE Queue Event Stream error: " . $e->getMessage());
        // Send error packet
        echo "data: " . json_encode(['success' => false, 'message' => $e->getMessage()]) . "\n\n";
        if (!defined('TESTING')) {
            @ob_flush();
            @flush();
        }
        break; // Exit loop on exception
    }

    sleep(1);
}
echo "event: close\ndata: end\n\n";
if (!defined('TESTING')) {
    @ob_flush();
    @flush();
}
