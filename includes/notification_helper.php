<?php
// includes/notification_helper.php

/**
 * System In-App Notification Engine
 * 
 * Purpose:
 * Manages the generation, retrieval, and status tracking of user notifications.
 * Supports targeted notifications (user_id is set) as well as global broadcasts (user_id is null)
 * which are shown to all active health staff members.
 */

/**
 * Creates a system notification.
 * If $userId is null, the notification is treated as a global/broadcast message for all staff.
 *
 * @param PDO $pdo DB connection handle
 * @param int|null $userId Target user ID, or null for global broadcast
 * @param string $title Header of the notification alert
 * @param string $message Detailed body text of the alert
 * @param string $type Visual theme of the alert ('info', 'success', 'warning', 'danger', 'security')
 * @return bool True on success, false on failure
 */
function add_notification($pdo, $userId, $title, $message, $type = 'info') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        return $stmt->execute([$userId, $title, $message, $type]);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper to fetch the Admin's user ID dynamically.
 * Useful for routing security alerts directly to the system administrator.
 *
 * @param PDO $pdo DB connection handle
 * @return int|null User ID of the administrator, or null if not found
 */
function get_admin_user_id($pdo) {
    try {
        $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Fetches the notifications list for a specific user (including global broadcasts).
 *
 * @param PDO $pdo DB connection handle
 * @param int $userId Target user ID
 * @param int $limit Max results to return for UI dropdowns
 * @return array List of notification records
 */
function fetch_user_notifications($pdo, $userId, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT notification_id, title, message, type, is_read, created_at
            FROM notifications
            WHERE user_id = ? OR user_id IS NULL
            ORDER BY created_at DESC, notification_id DESC
            LIMIT ?
        ");
        // PDO needs limit bound as an integer parameter if emulated prepares are disabled
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to fetch notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Gets the count of unread notifications for a specific user (including broadcasts).
 * Used to update the notification bell count dynamically in sidebar updates.
 *
 * @param PDO $pdo DB connection handle
 * @param int $userId Target user ID
 * @return int Number of unread alerts
 */
function get_unread_count($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM notifications
            WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Failed to count unread notifications: " . $e->getMessage());
        return 0;
    }
}

/**
 * Marks a specific notification as read.
 * Checks target user ownership or global broadcast availability for safety.
 *
 * @param PDO $pdo DB connection handle
 * @param int $notificationId Notification record ID
 * @param int $userId Target user ID verifying ownership/access
 * @return bool True on success, false on exception
 */
function mark_notification_as_read($pdo, $notificationId, $userId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE notification_id = ? AND (user_id = ? OR user_id IS NULL)
        ");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
        error_log("Failed to mark notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Marks all notifications for a specific user (including broadcasts) as read.
 *
 * @param PDO $pdo DB connection handle
 * @param int $userId Target user ID
 * @return bool True on success, false on exception
 */
function mark_all_notifications_as_read($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0
        ");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Failed to mark all notifications as read: " . $e->getMessage());
        return false;
    }
}

