# 🚀 Deployment Checklist for Hostinger

## Pre-Deployment (Local Testing)

### Database Setup
- [ ] Verify database schema is correct: `database/database_schema.sql`
- [ ] Test locally with XAMPP/WAMP/LAMP
- [ ] Confirm all tables created successfully
- [ ] Verify default data inserted (3 products, 1 admin user)
- [ ] Test database queries manually in phpMyAdmin

### API Testing
- [ ] Test GET /api/get-products.php
- [ ] Test POST /api/create-order.php
- [ ] Test POST /api/create-booking.php
- [ ] Test GET /api/get-available-slots.php?date=2026-01-25
- [ ] Test GET /api/get-unavailable-dates.php
- [ ] Test email sending with test-emails.html
- [ ] Test admin APIs with authentication

### Configuration
- [ ] Update `config/database.php` with local credentials
- [ ] Update `config/email-config.php` with test email
- [ ] Set DEBUG_MODE = true for testing
- [ ] Verify all file permissions are correct

### Frontend Integration
- [ ] Test shopping cart with mock data
- [ ] Test booking form with available slots
- [ ] Test email confirmations
- [ ] Test admin dashboard login
- [ ] Verify no JavaScript errors in console

---

## Hostinger Upload

### Step 1: File Upload
- [ ] Upload entire `seee/` folder via FTP/File Manager
- [ ] Verify directory structure:
  ```
  seee/
  ├── config/database.php
  ├── database/database_schema.sql
  ├── api/
  ├── includes/
  └── [all HTML files]
  ```
- [ ] Set folder permissions to 755
- [ ] Set PHP file permissions to 644
- [ ] Set .htaccess permissions to 644

### Step 2: Database Setup on Hostinger
- [ ] Login to cPanel
- [ ] Go to "MySQL Databases"
- [ ] Create database: `readers_haven`
- [ ] Create database user with strong password
- [ ] Grant all privileges to user
- [ ] Go to phpMyAdmin
- [ ] Select the new database
- [ ] Click "Import"
- [ ] Upload `database/database_schema.sql`
- [ ] Click "Go" to execute
- [ ] Verify all 9 tables created
- [ ] Verify default data inserted

### Step 3: Configuration Update
- [ ] Edit `config/database.php` with Hostinger credentials:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_USER', 'db_username');
  define('DB_PASS', 'db_password');
  define('DB_NAME', 'readers_haven');
  ```
- [ ] Save the file
- [ ] Re-upload to server if needed

### Step 4: Email Configuration
- [ ] Create email account in cPanel (e.g., noreply@yourdomain.com)
- [ ] Get SMTP settings from Hostinger
- [ ] Edit `config/email-config.php`:
  ```php
  define('SMTP_USERNAME', 'noreply@yourdomain.com');
  define('SMTP_PASSWORD', 'email_password');
  define('ADMIN_EMAIL', 'admin@yourdomain.com');
  define('SITE_URL', 'https://yourdomain.com');
  ```
- [ ] Download and install PHPMailer library to `includes/`
- [ ] Test email sending with `test-emails.html`

---

## Security Hardening

### Hostinger Security
- [ ] Enable SSL/HTTPS on domain
- [ ] Update .htaccess with security rules
- [ ] Set DEBUG_MODE = false in `config/database.php`
- [ ] Remove or protect database schema file:
  - [ ] Move `database/database_schema.sql` outside web root
  - [ ] Or add to .htaccess: `<FilesMatch "\.sql$"> Deny from all </FilesMatch>`
- [ ] Protect config files:
  - [ ] Verify .htaccess denies access to `/config/`

### Admin Security
- [ ] Change default admin password IMMEDIATELY
  - [ ] Login with: admin / admin123
  - [ ] Update admin password in MySQL:
    ```sql
    UPDATE admin_users SET password_hash = PASSWORD('newpassword') 
    WHERE username = 'admin';
    ```
- [ ] Add new admin user with strong password
- [ ] Consider IP whitelisting for admin panel

### Database Security
- [ ] Backup database before going live
- [ ] Set up automatic backups in cPanel
- [ ] Restrict database user to specific host (localhost)
- [ ] Use strong passwords (20+ characters)
- [ ] Regularly rotate credentials

---

## Testing on Live Server

### API Endpoints
- [ ] GET https://yourdomain.com/seee/api/get-products.php
- [ ] POST https://yourdomain.com/seee/api/create-order.php (test order)
- [ ] POST https://yourdomain.com/seee/api/create-booking.php (test booking)
- [ ] GET https://yourdomain.com/seee/api/get-available-slots.php?date=2026-01-25
- [ ] GET https://yourdomain.com/seee/api/get-unavailable-dates.php
- [ ] GET https://yourdomain.com/seee/api/admin/get-stats.php (should fail without auth)

### Orders & Bookings
- [ ] Create test order and verify in database
- [ ] Verify email confirmation received
- [ ] Verify inventory updated
- [ ] Create test booking and verify in database
- [ ] Verify booking confirmation email received
- [ ] Check admin dashboard shows new order/booking

### Admin Features
- [ ] Login as admin (admin / admin123 or new password)
- [ ] Access admin-orders.html
- [ ] Access admin-bookings.html
- [ ] View statistics and recent activity
- [ ] Add unavailable date
- [ ] Update order status
- [ ] Update booking status

### Email System
- [ ] Test with https://yourdomain.com/seee/test-emails.html
- [ ] Check spam folder if email doesn't arrive immediately
- [ ] Verify SPF/DKIM records in cPanel (ask support if needed)
- [ ] Test different email clients (Gmail, Outlook, etc)

---

## Performance & Optimization

### Database
- [ ] Verify all indexes created (check BACKEND_SETUP.md)
- [ ] Run OPTIMIZE TABLE on all tables:
  ```sql
  OPTIMIZE TABLE products, customers, orders, order_items, 
               bookings, unavailable_dates, admin_users, 
               inventory, audit_logs;
  ```
- [ ] Set up query logging to identify slow queries
- [ ] Consider caching for frequently accessed data

### Server
- [ ] Enable gzip compression in .htaccess
- [ ] Set proper cache headers for static files
- [ ] Monitor PHP error logs
- [ ] Check server resource usage (cPanel > Resource Usage)
- [ ] Enable APC or OPcache if available

### Code
- [ ] Set DEBUG_MODE = false
- [ ] Enable error logging but hide from users
- [ ] Minify CSS and JavaScript (optional)
- [ ] Optimize image sizes
- [ ] Use CDN for static files (if available)

---

## Monitoring & Maintenance

### Daily
- [ ] Check error logs in cPanel
- [ ] Monitor database size
- [ ] Spot-check a few recent orders
- [ ] Verify email deliveries

### Weekly
- [ ] Review admin dashboard for unusual activity
- [ ] Check database backup completed
- [ ] Test a few critical API endpoints
- [ ] Monitor traffic and resource usage

### Monthly
- [ ] Update admin password
- [ ] Review security logs
- [ ] Backup database manually
- [ ] Update any security patches
- [ ] Analyze customer feedback

---

## Backup & Recovery

### Before Going Live
- [ ] Create full backup of seee/ folder
- [ ] Create backup of database schema
- [ ] Document all credentials securely

### Regular Backups
- [ ] Set up automatic database backups in cPanel
- [ ] Backup files weekly via FTP
- [ ] Test backup restoration quarterly

### Disaster Recovery Plan
- [ ] Can restore from: cPanel backup
- [ ] Can restore from: database schema SQL file
- [ ] Can restore from: local backups
- [ ] Estimated recovery time: ~1 hour

---

## Go-Live Checklist

### 24 Hours Before
- [ ] Run final tests on local environment
- [ ] Verify all credentials ready
- [ ] Notify any stakeholders
- [ ] Prepare rollback plan

### Launch Day
- [ ] Upload all files to Hostinger
- [ ] Import database schema
- [ ] Update configuration files
- [ ] Test all endpoints
- [ ] Verify emails working
- [ ] Do final admin panel test
- [ ] Monitor for errors first 2 hours

### Post-Launch
- [ ] Monitor server performance
- [ ] Watch error logs
- [ ] Verify customer orders being received
- [ ] Test customer emails arrive
- [ ] Collect feedback from first users
- [ ] Be ready to rollback if issues

---

## Common Issues & Solutions

### Database Connection Error
```
Issue: "Connection refused"
Solution: 
1. Verify DB credentials in config/database.php
2. Confirm database exists in phpMyAdmin
3. Check MySQL user has correct permissions
4. Ask Hostinger support to verify MySQL is running
```

### Email Not Sending
```
Issue: "Failed to send email"
Solution:
1. Verify email account created in cPanel
2. Check SMTP credentials in config/email-config.php
3. Try port 465 with SSL or 587 with TLS
4. Ask Hostinger support for SMTP settings
5. Check spam folder
```

### 500 Error on API
```
Issue: "Internal Server Error"
Solution:
1. Set DEBUG_MODE = true temporarily
2. Check error log in cPanel
3. Verify all PHP files are readable
4. Verify database is accessible
5. Check for syntax errors in API files
```

### Admin Login Not Working
```
Issue: "Invalid username or password"
Solution:
1. Verify admin user exists: SELECT * FROM admin_users;
2. Check password hash is correct
3. Verify session.save_path is writable
4. Try restarting PHP
5. Clear browser cookies
```

---

## Deployment Complete! ✅

Once all checkboxes are complete:
- ✅ Database is live
- ✅ All APIs working
- ✅ Emails sending
- ✅ Admin panel accessible
- ✅ Orders being created
- ✅ Bookings being saved
- ✅ System is secure
- ✅ Monitoring in place

**You're ready to take orders!** 🎉

---

## Contacts & Resources

**Hostinger Support:**
- Email: support@hostinger.com
- Chat: Available 24/7 in cPanel
- Knowledge Base: https://support.hostinger.com

**Your Team:**
- Admin: admin@yourdomain.com
- Support: support@yourdomain.com
- Tech: [your contact]

**Backups Location:**
- Server: /home/username/backups/
- Local: [your backup location]
- Cloud: [if using cloud backup]

---

**Last Updated:** January 22, 2026
**Version:** 1.0
**Ready for Deployment:** ✅ YES

**Questions?** Refer to:
- API_DOCUMENTATION.md - API reference
- BACKEND_SETUP.md - Backend setup
- INTEGRATION_GUIDE.md - Integration help
- EMAIL_SYSTEM_README.md - Email setup
