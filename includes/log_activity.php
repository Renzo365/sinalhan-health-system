<?php
// includes/log_activity.php

/**
 * Log user activity to the database for auditing purposes.
 * 
 * @param PDO $pdo The database connection instance.
 * @param string $action The action performed (e.g. 'Logged in', 'Registered patient').
 * @param string $module The system module name (e.g. 'Auth', 'Patient Records').
 * @param int|null $record_id The primary key of the modified/created record, if any.
 * @param string|null $details Additional text description or JSON data.
 */
function log_activity($pdo, $action, $module, $record_id = null, $details = null) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, module, record_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $module,
            $record_id,
            $details,
            $ipAddress
        ]);
    } catch (PDOException $e) {
        // Log database failure to php error log, don't crash the main app
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
