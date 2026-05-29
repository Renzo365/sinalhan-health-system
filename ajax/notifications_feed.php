<?php
// ajax/notifications_feed.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    if (!defined('TESTING')) exit;
}

$pdo = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // CSRF Verification
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'CSRF verification failed.']);
            if (!defined('TESTING')) exit;
        } else {
            $action = $_POST['action'] ?? '';

            if ($action === 'mark_read') {
                $notificationId = (int)($_POST['notification_id'] ?? 0);
                if ($notificationId > 0) {
                    $result = mark_notification_as_read($pdo, $notificationId, $userId);
                    echo json_encode(['success' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
                }
                if (!defined('TESTING')) exit;
            } else if ($action === 'mark_all_read') {
                $result = mark_all_notifications_as_read($pdo, $userId);
                echo json_encode(['success' => $result]);
                if (!defined('TESTING')) exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid post action.']);
                if (!defined('TESTING')) exit;
            }
        }
    } else {
    // GET request - fetch list and count
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    if ($limit <= 0 || $limit > 20) {
        $limit = 5;
    }

    $unreadCount = get_unread_count($pdo, $userId);
    $list = fetch_user_notifications($pdo, $userId, $limit);

    // Format created_at to human readable (e.g. "2 mins ago")
    $formattedList = [];
    foreach ($list as $item) {
        $timeAgo = '';
        $timestamp = strtotime($item['created_at']);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            $timeAgo = 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            $timeAgo = $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            $timeAgo = $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($diff / 86400);
            $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        $formattedList[] = [
            'id' => (int)$item['notification_id'],
            'title' => htmlspecialchars($item['title']),
            'message' => htmlspecialchars($item['message']),
            'type' => $item['type'],
            'is_read' => (int)$item['is_read'],
            'time_ago' => $timeAgo
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => $unreadCount,
        'notifications' => $formattedList
    ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
