<?php
// includes/encryption.php

/**
 * Cryptographic Encryption Library
 * 
 * Purpose:
 * Provides AES-256-CBC column-level encryption/decryption for sensitive patient medical data, 
 * meeting capstone security expectations and HIPAA compliance.
 * Handles cipher padding, unique initialization vector (IV) generation, and legacy raw data fallbacks.
 */

/**
 * Encrypts cleartext using AES-256-CBC with HMAC.
 * Output format: enc::[base64_iv]::[base64_ciphertext]::[base64_hmac]
 * 
 * @param string|null $plaintext Raw readable text to encrypt
 * @return string The formatted encrypted string, or the raw input if empty/failed
 */
function encrypt_data($plaintext) {
    // Return early if there is no data to encrypt
    if (empty($plaintext)) {
        return $plaintext;
    }

    // Fetch the encryption key defined in config/app.php, or use a default key
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_sinalhan_health_center_key_32_bytes_long_123';
    
    // Hash key using SHA-256 and slice to 32 bytes (AES-256 requires exactly a 32-byte key)
    $key = substr(hash('sha256', $key, true), 0, 32);

    $cipher = 'aes-256-cbc';
    
    // Determine the required length of the initialization vector (IV) for CBC mode (16 bytes)
    $ivlen = openssl_cipher_iv_length($cipher);
    
    // Generate a secure pseudo-random initialization vector (IV) to prevent identical plaintexts producing identical ciphers
    $iv = openssl_random_pseudo_bytes($ivlen);

    // Encrypt the data securely
    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext_raw === false) {
        // Fallback: if encryption fails for any reason, return the plaintext so data is not lost
        return $plaintext; 
    }

    // Generate HMAC-SHA256 signature to verify integrity (prevent padding/bit-flipping attacks)
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);

    // Return the cipher tagged with 'enc::' and delimited with '::' for easy verification and decoding
    return 'enc::' . base64_encode($iv) . '::' . base64_encode($ciphertext_raw) . '::' . base64_encode($hmac);
}

/**
 * Decrypts a formatted ciphertext string.
 * Supports transparent legacy fallback: if the string is not encrypted,
 * or is in the old 3-part format, it will decrypt without HMAC verification.
 * If the 4-part format is detected, it verifies the HMAC signature first.
 * 
 * @param string|null $ciphertext The encrypted string, or raw legacy string
 * @return string Decrypted plaintext, or raw input on failure
 */
function decrypt_data($ciphertext) {
    // Return early if empty
    if (empty($ciphertext)) {
        return $ciphertext;
    }

    // Check if the data is encrypted in our specific tag format
    if (strpos($ciphertext, 'enc::') !== 0) {
        // Transparent legacy fallback: return raw data as-is if it's unencrypted
        return $ciphertext; 
    }

    // Parse the format: enc::[iv]::[ciphertext] or enc::[iv]::[ciphertext]::[hmac]
    $parts = explode('::', $ciphertext);
    $count = count($parts);

    if ($count !== 3 && $count !== 4) {
        // If the format is invalid or corrupted, return the raw input to prevent data loss
        return $ciphertext; 
    }

    // Decode the base64 encoded IV and raw ciphertext
    $iv = base64_decode($parts[1]);
    $ciphertext_raw = base64_decode($parts[2]);

    // Reconstruct the 32-byte hash key
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_sinalhan_health_center_key_32_bytes_long_123';
    $key = substr(hash('sha256', $key, true), 0, 32);

    $cipher = 'aes-256-cbc';

    // If 4-part format is detected, verify the HMAC signature first (mitigates padding oracle / manipulation)
    if ($count === 4) {
        $expected_hmac = base64_decode($parts[3]);
        $calculated_hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);

        if (!hash_equals($calculated_hmac, $expected_hmac)) {
            error_log("Decryption failed: HMAC signature verification failed (data manipulation suspected).");
            return $ciphertext; // Fallback to raw ciphertext to prevent processing manipulated data
        }
    }
    
    // Decrypt using OpenSSL
    $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    if ($plaintext === false) {
        // Decryption failed (e.g. key has changed), fallback to raw ciphertext to prevent crash
        return $ciphertext; 
    }

    return $plaintext;
}

