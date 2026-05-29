<?php
// includes/encryption.php

/**
 * Encrypts data using AES-256-CBC.
 * Output format: enc::[base64_iv]::[base64_ciphertext]
 */
function encrypt_data($plaintext) {
    if (empty($plaintext)) {
        return $plaintext;
    }

    // Load key from config
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_sinalhan_health_center_key_32_bytes_long_123';
    
    // Ensure key is exactly 32 bytes (for AES-256)
    $key = substr(hash('sha256', $key, true), 0, 32);

    $cipher = 'aes-256-cbc';
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);

    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext_raw === false) {
        return $plaintext; // Fallback to raw if encryption fails
    }

    return 'enc::' . base64_encode($iv) . '::' . base64_encode($ciphertext_raw);
}

/**
 * Decrypts data. If the string is not encrypted (does not start with 'enc::'),
 * or if decryption fails, it returns the raw input string.
 */
function decrypt_data($ciphertext) {
    if (empty($ciphertext)) {
        return $ciphertext;
    }

    // Check if it is encrypted in our format
    if (strpos($ciphertext, 'enc::') !== 0) {
        return $ciphertext; // Return raw data (fallback for legacy records)
    }

    // Parse format: enc::[iv]::[ciphertext]
    $parts = explode('::', $ciphertext);
    if (count($parts) !== 3) {
        return $ciphertext; // Invalid format, return raw
    }

    $iv = base64_decode($parts[1]);
    $ciphertext_raw = base64_decode($parts[2]);

    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_sinalhan_health_center_key_32_bytes_long_123';
    $key = substr(hash('sha256', $key, true), 0, 32);

    $cipher = 'aes-256-cbc';
    $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    if ($plaintext === false) {
        return $ciphertext; // Decryption failed, return raw
    }

    return $plaintext;
}
