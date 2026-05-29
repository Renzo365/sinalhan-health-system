<?php
// ajax/active_users.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access for auditing online users
require_role(['admin']);

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Select users with a login timestamp within the last 15 minutes
    $stmt = $pdo->query("
        SELECT user_id, username, first_name, last_name, role, last_login 
        FROM users 
        WHERE last_login >= NOW() - INTERVAL 15 MINUTE 
          AND is_active = 1 
          AND is_archived = 0 
        ORDER BY last_login DESC
    ");
    $activeUsers = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'count' => count($activeUsers),
        'users' => $activeUsers
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("AJAX Active Users feed error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load online users.'
    ]);
}
