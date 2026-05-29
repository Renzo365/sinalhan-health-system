<?php
// admin/purge_logs_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/settings.php');
    exit;
}

try {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        throw new Exception('CSRF verification failed.');
    }

    $timeframe = $_POST['timeframe'] ?? '';
    $pdo = Database::getInstance()->getConnection();

    if ($timeframe === '6_months') {
        $interval = 'INTERVAL 6 MONTH';
        $desc = 'older than 6 months';
    } elseif ($timeframe === '1_year') {
        $interval = 'INTERVAL 1 YEAR';
        $desc = 'older than 1 year';
    } else {
        throw new Exception('Invalid purge timeframe selected.');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Perform deletion
    $stmt = $pdo->prepare("DELETE FROM activity_log WHERE created_at < NOW() - {$interval}");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();

    // Log the purge activity
    log_activity($pdo, "Purged activity logs {$desc}", "System", null, "Deleted {$deletedCount} log entries.");

    $pdo->commit();

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Purge Completed',
        'message' => "Successfully deleted {$deletedCount} log entries."
    ];

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Purge Failed',
        'message' => $e->getMessage()
    ];
}

header('Location: ' . BASE_URL . 'admin/settings.php');
exit;
