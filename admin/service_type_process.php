<?php
// admin/service_type_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Invalid Request',
        'message' => 'Direct access is not allowed.'
    ];
    header('Location: ' . BASE_URL . 'admin/service_types.php');
    if (!defined('TESTING')) exit;
}

// 1. Verify CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Security Error',
        'message' => 'CSRF verification failed. Request denied.'
    ];
    header('Location: ' . BASE_URL . 'admin/service_types.php');
    if (!defined('TESTING')) exit;
}

$action = $_POST['action'] ?? '';
$pdo = Database::getInstance()->getConnection();

try {
    switch ($action) {
        case 'add':
            $serviceName = trim($_POST['service_name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;

            if (empty($serviceName)) {
                throw new Exception('Service category name is required.');
            }

            // Check if service name already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM service_types WHERE LOWER(service_name) = LOWER(?)");
            $checkStmt->execute([$serviceName]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Service category '{$serviceName}' already exists.");
            }

            // Insert new service type
            $insertStmt = $pdo->prepare("
                INSERT INTO service_types (service_name, description, is_active)
                VALUES (?, ?, 1)
            ");
            $insertStmt->execute([$serviceName, $description]);
            $newServiceId = $pdo->lastInsertId();

            log_activity($pdo, "Created service category '{$serviceName}'", 'Admin', $newServiceId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Category Created',
                'message' => "Service category '{$serviceName}' has been created."
            ];
            header('Location: ' . BASE_URL . 'admin/service_types.php');
            if (!defined('TESTING')) exit;
            break;

        case 'edit':
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $serviceName = trim($_POST['service_name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;

            if (!$serviceId) {
                throw new Exception('Invalid service category ID.');
            }

            if (empty($serviceName)) {
                throw new Exception('Service category name is required.');
            }

            // Fetch current service details
            $serviceStmt = $pdo->prepare("SELECT service_name FROM service_types WHERE service_id = ?");
            $serviceStmt->execute([$serviceId]);
            $currentService = $serviceStmt->fetchColumn();

            if (!$currentService) {
                throw new Exception('Service category not found.');
            }

            // Check uniqueness of name if changed
            if (strtolower($currentService) !== strtolower($serviceName)) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM service_types WHERE LOWER(service_name) = LOWER(?) AND service_id != ?");
                $checkStmt->execute([$serviceName, $serviceId]);
                if ($checkStmt->fetchColumn() > 0) {
                    throw new Exception("Service category name '{$serviceName}' is already taken.");
                }
            }

            // Update details
            $updateStmt = $pdo->prepare("
                UPDATE service_types 
                SET service_name = ?, description = ?
                WHERE service_id = ?
            ");
            $updateStmt->execute([$serviceName, $description, $serviceId]);

            log_activity($pdo, "Updated service category '{$currentService}'", 'Admin', $serviceId, "New Name: {$serviceName}");

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Category Updated',
                'message' => "Service category updated successfully."
            ];
            header('Location: ' . BASE_URL . 'admin/service_types.php');
            if (!defined('TESTING')) exit;
            break;

        case 'toggle_status':
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $status = (int)($_POST['status'] ?? 0); // 1 = active, 0 = inactive

            if (!$serviceId) {
                throw new Exception('Invalid service category ID.');
            }

            // Fetch current service name
            $serviceStmt = $pdo->prepare("SELECT service_name FROM service_types WHERE service_id = ?");
            $serviceStmt->execute([$serviceId]);
            $serviceName = $serviceStmt->fetchColumn();

            if (!$serviceName) {
                throw new Exception('Service category not found.');
            }

            // Update status
            $updateStmt = $pdo->prepare("UPDATE service_types SET is_active = ? WHERE service_id = ?");
            $updateStmt->execute([$status, $serviceId]);

            $statusText = $status === 1 ? 'Activated' : 'Deactivated';
            log_activity($pdo, "{$statusText} service category '{$serviceName}'", 'Admin', $serviceId);

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => "Category {$statusText}",
                'message' => "The category '{$serviceName}' has been {$statusText}."
            ];
            header('Location: ' . BASE_URL . 'admin/service_types.php');
            if (!defined('TESTING')) exit;
            break;

        default:
            throw new Exception('Invalid action parameter.');
    }
} catch (Exception $e) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Operation Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/service_types.php'));
    if (!defined('TESTING')) exit;
}
