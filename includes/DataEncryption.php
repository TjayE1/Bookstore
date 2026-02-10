<?php

/**
 * Data Encryption for Sensitive Information
 */

class DataEncryption {
    private static $algorithm = 'AES-256-CBC';
    private static $key = null;
    
    /**
     * Get encryption key from environment
     */
    private static function getKey() {
        if (self::$key === null) {
            // In production, load from secure environment variable
            self::$key = $_ENV['ENCRYPTION_KEY'] ?? 'your-secret-key-min-32-chars-long!';
            
            // Ensure key is 32 bytes for AES-256
            if (strlen(self::$key) < 32) {
                self::$key = str_pad(self::$key, 32, '0');
            } else {
                self::$key = substr(self::$key, 0, 32);
            }
        }
        return self::$key;
    }
    
    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$algorithm));
        $encrypted = openssl_encrypt($data, self::$algorithm, self::getKey(), 0, $iv);
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed');
        }
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($data) {
        $data = base64_decode($data, true);
        if ($data === false) {
            throw new Exception('Invalid encrypted data');
        }
        
        $iv_length = openssl_cipher_iv_length(self::$algorithm);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        $decrypted = openssl_decrypt($encrypted, self::$algorithm, self::getKey(), 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception('Decryption failed');
        }
        
        return $decrypted;
    }
}

?>
