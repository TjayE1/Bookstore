# Security Implementation Guide

## Overview
This document outlines all security improvements implemented for the seee project to address the 5 critical security issues.

---

## 1. ✅ SERVER-SIDE VALIDATION

### Implementation
- **File**: `config/security.php` - Contains `Validator` class with strict validation methods
- **Coverage**:
  - Email validation with RFC compliance
  - Name validation (letters, spaces, hyphens, apostrophes only)
  - Phone number validation with format checking
  - Date validation (must be future date, valid calendar date)
  - Time validation (HH:MM format, business hours)
  - Text validation with length limits
  - Price/amount validation with range checks
  - Integer validation with min/max bounds

### Usage in APIs
```php
// Example: Validate email
$email = Validator::email($data['email']);
if ($email === false) {
    throw new Exception('Invalid email');
}

// Example: Validate name
$name = Validator::name($data['name']);
if ($name === false) {
    throw new Exception('Invalid name format');
}
```

### Updated Files
- `api/create-booking.php` - Full server-side validation implemented
- `api/create-order.php` - Comprehensive validation with price verification

---

## 2. ✅ SQL INJECTION PREVENTION

### Implementation
All database queries use **prepared statements with parameterized queries**.

### Database Configuration
- **File**: `config/database.php`
- **Method**: MySQLi prepared statements with type binding
- **Type Binding**: Automatic type detection (i, d, s, b)

### Example
```php
// Secure: Uses prepared statement
$query = "SELECT id FROM bookings WHERE customer_email = ? AND booking_date = ?";
$result = getRow($query, [$email, $date]);

// Never passes raw user input to SQL
```

### Defense Mechanisms
1. All user inputs bound as parameters
2. No string concatenation in queries
3. Type casting enforced
4. Query preparation separates code from data

---

## 3. ✅ CUSTOMER DATA PROTECTION

### Encryption
- **File**: `includes/DataEncryption.php`
- **Method**: AES-256-CBC encryption
- **Key Management**: Load from environment variable `ENCRYPTION_KEY`

### Implementation
```php
// Encrypt sensitive data
$encrypted = DataEncryption::encrypt($sensitive_data);

// Decrypt when needed
$decrypted = DataEncryption::decrypt($encrypted);
```

### Security Headers
- **File**: `includes/security-headers.php`
- **Headers Implemented**:
  - `Strict-Transport-Security` - Force HTTPS
  - `X-Frame-Options: DENY` - Prevent clickjacking
  - `X-Content-Type-Options: nosniff` - Prevent MIME sniffing
  - `Content-Security-Policy` - Restrict resource loading
  - `X-XSS-Protection` - Enable XSS protection
  - `Permissions-Policy` - Disable dangerous APIs

### Session Security
```php
// Secure session configuration (includes/security-headers.php)
ini_set('session.cookie_httponly', 1);     // JS cannot access cookie
ini_set('session.cookie_secure', 1);       // HTTPS only
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
```

### Sensitive Data Removal
- Removes `Server`, `X-Powered-By`, `X-AspNet-Version` headers
- Disables debug mode in production
- Logs errors without exposing details to users

---

## 4. ✅ HTTPS ENFORCEMENT

### Apache Configuration
- **File**: `.htaccess`
- **Implementation**:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```
(Uncomment in production)

### HSTS Header
```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```
- Forces HTTPS for 1 year
- Includes subdomains
- Allows HSTS preload list registration

### Production Checklist
1. Install SSL certificate
2. Enable HTTPS redirect in `.htaccess`
3. Update `ENVIRONMENT` to 'production'
4. Set `ENCRYPTION_KEY` environment variable

---

## 5. ✅ AUTHENTICATION & AUTHORIZATION

### Secure Password Hashing
- **File**: `api/includes/auth.php`
- **Method**: `password_hash()` with bcrypt (cost 12)
- **Verification**: `password_verify()`

### Session Management
```php
// Session regeneration prevents fixation attacks
session_regenerate_id(true);

// Session timeout after 30 minutes
if (time() - $_SESSION['last_activity'] > 1800) {
    session_destroy();
}

// CSRF protection
$csrfToken = generateCSRFToken();
validateCSRFToken($token);
```

### CSRF Protection
- **File**: `includes/csrf.php`
- **Methods**:
  - `generateCSRFToken()` - Generate unique token
  - `validateCSRFToken($token)` - Verify token

### Rate Limiting
- **Class**: `RateLimiter` in `config/security.php`
- **Configuration**: 100 requests per hour per IP
- **Storage**: APCu cache

```php
$rateLimiter = new RateLimiter($conn);
if ($rateLimiter->isLimited($clientIP)) {
    http_response_code(429);
    die('Too many requests');
}
```

---

## 6. ✅ CORS & API SECURITY

### Whitelist Validation
```php
// config/security.php
$ALLOWED_ORIGINS = [
    'http://localhost:8080',
    'https://yourdomain.com'
];

validateCORSOrigin(); // Checks against whitelist
```

### Secure CORS Headers
```php
Header set Access-Control-Allow-Origin: [WHITELISTED DOMAIN]
Header set Access-Control-Allow-Methods: GET, POST, OPTIONS
Header set Access-Control-Allow-Credentials: true
```

---

## 7. ✅ INPUT SANITIZATION

### XSS Prevention
```php
// Output escaping for HTML context
$safe = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Text validation and sanitization
$text = Validator::text($input, 1000, 0);
```

### File Upload Security
- Filename sanitization in `Sanitizer::filename()`
- Size limits in `.htaccess`: 5MB max
- File type validation on backend

---

## 8. ✅ LOGGING & MONITORING

### Security Logger
- **File**: `config/security.php` - `SecurityLogger` class
- **Log Location**: `logs/` directory with restricted permissions
- **Events Logged**:
  - Login attempts (success/failure)
  - Data creation (bookings, orders)
  - Validation errors
  - Authentication failures

### Example Usage
```php
$logger = new SecurityLogger('bookings.log');
$logger->log('BOOKING_CREATED', [
    'booking_id' => $id,
    'customer_email' => $email
]);
```

---

## Production Deployment Checklist

- [ ] Enable HTTPS (SSL certificate installed)
- [ ] Uncomment HTTPS redirect in `.htaccess`
- [ ] Set `ENVIRONMENT` to 'production' in `config/security.php`
- [ ] Set `ENCRYPTION_KEY` environment variable (min 32 chars)
- [ ] Restrict database user permissions (minimal required)
- [ ] Enable firewall rules
- [ ] Set up log monitoring
- [ ] Configure error logging (logs not visible to users)
- [ ] Update `.env` or environment variables with secrets
- [ ] Test all validation rules
- [ ] Configure rate limiting thresholds
- [ ] Set up CORS whitelist with production domain
- [ ] Review and test CSRF token generation
- [ ] Verify all prepared statements are used

---

## Testing Security

### Manual Testing
1. **SQL Injection Test**: Try `' OR '1'='1` in forms - should be blocked
2. **XSS Test**: Try `<script>alert('XSS')</script>` - should be escaped
3. **CSRF Test**: Try submitting from different origin - should fail
4. **Rate Limiting Test**: Send 101 requests in 1 hour - should get 429
5. **Session Test**: Check session expires after 30 minutes

### Automated Testing
```bash
# Test with curl - check headers
curl -i https://localhost:8080/seee/api/create-booking.php

# Check for security headers
curl -i https://yourdomain.com | grep -E "Strict-Transport|X-Frame-Options|X-Content-Type"
```

---

## API Security Headers Reference

| Header | Purpose | Value |
|--------|---------|-------|
| Strict-Transport-Security | Force HTTPS | max-age=31536000 |
| X-Frame-Options | Clickjacking | DENY |
| X-Content-Type-Options | MIME sniffing | nosniff |
| X-XSS-Protection | XSS defense | 1; mode=block |
| Content-Security-Policy | Resource loading | restrictive policy |
| Referrer-Policy | Referrer info | strict-origin-when-cross-origin |

---

## Files Modified/Created

### New Files
- `includes/security-headers.php` - Security header configuration
- `config/security.php` - Validators, sanitizers, logger, rate limiter
- `includes/csrf.php` - CSRF and JWT token management
- `includes/DataEncryption.php` - Data encryption utilities

### Modified Files
- `api/create-booking.php` - Complete security overhaul
- `api/create-order.php` - Complete security overhaul
- `api/includes/auth.php` - Secure authentication
- `.htaccess` - Enhanced security rules

---

## Support & References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security](https://www.php.net/manual/en/security.php)
- [MySQLi Prepared Statements](https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php)
- [Password Hashing](https://www.php.net/manual/en/function.password-hash.php)

