<?php
// ajax/get_csrf_token.php
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please log in again.'
    ]);
    exit;
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'success' => true,
    'csrf_token' => $_SESSION['csrf_token']
]);
