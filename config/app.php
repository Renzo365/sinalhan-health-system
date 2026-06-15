<?php
// config/app.php
require_once __DIR__ . '/env.php';


/**
 * App Configuration Settings
 * 
 * Purpose:
 * Establishes system-wide constants, paths, error logging configuration, 
 * date settings, and critical security functions (escaping helper).
 */

// Define timezone (Asia/Manila timezone is standard for the Sinalhan Health Center, Santa Rosa City)
date_default_timezone_set('Asia/Manila');

/**
 * Dynamic Base URL Calculation
 * 
 * Purpose:
 * Automatically detects whether the application is running inside a root domain or a subdirectory
 * (e.g. http://localhost/sinalhan-health-system/ or http://localhost/sinalhan-health-system/subfolders/).
 * This allows all asset URLs and redirects to resolve correctly without manual configuration edits.
 */
if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli') {
        // Fallback for CLI environments (e.g. running background scripts or crons)
        define('BASE_URL', '/');
    } else {
        // Get server document root directory path and sanitize windows/unix backslashes
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        // Get application base root directory path
        $appDir = str_replace('\\', '/', dirname(__DIR__));
        // Calculate the difference/relative folder structure (case-insensitive for Windows compatibility)
        $relative = preg_replace('/^' . preg_quote($docRoot, '/') . '/i', '', $appDir);
        // Build base URL string
        $base = '/' . trim($relative, '/') . '/';
        // Fallback to absolute root if blank
        if ($base === '//') {
            $base = '/';
        }
        define('BASE_URL', $base);
    }
}

// Core App Metadata Constants
define('APP_NAME', 'Sinalhan Health Center Patient Management System');
define('APP_VERSION', '1.0.0');

/**
 * Global Error Logging and Display Controls
 * 
 * Security Rule:
 * Never display detailed PHP runtime exceptions or stack traces to end users (mitigates information disclosure risks).
 * Raw errors are hidden (display_errors = 0) and redirected to a secure write-only server log file (logs/error.log).
 */
ini_set('display_errors', 0); // Hide raw errors from public view
ini_set('log_errors', 1);     // Enable server-side error logging

// Verify or dynamically create logs folder inside the project scope
$logDir = dirname(__DIR__) . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true); // Create directory with standard read/write permissions
}
// Set error log destination path
ini_set('error_log', $logDir . '/error.log');

/**
 * Global HTML Escaping Function
 * 
 * Purpose:
 * Mitigates Cross-Site Scripting (XSS) injection attacks.
 * All dynamic values outputted inside HTML elements should be wrapped in e() to strip malicious tags.
 * 
 * @param string|null $string The raw input to be escaped
 * @return string The safe, HTML-encoded string
 */
if (!function_exists('e')) {
    function e($string) {
        // ENT_QUOTES ensures both single and double quotes are escaped safely
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * HIPAA Cryptographic Encryption Key
 * 
 * Security Rule:
 * Must be a cryptographically secure 32-byte string.
 * This key is utilized by the OpenSSL AES-256-CBC engine to encrypt sensitive patient records.
 */
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'default_sinalhan_health_center_key_32_bytes_long_123');


