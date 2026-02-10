<?php
/**
 * Environment Configuration Helper
 * Loads and manages environment variables
 */

class EnvironmentConfig {
    private static $loaded = false;
    private static $config = [];

    /**
     * Load environment variables from .env file
     */
    public static function load($filePath = null) {
        if (self::$loaded) return;
        
        $filePath = $filePath ?: dirname(__DIR__) . '/.env';
        
        if (!file_exists($filePath)) {
            // Create default .env if missing
            self::createDefaultEnv($filePath);
        }
        
        if (file_exists($filePath)) {
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && $line[0] !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
                        $value = substr($value, 1, -1);
                    }
                    
                    self::$config[$key] = $value;
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
    }

    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? $_ENV[$key] ?? $default;
    }

    /**
     * Create default .env file
     */
    private static function createDefaultEnv($filePath) {
        $template = <<<'ENV'
# === DATABASE CONFIGURATION ===
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=readers_haven

# === BANK TRANSFER CONFIGURATION ===
BANK_NAME=Your Bank Name
ACCOUNT_NAME=Your Business Name
ACCOUNT_NUMBER=1234567890
BANK_CURRENCY=UGX
SWIFT_CODE=
IBAN=

# === MOBILE MONEY CONFIGURATION ===
MTN_NUMBER=+256700000000
AIRTEL_NUMBER=+256700000001

# === PAYPAL CONFIGURATION ===
PAYPAL_ENABLED=false
PAYPAL_CLIENT_ID=
PAYPAL_SECRET=
PAYPAL_MODE=sandbox

# === STRIPE CONFIGURATION ===
STRIPE_ENABLED=false
STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=

# === EMAIL CONFIGURATION ===
MAIL_HOST=
MAIL_PORT=587
MAIL_USER=
MAIL_PASSWORD=
MAIL_FROM=

# === SECURITY ===
APP_KEY=
CORS_ORIGINS=http://localhost,http://localhost:3000

ENV;
        
        file_put_contents($filePath, $template);
        chmod($filePath, 0600); // Restrict access
    }

    /**
     * Validate configuration
     */
    public static function validate() {
        self::load();
        
        $errors = [];
        
        // Database check
        if (!self::get('DB_HOST')) $errors[] = 'DB_HOST not configured';
        if (!self::get('DB_NAME')) $errors[] = 'DB_NAME not configured';
        
        // Payment check
        if (!self::get('BANK_ACCOUNT_NUMBER') && 
            !self::get('MTN_NUMBER') && 
            !self::get('PAYPAL_ENABLED') && 
            !self::get('STRIPE_ENABLED')) {
            $errors[] = 'No payment method configured. Add at least one payment method.';
        }
        
        return $errors;
    }

    /**
     * Get all configuration (safe - without sensitive keys)
     */
    public static function getAll() {
        self::load();
        
        $safe = [];
        $sensitive = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN'];
        
        foreach (self::$config as $key => $value) {
            $isSensitive = false;
            foreach ($sensitive as $s) {
                if (stripos($key, $s) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            $safe[$key] = $isSensitive ? '***' : $value;
        }
        
        return $safe;
    }
}

// Auto-load on include
EnvironmentConfig::load();
