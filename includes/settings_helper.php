<?php
// includes/settings_helper.php

/**
 * System Settings Manager Helpers
 * 
 * Purpose:
 * Provides unified helper functions to retrieve or modify configuration values stored in
 * the `system_settings` database table (e.g. clinic name, session lifetime, 2FA mandates).
 */

/**
 * Gets a configuration value from system_settings.
 *
 * @param PDO $pdo Active DB connection handle
 * @param string $key Unique setting identifier key (e.g. 'clinic_name')
 * @param mixed $default Default fallback value if setting doesn't exist
 * @return mixed The stored configuration string or default fallback
 */
function get_setting($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        
        // Fetch the single column value
        $val = $stmt->fetchColumn();
        
        // If query returns a valid result, return it; otherwise return default
        return ($val !== false && $val !== null) ? $val : $default;
    } catch (Exception $e) {
        // Log query errors to PHP error log to prevent crashing caller templates
        error_log("Failed to fetch setting '{$key}': " . $e->getMessage());
        return $default;
    }
}

/**
 * Sets or updates a configuration value in system_settings.
 * Uses upsert (INSERT ON DUPLICATE KEY UPDATE) to handle key additions/updates cleanly.
 *
 * @param PDO $pdo Active DB connection handle
 * @param string $key Unique setting identifier key (e.g. 'clinic_name')
 * @param string|null $value The setting configuration value to save
 * @return bool True on query success, false on exception
 */
function set_setting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        error_log("Failed to save setting '{$key}': " . $e->getMessage());
        return false;
    }
}

