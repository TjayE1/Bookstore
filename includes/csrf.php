<?php
/**
 * CSRF Token Management
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate JWT token (for API authentication)
 * Note: For production, use a proper JWT library like firebase/php-jwt
 */
function generateJWT($payload, $secret, $expiresIn = 3600) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    
    $issuedAt = time();
    $expire = $issuedAt + $expiresIn;
    
    $payload['iat'] = $issuedAt;
    $payload['exp'] = $expire;
    
    $payload = base64_encode(json_encode($payload));
    
    $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
    $signature = base64_encode($signature);
    
    return "$header.$payload.$signature";
}

/**
 * Verify JWT token
 */
function verifyJWT($token, $secret) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $expectedSignature = hash_hmac('sha256', "$header.$payload", $secret, true);
    $expectedSignature = base64_encode($expectedSignature);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }
    
    // Decode and verify expiration
    $decoded = json_decode(base64_decode($payload), true);
    
    if (isset($decoded['exp']) && $decoded['exp'] < time()) {
        return false; // Token expired
    }
    
    return $decoded;
}

?>
