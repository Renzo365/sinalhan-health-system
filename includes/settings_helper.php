<?php
// includes/settings_helper.php

/**
 * Gets a configuration value from system_settings.
 *
 * @param PDO $pdo DB Connection
 * @param string $key Setting Key
 * @param mixed $default Default fallback value if not found
 * @return mixed The setting value or default
 */
function get_setting($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? $val : $default;
    } catch (Exception $e) {
        error_log("Failed to fetch setting '{$key}': " . $e->getMessage());
        return $default;
    }
}

/**
 * Sets a configuration value in system_settings.
 *
 * @param PDO $pdo DB Connection
 * @param string $key Setting Key
 * @param string|null $value Setting Value
 * @return bool True on success, false on failure
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
