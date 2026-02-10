<?php
/**
 * Security Configuration & Utility Functions
 */

// Define environment
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');

// Rate limiting configuration
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// CORS whitelist - only allow your domain
$ALLOWED_ORIGINS = [
    'http://localhost:8080',
    'http://localhost:3000',
    // Add your production domain here
    // 'https://yourdomain.com'
];

/**
 * Validate CORS origin
 */
function validateCORSOrigin() {
    global $ALLOWED_ORIGINS;
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $ALLOWED_ORIGINS)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        return true;
    }
    return false;
}

/**
 * Input validation utilities
 */
class Validator {
    /**
     * Validate email
     */
    public static function email($email) {
        $email = trim(strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (strlen($email) > 255) {
            return false;
        }
        return $email;
    }
    
    /**
     * Validate name (letters, spaces, hyphens, apostrophes)
     */
    public static function name($name, $minLength = 2, $maxLength = 100) {
        $name = trim($name);
        if (strlen($name) < $minLength || strlen($name) > $maxLength) {
            return false;
        }
        if (!preg_match("/^[a-zA-Z\s'-]+$/", $name)) {
            return false;
        }
        return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate phone number
     */
    public static function phone($phone) {
        $phone = preg_replace('/[^0-9+\-\(\)\s]/', '', $phone);
        if (strlen($phone) < 7 || strlen($phone) > 20) {
            return false;
        }
        return $phone;
    }
    
    /**
     * Validate date (YYYY-MM-DD)
     */
    public static function date($date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $parts = explode('-', $date);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            return false;
        }
        // Ensure date is not in the past
        if (strtotime($date) < strtotime('today')) {
            return false;
        }
        return $date;
    }
    
    /**
     * Validate time (HH:MM)
     */
    public static function time($time) {
        if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $time)) {
            return false;
        }
        return $time;
    }
    
    /**
     * Validate message/text (limit length and sanitize)
     */
    public static function text($text, $maxLength = 1000, $minLength = 0) {
        $text = trim($text);
        if (strlen($text) < $minLength || strlen($text) > $maxLength) {
            return false;
        }
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize text (alias for text method, shorter version)
     */
    public static function sanitizeText($text, $maxLength = 1000) {
        $text = trim($text);
        if (strlen($text) > $maxLength) {
            return false;
        }
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate price/amount
     */
    public static function price($price, $min = 0, $max = 999999.99) {
        $price = (float)$price;
        if ($price < $min || $price > $max) {
            return false;
        }
        return round($price, 2);
    }
    
    /**
     * Validate integer within range
     */
    public static function integer($value, $min = PHP_INT_MIN, $max = PHP_INT_MAX) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            return false;
        }
        return $value;
    }
}

/**
 * Rate limiting
 */
class RateLimiter {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    /**
     * Check if request should be rate limited
     * Gracefully falls back if APCu is not available
     */
    public function isLimited($identifier, $limit = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
        // Check if APCu is available
        if (!extension_loaded('apcu')) {
            // Fallback: use session-based rate limiting
            return $this->isLimitedSession($identifier, $limit, $window);
        }
        
        $key = 'rate_limit_' . md5($identifier);
        $count = apcu_fetch($key);
        
        if ($count === false) {
            apcu_store($key, 1, $window);
            return false;
        }
        
        if ($count >= $limit) {
            return true;
        }
        
        apcu_inc($key);
        return false;
    }
    
    /**
     * Session-based fallback when APCu is not available
     */
    private function isLimitedSession($identifier, $limit, $window) {
        session_start();
        
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'time' => $now];
            return false;
        }
        
        $data = $_SESSION[$key];
        
        // If window has expired, reset
        if ($now - $data['time'] > $window) {
            $_SESSION[$key] = ['count' => 1, 'time' => $now];
            return false;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $limit) {
            return true;
        }
        
        $_SESSION[$key]['count']++;
        return false;
    }
}

/**
 * Sanitization utilities
 */
class Sanitizer {
    /**
     * Sanitize output (escape for HTML)
     */
    public static function output($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize filename
     */
    public static function filename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }
}

/**
 * Secure logging
 */
class SecurityLogger {
    private $logFile;
    
    public function __construct($filename = 'security.log') {
        $this->logFile = __DIR__ . '/../logs/' . $filename;
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0700, true);
        }
    }
    
    /**
     * Log security event
     */
    public function log($event, $details = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $user = $_SESSION['admin_id'] ?? 'GUEST';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'event' => $event,
            'ip' => $ip,
            'user' => $user,
            'details' => $details
        ];
        
        error_log(json_encode($logEntry) . PHP_EOL, 3, $this->logFile);
    }
}

?>
