<?php
/**
 * Security Headers & HTTPS Enforcement
 * Include this at the top of every PHP file
 */

// Enforce HTTPS in production
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'];
} else {
    $proto = $_SERVER['REQUEST_SCHEME'] ?? 'http';
}

if ($proto !== 'https' && ($_ENV['ENVIRONMENT'] ?? 'development') === 'production') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit();
}

// Security Headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';");

// Prevent sensitive data leakage
header('X-Powered-By: ');
header_remove('X-Powered-By');

// Set secure session cookie
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

?>
