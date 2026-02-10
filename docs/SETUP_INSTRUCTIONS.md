# Email Setup Instructions for Hostinger

## 📧 Email System Setup

### Step 1: Download PHPMailer Library

1. **Download PHPMailer** from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract the downloaded ZIP file
3. Copy these files to `seee/includes/` folder:
   - `src/PHPMailer.php`
   - `src/SMTP.php`
   - `src/Exception.php`
4. Replace the placeholder `PHPMailer.php` file

**OR use Composer (if available on your hosting):**
```bash
cd seee
composer require phpmailer/phpmailer
```

Then update `includes/PHPMailer.php` to:
```php
<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
?>
```

### Step 2: Configure Email Settings on Hostinger

1. **Login to Hostinger cPanel**
2. **Create an Email Account:**
   - Go to "Email Accounts"
   - Create: `noreply@yourdomain.com` (for sending emails)
   - Create: `admin@yourdomain.com` (for receiving notifications)
   - Set strong passwords

3. **Get SMTP Settings:**
   - Hostinger SMTP Server: `smtp.hostinger.com`
   - Port: `587` (TLS) or `465` (SSL)
   - Username: Your full email address (e.g., `noreply@yourdomain.com`)
   - Password: Your email password

### Step 3: Update Configuration File

Edit `config/email-config.php` and update:

```php
// Update these with your actual values:
define('SMTP_USERNAME', 'noreply@yourdomain.com'); // Your Hostinger email
define('SMTP_PASSWORD', 'your-actual-password');    // Email password
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('ADMIN_EMAIL', 'admin@yourdomain.com');
define('SITE_URL', 'https://yourdomain.com');
```

### Step 4: Update Shopping Cart JavaScript

The JavaScript files need to be updated to call the PHP email APIs.

**File:** `shopping-cart.html`

Find the order success section (around line 1880) and update to call the email API.

### Step 5: Test Email Functionality

1. **Set to test mode first:**
   In `config/email-config.php`:
   ```php
   define('ENABLE_EMAILS', false); // Test without sending
   ```

2. **Test the API:**
   ```bash
   # Test order email API
   curl -X POST http://localhost/seee/api/send-order-email.php \
     -H "Content-Type: application/json" \
     -d '{"customerName":"Test User","customerEmail":"test@email.com","items":[{"name":"Test Book","quantity":1,"price":50000}],"total":50000}'
   ```

3. **Enable emails when ready:**
   ```php
   define('ENABLE_EMAILS', true);
   ```

### Step 6: Upload to Hostinger

1. **Upload all files via FTP or File Manager:**
   ```
   public_html/
     ├── seee/
     │   ├── shopping-cart.html
     │   ├── config/
     │   │   └── email-config.php
     │   ├── api/
     │   │   ├── send-order-email.php
     │   │   └── send-booking-email.php
     │   └── includes/
     │       ├── PHPMailer.php
     │       ├── SMTP.php
     │       ├── Exception.php
     │       └── send-email.php
   ```

2. **Set proper permissions:**
   - Folders: `755`
   - PHP files: `644`

3. **Test on live site**

### Step 7: Security Checklist

- [ ] Never commit `email-config.php` with real passwords to GitHub
- [ ] Use environment variables for sensitive data (if available)
- [ ] Enable HTTPS on your domain (free with Hostinger)
- [ ] Test emails in spam folder
- [ ] Add SPF and DKIM records (ask Hostinger support)

## 🔧 Troubleshooting

**Emails not sending?**
1. Check `error_log` file in cPanel
2. Verify SMTP credentials are correct
3. Check spam folder
4. Ensure port 587 is not blocked
5. Try port 465 with SSL instead of TLS

**SMTP Authentication Failed?**
- Double-check email password
- Ensure you're using full email address as username
- Verify email account exists in cPanel

**Contact Hostinger Support:**
- They can help with SMTP configuration
- Ask about email sending limits
- Request SPF/DKIM setup for better delivery

## 📝 Next Steps

After email is working:
1. Set up database for storing orders
2. Add payment gateway integration
3. Create admin dashboard for order management
