<?php
// includes/log_activity.php

/**
 * Write-Only System Audit Logging
 * 
 * Purpose:
 * Logs all user-performed actions (e.g. adding patients, changing schedules, configuring settings)
 * to the `activity_log` database table. This constitutes the system's security audit trail.
 * Designed with error isolation: if the logging query fails, it logs to the PHP server log
 * and fails silently rather than crashing the user-facing web request.
 * 
 * @param PDO $pdo The active database connection instance.
 * @param string $action Description of action performed (e.g. 'Logged in', 'Registered patient').
 * @param string $module System module name ('Patient Records', 'Health Records', 'Appointment', 'Queue', 'Admin', 'Auth', 'System').
 * @param int|null $record_id The primary key ID of the affected table record.
 * @param string|null $details Additional context (e.g. metadata, IP addresses, change details).
 */
function log_activity($pdo, $action, $module, $record_id = null, $details = null) {
    try {
        // Retrieve executing user's ID from active session, or null if guest/anonymous action
        $userId = $_SESSION['user_id'] ?? null;
        
        // Fetch client IP address, default to localhost loopback if missing
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Prepare write-only insert statement
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, module, record_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Execute parameter bindings (prevents SQL injection)
        $stmt->execute([
            $userId,
            $action,
            $module,
            $record_id,
            $details,
            $ipAddress
        ]);
    } catch (PDOException $e) {
        // Log database failure to PHP server error log; don't interrupt active user workflow
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

