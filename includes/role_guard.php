<?php
// includes/role_guard.php
require_once __DIR__ . '/../config/session.php';

/**
 * Enforce page-level access control based on user roles.
 * Redirects unauthorized users to their appropriate landing pages.
 * 
 * @param array $allowed_roles Array of roles allowed to access the page (e.g. ['admin', 'staff'])
 */
function require_role(array $allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Access Denied',
            'message' => 'You do not have permission to access this page.'
        ];
        
        // Redirect to appropriate landing page based on role
        if (isset($_SESSION['role'])) {
            switch ($_SESSION['role']) {
                case 'admin':
                    header('Location: ' . BASE_URL . 'admin/dashboard.php');
                    break;
                case 'staff':
                case 'bhw':
                    header('Location: ' . BASE_URL . 'patients/list.php');
                    break;
                default:
                    header('Location: ' . BASE_URL . 'auth/login.php');
            }
        } else {
            header('Location: ' . BASE_URL . 'auth/login.php');
        }
        exit;
    }
}
