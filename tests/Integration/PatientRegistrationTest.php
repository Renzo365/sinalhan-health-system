<?php
// tests/Integration/PatientRegistrationTest.php

class PatientRegistrationTest extends TestCase {
    private $pdo;

    public function setUp() {
        $this->pdo = Database::getInstance()->getConnection();
        // Start transaction to isolate database modifications
        $this->pdo->beginTransaction();
        
        // Setup default session and post variables
        $_SESSION = [
            'user_id' => 1, // Pre-seeded Admin user ID
            'username' => 'admin',
            'role' => 'admin',
            'csrf_token' => 'test_csrf_token_123'
        ];
        
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    public function tearDown() {
        // Rollback transaction to clean database after test
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        $_SESSION = [];
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testSuccessfulRegistration() {
        $_POST = [
            'csrf_token' => 'test_csrf_token_123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '1995-05-15',
            'sex' => 'Male',
            'civil_status' => 'Single',
            'purok' => 'Purok 2',
            'contact_number' => '09123456789',
            'address' => '123 Test St Barangay Sinalhan',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_number' => '09987654321',
            'medical_history' => 'Hypertension',
            'allergies' => 'Penicillin',
            'allow_duplicate' => '0'
        ];

        // Include process file
        ob_start();
        require __DIR__ . '/../../patients/register_process.php';
        ob_end_clean();

        // Check if registration succeeded
        $this->assertNotNull($_SESSION['alert'] ?? null, "Alert session should be populated.");
        $this->assertEquals('success', $_SESSION['alert']['type'], "Alert type should be success. Msg: " . ($_SESSION['alert']['message'] ?? ''));

        // Query database to verify patient record was inserted
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE first_name = ? AND last_name = ?");
        $stmt->execute(['John', 'Doe']);
        $patient = $stmt->fetch();

        $this->assertNotEmpty($patient, "Patient record should exist in the database.");
        $this->assertEquals('Male', $patient['sex']);
        $this->assertEquals('Purok 2', $patient['purok']);
        
        // Decrypt medical history and verify
        require_once __DIR__ . '/../../includes/encryption.php';
        $this->assertEquals('Hypertension', decrypt_data($patient['medical_history']), "Medical history should be stored encrypted.");
    }

    public function testFailedRegistrationInvalidName() {
        $_POST = [
            'csrf_token' => 'test_csrf_token_123',
            'first_name' => 'John123', // Numbers not allowed in name
            'last_name' => 'Doe',
            'birthdate' => '1995-05-15',
            'sex' => 'Male',
            'purok' => 'Purok 2',
            'allow_duplicate' => '0'
        ];

        ob_start();
        require __DIR__ . '/../../patients/register_process.php';
        ob_end_clean();

        $this->assertNotNull($_SESSION['alert'] ?? null, "Alert session should be populated.");
        $this->assertEquals('error', $_SESSION['alert']['type'], "Alert type should be error for invalid name.");
        $this->assertTrue(strpos($_SESSION['alert']['message'], 'name can only contain letters') !== false);
    }

    public function testFailedRegistrationFutureBirthdate() {
        $_POST = [
            'csrf_token' => 'test_csrf_token_123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => date('Y-m-d', strtotime('+1 day')), // Future date
            'sex' => 'Male',
            'purok' => 'Purok 2',
            'allow_duplicate' => '0'
        ];

        ob_start();
        require __DIR__ . '/../../patients/register_process.php';
        ob_end_clean();

        $this->assertNotNull($_SESSION['alert'] ?? null, "Alert session should be populated.");
        $this->assertEquals('error', $_SESSION['alert']['type'], "Alert type should be error for future birthdate.");
        $this->assertTrue(strpos($_SESSION['alert']['message'], 'Birthdate cannot be a future date') !== false);
    }

    public function testDuplicateRegistrationPrevented() {
        // Pre-insert a patient with created_at set to 1 minute ago to bypass the 30s idempotency guard
        $insertStmt = $this->pdo->prepare("
            INSERT INTO patients (first_name, last_name, birthdate, sex, purok, registered_by, is_archived, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, DATE_SUB(NOW(), INTERVAL 1 MINUTE))
        ");
        $insertStmt->execute(['Duplicate', 'User', '1990-10-10', 'Female', 'Purok 1', 1]);

        // Attempt to register same patient
        $_POST = [
            'csrf_token' => 'test_csrf_token_123',
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'birthdate' => '1990-10-10',
            'sex' => 'Female',
            'purok' => 'Purok 1',
            'allow_duplicate' => '0'
        ];

        ob_start();
        require __DIR__ . '/../../patients/register_process.php';
        ob_end_clean();

        $this->assertNotNull($_SESSION['alert'] ?? null, "Alert session should be populated.");
        $this->assertEquals('error', $_SESSION['alert']['type'], "Alert type should be error due to duplicate registration.");
        $this->assertTrue(strpos($_SESSION['alert']['message'], 'is already registered') !== false);
    }
}
