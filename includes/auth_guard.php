<?php
// includes/auth_guard.php
require_once __DIR__ . '/../config/session.php';

// If user session is not active, redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = [
        'type' => 'warning',
        'title' => 'Access Denied',
        'message' => 'Please log in to access this page.'
    ];
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
