<?php
/**
 * Production Configuration
 * Load environment variables for production deployment
 */

// Load environment variables from .env file if it exists
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
    
    return true;
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

// Set production error reporting
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
    
    // Set secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', getenv('SESSION_SECURE') === 'true' ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
}
?> 