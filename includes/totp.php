<?php
// includes/totp.php

class TOTP {
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Helper to decode base32 strings
     */
    private static function base32Decode($base32) {
        $base32 = strtoupper($base32);
        if (!preg_match('/^[A-Z2-7]+$/', $base32)) {
            return false;
        }

        $data = '';
        $buf = 0;
        $bufLen = 0;

        for ($i = 0; $i < strlen($base32); $i++) {
            $val = strpos(self::$base32Chars, $base32[$i]);
            $buf = ($buf << 5) | $val;
            $bufLen += 5;

            if ($bufLen >= 8) {
                $bufLen -= 8;
                $data .= chr(($buf >> $bufLen) & 0xFF);
            }
        }

        return $data;
    }

    /**
     * Generates a 16-character random base32 secret key.
     */
    public static function generateSecret() {
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= self::$base32Chars[rand(0, 31)];
        }
        return $secret;
    }

    /**
     * Calculates the 6-digit TOTP code for a secret at a specific time step.
     */
    public static function getCode($secret, $timeStep = null) {
        if ($timeStep === null) {
            $timeStep = floor(time() / 30);
        }

        $secretKey = self::base32Decode($secret);
        if ($secretKey === false) {
            return false;
        }

        // Pack time step into 64-bit binary string
        $timeBin = pack('N*', 0) . pack('N*', $timeStep);

        // Compute HMAC-SHA1
        $hash = hash_hmac('sha1', $timeBin, $secretKey, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0x0F;
        $halfCode = unpack('N', substr($hash, $offset, 4));
        $value = $halfCode[1] & 0x7FFFFFFF;

        $code = $value % 1000000;
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifies if code is correct within window of drift discrepancy (+/- 30s steps)
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeStep = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculated = self::getCode($secret, $currentTimeStep + $i);
            if ($calculated !== false && hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates the standard otpauth:// URL for authenticator apps.
     */
    public static function getQRUrl($username, $secret, $issuer = 'Sinalhan HC') {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($username) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }
}
