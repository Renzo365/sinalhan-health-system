<?php
// config/app.php

// Define timezone
date_default_timezone_set('Asia/Manila');

// Dynamic Base URL calculation (robust for different subdirectory layouts)
if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli') {
        define('BASE_URL', '/');
    } else {
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $appDir = str_replace('\\', '/', dirname(__DIR__));
        $relative = str_replace($docRoot, '', $appDir);
        $base = '/' . trim($relative, '/') . '/';
        if ($base === '//') {
            $base = '/';
        }
        define('BASE_URL', $base);
    }
}

// App Constants
define('APP_NAME', 'Sinalhan Health Center Patient Management System');
define('APP_VERSION', '1.0.0');

// Global error logging setup
ini_set('display_errors', 0); // Hide raw errors from users
ini_set('log_errors', 1);

// Ensure logs directory exists
$logDir = dirname(__DIR__) . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/error.log');

// Global escaping function for XSS protection
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// HIPAA Cryptographic Key (MUST be 32 bytes or longer, securely backed up)
define('ENCRYPTION_KEY', 'SinalhanHealthCenterDataKey2026_Secure32Bytes!');

