<?php
// config/database.php

/**
 * Database Connection Wrapper (Singleton Pattern)
 * 
 * Purpose:
 * Provides a single, centralized database connection instance throughout the application.
 * Using a Singleton prevents creating multiple database connections on a single page request,
 * which optimizes system resources and ensures query execution consistency.
 */
class Database {
    // Stores the single instance of this class
    private static $instance = null;
    
    // Stores the active PDO database connection object
    private $conn;

    // Database connection details
    private $host = 'localhost';
    private $dbname = 'bhc_sinalhan_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    private function __construct() {
        // Load database connection settings dynamically from environment variables
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbname = getenv('DB_NAME') ?: 'bhc_sinalhan_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

        try {
            // Data Source Name (DSN) defines database type, host, name, and charset
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            // Connection configuration options for enhanced security and error reporting
            $options = [
                // Throws PDOExceptions instead of failing silently (critical for debugging and try-catch handling)
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Automatically fetches query rows as associative arrays (e.g. ['column_name' => 'value'])
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Disables emulated prepared statements to ensure native MySQL parameter binding (mitigates SQL injection)
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            // Initialize PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log full error details securely on the server's logs directory
            error_log("Database connection failed: " . $e->getMessage());
            // Display a user-friendly error message without exposing database credentials
            die("Database connection error. Please verify that MySQL is running and configuration is correct.");
        }
    }

    /**
     * Get instance of the Database
     * 
     * Globally accessible method to obtain the singleton instance.
     * Instantiates the Database class on the first call, then returns the existing instance on subsequent calls.
     * 
     * @return Database The singleton database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the active PDO connection
     * 
     * Used by models, helpers, or page-level scripts to fetch the database handle for querying.
     * 
     * @return PDO The active PDO connection handle
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Prevent Cloning
     * 
     * Restricting clone operation ensures that the database connection instance cannot be duplicated.
     */
    private function __clone() {}

    /**
     * Prevent Unserialization
     * 
     * Restricting wakeup method prevents restoring a duplicate database class instance from serialized data.
     */
    public function __wakeup() {}
}

