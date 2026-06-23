<?php
// tests/run_tests.php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

// Helper for colored console output
function color_log($message, $color = 'white') {
    $colors = [
        'red'    => "\033[31m",
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'blue'   => "\033[34m",
        'magenta'=> "\033[35m",
        'cyan'   => "\033[36m",
        'white'  => "\033[37m",
        'reset'  => "\033[0m"
    ];
    $prefix = isset($colors[$color]) ? $colors[$color] : '';
    $suffix = $colors['reset'];
    echo $prefix . $message . $suffix . "\n";
}

function initialize_test_database() {
    color_log("Initializing test database 'bhc_sinalhan_db_test'...", 'blue');

    $host = getenv('DB_HOST') ?: 'localhost';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    try {
        $pdo = new PDO("mysql:host={$host}", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("DROP DATABASE IF EXISTS bhc_sinalhan_db_test;");
        $pdo->exec("CREATE DATABASE bhc_sinalhan_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    } catch (PDOException $e) {
        color_log("MySQL connection failed: " . $e->getMessage(), 'red');
        exit(1);
    }

    // Locate mysql CLI
    $mysqlPath = 'mysql';
    if (file_exists('C:\\xampp\\mysql\\bin\\mysql.exe')) {
        $mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';
    }

    $sqlFile = realpath(__DIR__ . '/../sql/bhc_sinalhan_db.sql');
    if (!$sqlFile || !file_exists($sqlFile)) {
        color_log("SQL schema file not found at " . $sqlFile, 'red');
        exit(1);
    }

    // Modify schema import to use the test database
    $sqlContent = file_get_contents($sqlFile);
    $sqlContent = str_replace('USE bhc_sinalhan_db;', 'USE bhc_sinalhan_db_test;', $sqlContent);
    $sqlContent = str_replace('CREATE DATABASE IF NOT EXISTS bhc_sinalhan_db', 'CREATE DATABASE IF NOT EXISTS bhc_sinalhan_db_test', $sqlContent);

    $tempSqlFile = __DIR__ . '/temp_test_schema.sql';
    file_put_contents($tempSqlFile, $sqlContent);

    // Build mysql restore command
    $command = "\"{$mysqlPath}\" -u {$username} " . ($password ? "-p{$password} " : "") . "bhc_sinalhan_db_test < \"{$tempSqlFile}\" 2>&1";
    
    exec($command, $output, $returnVar);
    @unlink($tempSqlFile);

    if ($returnVar !== 0) {
        color_log("Failed to import SQL schema. CLI Output:", 'red');
        foreach ($output as $line) {
            echo "  " . $line . "\n";
        }
        exit(1);
    }

    color_log("Test database schema imported successfully.", 'green');
}

// 1. Initialize DB
initialize_test_database();

// 2. Discover Test Files
$testFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
foreach ($iterator as $file) {
    if ($file->isFile() && strpos($file->getFilename(), 'Test.php') !== false) {
        $testFiles[] = $file->getPathname();
    }
}

color_log("\nRunning test suite...", 'cyan');
$startTime = microtime(true);

$testsCount = 0;
$assertionsCount = 0;
$failedTests = [];

foreach ($testFiles as $filePath) {
    require_once $filePath;
    
    $className = basename($filePath, '.php');
    if (!class_exists($className)) {
        continue;
    }
    
    color_log("\nClass: {$className}", 'magenta');
    
    $methods = get_class_methods($className);
    $testMethods = array_filter($methods, function($method) {
        return strpos($method, 'test') === 0;
    });

    foreach ($testMethods as $method) {
        $testsCount++;
        $testCase = new $className();
        
        try {
            $testCase->setUp();
            $testCase->$method();
            $testCase->tearDown();
            
            $assertionsCount += $testCase->assertionsCount;
            echo "  " . "\033[32m✔\033[0m" . " {$method} (" . $testCase->assertionsCount . " assertions)\n";
        } catch (Throwable $e) {
            $failedTests[] = [
                'class' => $className,
                'method' => $method,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            echo "  " . "\033[31m✘\033[0m" . " {$method}\n";
            color_log("    Error: " . $e->getMessage(), 'red');
        }
    }
}

$elapsedTime = round(microtime(true) - $startTime, 4);

color_log("\n=======================================================", 'cyan');
if (empty($failedTests)) {
    color_log("SUCCESS: All tests passed!", 'green');
    color_log("Tests: {$testsCount}, Assertions: {$assertionsCount}, Time: {$elapsedTime}s", 'green');
    exit(0);
} else {
    color_log("FAILURE: " . count($failedTests) . " tests failed.", 'red');
    color_log("Tests: {$testsCount}, Assertions: {$assertionsCount}, Time: {$elapsedTime}s\n", 'red');
    
    color_log("Failed Tests Summary:", 'yellow');
    foreach ($failedTests as $index => $fail) {
        $num = $index + 1;
        color_log("{$num}) {$fail['class']}::{$fail['method']}", 'red');
        color_log("   " . $fail['message'], 'white');
        echo "\n";
    }
    exit(1);
}
