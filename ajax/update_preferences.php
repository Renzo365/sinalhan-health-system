<?php
// ajax/update_preferences.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Verify CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$preference = trim($_POST['preference'] ?? '');
$value = trim($_POST['value'] ?? '');

if (!in_array($preference, ['theme', 'font_size'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid preference field']);
    exit;
}

// Validate preference values
if ($preference === 'theme' && !in_array($value, ['light', 'dark'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid theme value']);
    exit;
}
if ($preference === 'font_size' && !in_array($value, ['normal', 'medium', 'large'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid font size value']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Update preferences in database and session cache
    if ($preference === 'theme') {
        $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE user_id = ?");
        $stmt->execute([$value, $userId]);
        $_SESSION['theme'] = $value;
    } else {
        $stmt = $pdo->prepare("UPDATE users SET font_size = ? WHERE user_id = ?");
        $stmt->execute([$value, $userId]);
        $_SESSION['font_size'] = $value;
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Failed to save user UI preference: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database update failed']);
}
