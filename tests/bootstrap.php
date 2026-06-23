<?php
// tests/bootstrap.php

// 1. Establish Testing Mode globally
define('TESTING', true);

// 2. Configure error reporting for development/testing visibility
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. Override DB environment variables for safety before database file loads
putenv("DB_NAME=bhc_sinalhan_db_test");
$_ENV['DB_NAME'] = 'bhc_sinalhan_db_test';
$_SERVER['DB_NAME'] = 'bhc_sinalhan_db_test';

// 4. Load environment loader and app configs
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
