<?php
// config/env.php

/**
 * Lightweight Environment File (.env) Loader
 * 
 * Purpose:
 * Loads environment variables from the root .env file and registers them globally.
 * This isolates secret configuration keys from the codebase to prevent key leakages.
 */
function load_env() {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments starting with '#'
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Split by the first '=' character
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Strip surrounding quotes if present
            $value = trim($value, '"\'');

            // Set environment variables if not already defined
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Automatically invoke environment loader
load_env();
