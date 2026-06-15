<?php
// config/session.php
require_once __DIR__ . '/app.php';

/**
 * Session Security Configuration & Activity Monitor
 * 
 * Purpose:
 * Configures PHP session parameters to prevent attacks like Session Fixation, Session Hijacking, and CSRF.
 * Also monitors user activity time to automatically expire sessions after 30 minutes of inactivity.
 */

if (php_sapi_name() !== 'cli') {
    // Session security configurations for browser/web requests
    
    // 1. Prevent client-side scripts (JS) from accessing session cookie (mitigates XSS cookie theft)
    ini_set('session.cookie_httponly', 1);     
    
    // 2. Force session ID storage exclusively in cookies (prevents session hijacking via query strings)
    ini_set('session.use_only_cookies', 1);    
    
    // 3. Restrict session cookies to SameSite 'Strict' (prevents cross-site request forgery attacks)
    ini_set('session.cookie_samesite', 'Strict'); 
    
    // 4. Force session cookies over HTTPS dynamically (mitigates man-in-the-middle session theft)
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    
    // 5. Set maximum session lifetime on the server to 30 minutes (1800 seconds)
    ini_set('session.gc_maxlifetime', 1800);   

    // Initialize session if not already started by another page/file
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 5. Inactivity Session Timeout Guard
    if (isset($_SESSION['last_activity'])) {
        // Calculate total idle seconds
        $inactive = time() - $_SESSION['last_activity'];
        
        // If idle time exceeds 30 minutes (1800 seconds)
        if ($inactive >= 1800) { 
            // Clear and destroy the session
            session_unset();
            session_destroy();
            
            // Re-initialize a blank session temporarily to flash a timeout notification to the login page
            session_start();
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Session Expired',
                'message' => 'You have been logged out due to inactivity.'
            ];
            
            // Redirect to login page
            header('Location: ' . BASE_URL . 'auth/login.php?timeout=1');
            exit;
        }
    }
    
    // Update the last active timestamp to current time for subsequent request checks
    $_SESSION['last_activity'] = time();
}

