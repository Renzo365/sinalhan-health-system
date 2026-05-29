<?php
// admin/backup_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';

require_role(['admin']);

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get all tables
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $sql = "-- Barangay Sinalhan Patient Management System Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Table schema
        $schemaStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $schema = $schemaStmt->fetch(PDO::FETCH_NUM);
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $schema[1] . ";\n\n";
        
        // Table data
        $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
        while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            $keys = array_keys($row);
            $values = array_values($row);
            
            $escapedValues = array_map(function($val) use ($pdo) {
                if ($val === null) return 'NULL';
                return $pdo->quote($val);
            }, $values);
            
            $sql .= "INSERT INTO `{$table}` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escapedValues) . ");\n";
        }
        $sql .= "\n";
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Log backup activity
    log_activity($pdo, "Database manual backup performed", "System", null, "Generated SQL file of all tables");
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="sinalhan_db_backup_' . date('Ymd_His') . '.sql"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    if (!defined('TESTING')) exit;
    
} catch (Exception $e) {
    error_log("Database backup error: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Backup Failed',
        'message' => $e->getMessage()
    ];
    header('Location: ' . BASE_URL . 'admin/settings.php');
    if (!defined('TESTING')) exit;
}
