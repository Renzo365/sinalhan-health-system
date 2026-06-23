<?php
// tests/Unit/DatabaseTest.php

class DatabaseTest extends TestCase {
    public function testSingletonInstance() {
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();
        
        $this->assertNotNull($db1, "Database instance should not be null.");
        $this->assertEquals($db1, $db2, "Database instances should refer to the exact same singleton object.");
    }

    public function testPdoConnection() {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $this->assertNotNull($conn, "PDO connection handle should not be null.");
        $this->assertTrue($conn instanceof PDO, "Connection handle must be an instance of PDO.");
    }

    public function testUnserializeException() {
        $db = Database::getInstance();
        
        $this->assertException(function() use ($db) {
            $serialized = serialize($db);
            unserialize($serialized);
        }, Exception::class, "Unserializing singleton Database instance should throw an Exception.");
    }
}
