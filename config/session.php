<?php
// config/session.php
require_once __DIR__ . '/app.php';

if (php_sapi_name() !== 'cli') {
    // Session configuration for web requests
    ini_set('session.cookie_httponly', 1);     // Prevent JS access to session cookie
    ini_set('session.use_only_cookies', 1);    // Prevent session fixation via URL
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    ini_set('session.gc_maxlifetime', 1800);   // 30-minute session lifetime on server

    // Start session if not already active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Session timeout check (30 minutes of inactivity)
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        if ($inactive >= 1800) { // 30 minutes
            // Destroy session
            session_unset();
            session_destroy();
            
            // Restart session to flash timeout alert
            session_start();
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Session Expired',
                'message' => 'You have been logged out due to inactivity.'
            ];
            header('Location: ' . BASE_URL . 'auth/login.php?timeout=1');
            exit;
        }
    }
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
}
