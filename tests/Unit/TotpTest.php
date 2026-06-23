<?php
// tests/Unit/TotpTest.php

require_once __DIR__ . '/../../includes/totp.php';

class TotpTest extends TestCase {
    public function testSecretGeneration() {
        $secret = TOTP::generateSecret();
        
        $this->assertNotEmpty($secret, "Generated secret should not be empty.");
        $this->assertEquals(16, strlen($secret), "Generated secret must be exactly 16 characters long.");
        
        // Assert base32 characters only
        $this->assertTrue((bool)preg_match('/^[A-Z2-7]+$/', $secret), "Secret must only contain base32 characters (A-Z, 2-7).");
    }

    public function testGetCodeFormat() {
        $secret = "ORSXG5BRGIZTINJWG4======"; // Dummy Base32 secret (16-char equivalent base32)
        $secret = substr($secret, 0, 16);
        
        $code = TOTP::getCode($secret);
        $this->assertNotEmpty($code, "Calculated TOTP code should not be empty.");
        $this->assertEquals(6, strlen($code), "Calculated TOTP code must be exactly 6 characters long.");
        $this->assertTrue((bool)preg_match('/^\d{6}$/', $code), "TOTP code must be numeric only.");
    }

    public function testVerifyCodeWithDrift() {
        $secret = TOTP::generateSecret();
        
        // Test exact current time code
        $code = TOTP::getCode($secret);
        $this->assertTrue(TOTP::verifyCode($secret, $code), "Code should verify successfully with default parameters.");
        
        // Test code from previous timestep (-30s)
        $prevTimeStep = floor(time() / 30) - 1;
        $prevCode = TOTP::getCode($secret, $prevTimeStep);
        $this->assertTrue(TOTP::verifyCode($secret, $prevCode, 1), "Previous timestep code should verify successfully with a discrepancy window of 1.");
        
        // Test invalid code
        $invalidCode = "999999";
        if ($invalidCode !== $code && $invalidCode !== $prevCode) {
            $this->assertFalse(TOTP::verifyCode($secret, $invalidCode), "An arbitrary invalid code should not verify.");
        }
    }
}
