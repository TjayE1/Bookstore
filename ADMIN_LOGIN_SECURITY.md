# Admin Login Security & Password Changes

## How login is secured
- Session auth with 30-minute idle timeout and session regeneration on login (see `api/includes/auth.php`).
- Password hashing with bcrypt; verify via `password_verify`; create hashes via `hashPassword()`.
- Role gating with `requireAdminRole()`; CSRF token set on login.
- Security logging to `logs/auth.log` via `SecurityLogger`.
- Headers/CORS controls in `config/security.php` and `.htaccess`.

## Change the admin password (safe, recommended)
Use a temporary PHP script, then delete it.

1) Create `admin-reset.php` in project root:
```
<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/api/includes/auth.php'; // hashPassword()

$new = 'NEW_STRONG_PASSWORD_HERE';
$username = 'admin'; // adjust

$hash = hashPassword($new);
$sql = "UPDATE admin_users SET password_hash = ? WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $hash, $username);
if ($stmt->execute()) { echo "Password updated\n"; } else { echo "Error: " . $stmt->error . "\n"; }
```
2) Run locally: `php admin-reset.php`
3) Verify it prints "Password updated".
4) Delete `admin-reset.php` immediately.

## Alternate: via MySQL/phpMyAdmin
1) Generate hash in CLI:
```
php -r "require 'api/includes/auth.php'; echo hashPassword('NEW_STRONG_PASSWORD_HERE');"
```
2) Update in MySQL:
```
UPDATE admin_users SET password_hash = 'PASTE_HASH_HERE' WHERE username = 'admin';
```

## Deployment hardening
- Enforce HTTPS in production (enable HTTPS rewrite in `.htaccess`).
- Restrict CORS: add your production domain in `$ALLOWED_ORIGINS` inside `config/security.php`.
- Optionally shorten session timeout (currently 30 minutes idle) in `auth.php`.
- Add rate limiting to the login endpoint using `RateLimiter` from `config/security.php`.
- Use strong passwords (12+ chars, mixed case/nums/symbols) and rotate periodically.

## Where things live
- Auth logic: `api/includes/auth.php`
- Security config/CORS/rate limit: `config/security.php`
- Logs: `logs/auth.log` (created automatically if missing)
- Admin table: `admin_users` (fields include `username`, `password_hash`, `role`, `is_active`)
