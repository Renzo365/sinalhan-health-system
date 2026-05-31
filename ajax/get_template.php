<?php
// ajax/get_template.php
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

$templateId = (int)($_GET['template_id'] ?? 0);

if ($templateId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid template ID']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT template_id, template_name, chief_complaint, diagnosis, treatment, prescription 
        FROM consultation_templates 
        WHERE template_id = ?
    ");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['error' => 'Template not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'template' => [
            'id' => $template['template_id'],
            'name' => $template['template_name'],
            'chief_complaint' => $template['chief_complaint'],
            'diagnosis' => $template['diagnosis'],
            'treatment' => $template['treatment'],
            'prescription' => $template['prescription']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Failed to fetch template AJAX error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to query template.']);
}
