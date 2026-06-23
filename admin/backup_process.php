<?php
// admin/backup_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Security Violation',
        'message' => 'Direct access to backup download is not permitted.'
    ];
    header('Location: ' . BASE_URL . 'admin/settings.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Security Error',
        'message' => 'CSRF verification failed.'
    ];
    header('Location: ' . BASE_URL . 'admin/settings.php');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get all tables
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    // Log backup activity (do this before sending output in case it fails)
    log_activity($pdo, "Database manual backup performed", "System", null, "Generated SQL file of all tables");
    
    // Set streaming headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="sinalhan_db_backup_' . date('Ymd_His') . '.sql"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Turn off output buffering if active
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    echo "-- Barangay Sinalhan Patient Management System Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
    flush();
    
    foreach ($tables as $table) {
        // Table schema
        $schemaStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $schema = $schemaStmt->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `{$table}`;\n";
        echo $schema[1] . ";\n\n";
        flush();
        
        // Table data
        $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
        while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            $keys = array_keys($row);
            $values = array_values($row);
            
            $escapedValues = array_map(function($val) use ($pdo) {
                if ($val === null) return 'NULL';
                return $pdo->quote($val);
            }, $values);
            
            echo "INSERT INTO `{$table}` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escapedValues) . ");\n";
        }
        echo "\n";
        flush();
    }
    
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    flush();
    if (!defined('TESTING')) exit;
    
} catch (Exception $e) {
    error_log("Database backup error: " . $e->getMessage());
    if (headers_sent()) {
        echo "\n-- ERROR: Backup failed during table streaming.\n";
        echo "-- Details: " . $e->getMessage() . "\n";
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Backup Failed',
            'message' => $e->getMessage()
        ];
        header('Location: ' . BASE_URL . 'admin/settings.php');
    }
    if (!defined('TESTING')) exit;
}
