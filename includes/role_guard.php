<?php
// includes/role_guard.php
require_once __DIR__ . '/../config/session.php';

/**
 * Role-Based Access Control (RBAC) Guard
 * 
 * Purpose:
 * Enforces page-level authorization. Restricts access to specific pages
 * based on user roles (e.g. 'admin', 'staff', 'bhw').
 * If a user does not possess an allowed role, they are redirected
 * to their respective module dashboard with a permission alert.
 * 
 * @param array $allowed_roles List of roles permitted to view the page (e.g. ['admin', 'staff'])
 */
function require_role(array $allowed_roles) {
    // Check if the current user's session role is missing or not in the allowed list
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // Flash access denied error alert
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Access Denied',
            'message' => 'You do not have permission to access this page.'
        ];
        
        // Redirect the unauthorized user to their default landing page based on role
        if (isset($_SESSION['role'])) {
            switch ($_SESSION['role']) {
                case 'admin':
                    // Administrators go to the central stats dashboard
                    header('Location: ' . BASE_URL . 'admin/dashboard.php');
                    break;
                case 'staff':
                case 'bhw':
                    // Health Staff and BHWs default to the patient directory directory list
                    header('Location: ' . BASE_URL . 'patients/list.php');
                    break;
                default:
                    // Unknown roles are sent to the login screen
                    header('Location: ' . BASE_URL . 'auth/login.php');
            }
        } else {
            // Unauthenticated requests go to the login screen
            header('Location: ' . BASE_URL . 'auth/login.php');
        }
        exit;
    }
}

