<?php
// index.php
require_once __DIR__ . '/config/session.php';

if (isset($_SESSION['user_id'])) {
    // User is logged in, redirect based on their role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            break;
        case 'staff':
        case 'bhw':
            header('Location: ' . BASE_URL . 'patients/list.php');
            break;
        default:
            // Fallback: clear session and redirect to login
            session_unset();
            session_destroy();
            header('Location: ' . BASE_URL . 'auth/login.php');
    }
} else {
    // User is not logged in, redirect to login page
    header('Location: ' . BASE_URL . 'auth/login.php');
}
exit;
