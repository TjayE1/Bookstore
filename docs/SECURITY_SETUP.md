# Security Setup Instructions

## Quick Start

### 1. Update Your .env File
Create or update `.env` in project root:
```
ENVIRONMENT=production
ENCRYPTION_KEY=your-secret-key-min-32-chars-long!1234567890
```

### 2. Enable HTTPS (Production Only)
Edit `.htaccess`:
```apache
# Uncomment these lines
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3. Create Admin Account with Secure Password
```php
<?php
require_once 'config/database.php';
require_once 'api/includes/auth.php';

// Hash password using bcrypt
$password = 'YourSecurePassword123!'; // Min 8 characters
$hashedPassword = hashPassword($password);

// Insert admin user
$query = "INSERT INTO admin_users (username, email, password_hash, role, is_active) 
          VALUES (?, ?, ?, 'admin', 1)";
executeQuery($query, ['admin', 'admin@example.com', $hashedPassword]);

echo "Admin created successfully";
?>
```

### 4. Set Correct File Permissions
```bash
# Logs directory - writable by web server
chmod 700 logs/

# Config files - readable by web server only
chmod 600 config/security.php
chmod 600 config/database.php

# Disable public access to includes
chmod 000 includes/
chmod 700 includes/
```

### 5. Test Security Headers
```bash
# Check headers are being sent
curl -i https://yoursite.com/seee/index.html | grep -E "Strict-Transport|X-Frame|X-Content"
```

---

## Validation Rules Summary

### Name Field
- Letters, spaces, hyphens, apostrophes only
- 2-100 characters
- Example: `John O'Brien`

### Email Field
- Standard RFC email format
- Max 255 characters
- Example: `user@example.com`

### Phone Field (Optional)
- 7-20 digits, +, -, (), spaces
- Example: `+1 (555) 123-4567`

### Date Field
- Format: `YYYY-MM-DD`
- Must be future date
- Must be valid calendar date
- Example: `2025-02-01`

### Time Field
- Format: `HH:MM` (24-hour)
- Example: `14:30`

### Message Field (Optional)
- Max 1000 characters
- HTML special characters escaped
- Example: `Please call me at 2pm`

### Price/Amount Field
- Decimal format
- $0.01 - $999,999.99
- Example: `49.99`

### Quantity Field
- Integer only
- 1-1000 items
- Example: `5`

---

## API Request Examples

### Create Booking
```bash
curl -X POST http://localhost:8080/seee/api/create-booking.php \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Smith",
    "email": "john@example.com",
    "phone": "+1 (555) 123-4567",
    "date": "2025-02-28",
    "time": "14:30",
    "message": "Please reserve morning slot"
  }'
```

**Response (Success)**:
```json
{
  "success": true,
  "message": "Booking created successfully",
  "bookingId": 123,
  "bookingNumber": "BOOK-20250124123456-abc123"
}
```

**Response (Error)**:
```json
{
  "success": false,
  "message": "Invalid email address"
}
```

### Create Order
```bash
curl -X POST http://localhost:8080/seee/api/create-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "customerName": "Jane Doe",
    "customerEmail": "jane@example.com",
    "items": [
      {
        "id": 1,
        "name": "Product Name",
        "quantity": 2,
        "price": 29.99
      }
    ],
    "total": 59.98
  }'
```

---

## Rate Limiting

**Current Settings**: 100 requests per hour per IP address

**Response when limit exceeded**:
```json
{
  "success": false,
  "message": "Too many requests. Please try again later."
}
```

**HTTP Status**: 429 Too Many Requests

---

## CORS Configuration

### Allowed Origins (Development)
- `http://localhost:8080`
- `http://localhost:3000`

### To Add Production Domain
Edit `config/security.php`:
```php
$ALLOWED_ORIGINS = [
    'http://localhost:8080',
    'https://yourdomain.com'  // Add this
];
```

---

## Common Issues & Solutions

### Issue: "403 Forbidden" on API calls
**Solution**: Check `.htaccess` doesn't block API directory
```apache
# Ensure API is NOT blocked
RewriteRule ^api/ - [L]
```

### Issue: "Too many requests" error immediately
**Solution**: 
1. Check APCu is installed: `php -m | grep apcu`
2. Increase rate limit in `config/security.php`:
```php
define('RATE_LIMIT_REQUESTS', 1000);
```

### Issue: Passwords not working after update
**Solution**: Old passwords use `password_hash`, new system requires bcrypt. Reset all admin passwords:
```php
$newHash = password_hash('newpassword123', PASSWORD_BCRYPT, ['cost' => 12]);
// Update in database
```

### Issue: HTTPS certificate errors
**Solution**: 
1. Ensure SSL certificate is valid
2. Update domain in `.htaccess`
3. Test with: `curl -v https://yourdomain.com`

---

## Monitoring & Logs

### Security Logs Location
```
logs/
  ├── security.log
  ├── bookings.log
  ├── orders.log
  ├── auth.log
  └── error_log.txt
```

### View Recent Bookings
```bash
tail -f logs/bookings.log
```

### View Auth Failures
```bash
grep "LOGIN_FAILED" logs/auth.log
```

---

## Additional Security Tips

1. **Regular Updates**: Keep PHP, MySQL, and dependencies updated
2. **Backups**: Daily backups of database and code
3. **Monitoring**: Set up alerts for failed login attempts
4. **Firewall**: Use ModSecurity or AWS WAF
5. **Database**: Restrict MySQL user to specific host
6. **Passwords**: Enforce strong admin passwords (min 12 chars)
7. **2FA**: Consider adding two-factor authentication for admin
8. **SSL**: Use TLS 1.2+ minimum

---

## Need Help?

See `SECURITY_IMPLEMENTATION.md` for detailed documentation.

