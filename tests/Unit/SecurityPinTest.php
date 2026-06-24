<?php
// tests/Unit/SecurityPinTest.php

class SecurityPinTest extends TestCase {
    
    /**
     * Test valid and invalid PIN patterns
     */
    public function testPinPatternValidation() {
        $validPin = "123456";
        $invalidPinTooShort = "123";
        $invalidPinTooLong = "1234567";
        $invalidPinNonNumeric = "123a56";
        $invalidPinEmpty = "";

        $pattern = '/^\d{6}$/';

        $this->assertTrue((bool)preg_match($pattern, $validPin), "PIN '123456' must be valid.");
        $this->assertFalse((bool)preg_match($pattern, $invalidPinTooShort), "PIN '123' must be invalid (too short).");
        $this->assertFalse((bool)preg_match($pattern, $invalidPinTooLong), "PIN '1234567' must be invalid (too long).");
        $this->assertFalse((bool)preg_match($pattern, $invalidPinNonNumeric), "PIN '123a56' must be invalid (non-numeric).");
        $this->assertFalse((bool)preg_match($pattern, $invalidPinEmpty), "Empty PIN must be invalid.");
    }

    /**
     * Test secure hashing and verification cycle for security PINs
     */
    public function testPinHashAndVerify() {
        $pin = "987654";
        $wrongPin = "987655";

        // Hash the PIN
        $hashedPin = password_hash($pin, PASSWORD_BCRYPT);
        
        $this->assertNotEmpty($hashedPin, "Hashed PIN should not be empty.");
        $this->assertTrue($pin !== $hashedPin, "Hashed PIN must not match raw PIN string.");
        $this->assertTrue(password_verify($pin, $hashedPin), "password_verify must succeed with the correct PIN.");
        $this->assertFalse(password_verify($wrongPin, $hashedPin), "password_verify must fail with an incorrect PIN.");
    }
}
