<?php
// includes/auth_guard.php
require_once __DIR__ . '/../config/session.php';

/**
 * Authentication Guard
 * 
 * Purpose:
 * Protects server-side pages from unauthorized public/guest access.
 * Included at the top of protected PHP scripts. If a user is not logged in
 * (i.e. 'user_id' is missing from session), they are redirected back to the login page.
 */

// If user session is not active, block access and redirect to login
if (!isset($_SESSION['user_id'])) {
    // Flash warning notification to the user
    $_SESSION['alert'] = [
        'type' => 'warning',
        'title' => 'Access Denied',
        'message' => 'Please log in to access this page.'
    ];
    
    // Redirect to login page
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Redirect user to force password update page if the flag is active
if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    if ($currentScript !== 'force_change_password.php' && $currentScript !== 'logout.php' && $currentScript !== 'force_change_password_process.php') {
        header('Location: ' . BASE_URL . 'auth/force_change_password.php');
        exit;
    }
}

