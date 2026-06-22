<?php
// scratch/test_encryption_compat.php

require_once __DIR__ . '/../includes/encryption.php';

echo "--- START ENCRYPTION COMPATIBILITY TEST ---\n\n";

// Define ENCRYPTION_KEY if not defined
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', 'test_secret_key_12345_67890_abcde');
}

$testString = "This is a sensitive patient diagnosis: Chronic hypertension.";
echo "Original plaintext: '$testString'\n\n";

// Test 1: New format (HMAC) encryption and decryption
echo "1. Testing New Format (AES-256-CBC + HMAC-SHA256)...\n";
$encryptedNew = encrypt_data($testString);
echo "   Encrypted (New): $encryptedNew\n";

$decryptedNew = decrypt_data($encryptedNew);
echo "   Decrypted (New): '$decryptedNew'\n";

if ($decryptedNew === $testString) {
    echo "   [SUCCESS] New format matches original plaintext.\n\n";
} else {
    echo "   [FAIL] New format decryption failed!\n\n";
}

// Test 2: Legacy format (no HMAC) decryption compatibility
echo "2. Testing Legacy Format (3-part) Decryption Compatibility...\n";
// Let's manually encrypt in the old 3-part format (simulate legacy database entry)
$key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
$iv = openssl_random_pseudo_bytes(16);
$ciphertext_raw = openssl_encrypt($testString, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
$encryptedOld = 'enc::' . base64_encode($iv) . '::' . base64_encode($ciphertext_raw);
echo "   Mocked Legacy DB Cipher: $encryptedOld\n";

$decryptedOld = decrypt_data($encryptedOld);
echo "   Decrypted Legacy: '$decryptedOld'\n";

if ($decryptedOld === $testString) {
    echo "   [SUCCESS] Decrypted legacy format matches original plaintext.\n\n";
} else {
    echo "   [FAIL] Legacy format decryption failed!\n\n";
}

// Test 3: Tampered ciphertext protection
echo "3. Testing Tampered Ciphertext Protection...\n";
$parts = explode('::', $encryptedNew);
// Flip a bit in the ciphertext base64 representation
$parts[2][0] = ($parts[2][0] === 'A') ? 'B' : 'A';
$tamperedCipher = implode('::', $parts);
echo "   Tampered Cipher: $tamperedCipher\n";

$decryptedTampered = decrypt_data($tamperedCipher);
echo "   Decrypted result: '$decryptedTampered'\n";

if ($decryptedTampered === $tamperedCipher) {
    echo "   [SUCCESS] Tampered cipher was rejected and fallback returned the cipher safely.\n\n";
} else {
    echo "   [FAIL] Tampered cipher decrypted successfully (vulnerable to padding manipulation)!\n\n";
}

echo "--- COMPATIBILITY TEST COMPLETE ---\n";
