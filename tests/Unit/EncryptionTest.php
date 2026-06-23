<?php
// tests/Unit/EncryptionTest.php

require_once __DIR__ . '/../../includes/encryption.php';

class EncryptionTest extends TestCase {
    public function testEncryptionCycle() {
        $plaintext = "Hello Barangay Sinalhan Health Center Patient Records!";
        $encrypted = encrypt_data($plaintext);
        
        $this->assertNotEmpty($encrypted, "Encrypted output should not be empty.");
        $this->assertTrue(strpos($encrypted, 'enc::') === 0, "Encrypted output must start with 'enc::'.");
        
        $decrypted = decrypt_data($encrypted);
        $this->assertEquals($plaintext, $decrypted, "Decrypted data must match original plaintext.");
    }

    public function testUnencryptedFallback() {
        $plaintext = "Raw unencrypted text";
        $decrypted = decrypt_data($plaintext);
        
        $this->assertEquals($plaintext, $decrypted, "Decryption must return the raw data if it is not encrypted.");
    }

    public function testLegacyFormatSupport() {
        // Old 3-part format: enc::[iv]::[ciphertext]
        // Let's generate a valid 3-part ciphertext manually
        $plaintext = "Legacy Patient History Data";
        
        $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_sinalhan_health_center_key_32_bytes_long_123';
        $key = substr(hash('sha256', $key, true), 0, 32);
        
        $cipher = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $legacyCipher = 'enc::' . base64_encode($iv) . '::' . base64_encode($ciphertext_raw);
        
        $decrypted = decrypt_data($legacyCipher);
        $this->assertEquals($plaintext, $decrypted, "Decryption must successfully decrypt legacy 3-part formats.");
    }

    public function testHmacTamperingRejection() {
        $plaintext = "Sensitive Health Record";
        $encrypted = encrypt_data($plaintext);
        
        // Parts: enc :: [base64_iv] :: [base64_ciphertext] :: [base64_hmac]
        $parts = explode('::', $encrypted);
        $this->assertEquals(4, count($parts), "Ciphertext should contain 4 parts.");
        
        // Tamper with the ciphertext (parts[2])
        $ciphertext_raw = base64_decode($parts[2]);
        $ciphertext_raw[0] = chr(ord($ciphertext_raw[0]) ^ 0xFF); // Flip bits in first character
        $parts[2] = base64_encode($ciphertext_raw);
        
        $tamperedCipher = implode('::', $parts);
        $decrypted = decrypt_data($tamperedCipher);
        
        $this->assertEquals($tamperedCipher, $decrypted, "Decryption of tampered data must fail HMAC verification and return the raw input to prevent processing corrupted data.");
    }
}
