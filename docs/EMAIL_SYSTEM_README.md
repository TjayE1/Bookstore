# PHPMailer Email System - Quick Reference

## 📁 File Structure Created

```
seee/
├── config/
│   └── email-config.php          # Email settings (UPDATE THIS!)
├── api/
│   ├── send-order-email.php      # Order confirmation endpoint
│   └── send-booking-email.php    # Booking confirmation endpoint
├── includes/
│   ├── PHPMailer.php             # PHPMailer library (DOWNLOAD NEEDED!)
│   ├── SMTP.php                  # (Download from PHPMailer)
│   ├── Exception.php             # (Download from PHPMailer)
│   └── send-email.php            # Email sending function
├── test-emails.html              # Test page for emails
├── shopping-cart.html            # Updated with email calls
└── SETUP_INSTRUCTIONS.md         # Full setup guide
```

## 🚀 Quick Setup (3 Steps)

### Step 1: Download PHPMailer
```
Visit: https://github.com/PHPMailer/PHPMailer/releases
Download latest version
Extract these files to seee/includes/:
  - src/PHPMailer.php
  - src/SMTP.php
  - src/Exception.php
```

### Step 2: Configure Email
Edit `config/email-config.php`:
```php
define('SMTP_USERNAME', 'noreply@yourdomain.com'); // Your Hostinger email
define('SMTP_PASSWORD', 'your-password-here');      // Email password
define('ADMIN_EMAIL', 'admin@yourdomain.com');      // Admin notifications
define('SITE_URL', 'https://yourdomain.com');       // Your website
```

### Step 3: Test Locally
1. Run PHP server: `php -S localhost:8000`
2. Open: `http://localhost:8000/test-emails.html`
3. Enter your email and click "Send Test Order Email"
4. Check your inbox!

## 📧 Email Features

### Order Confirmation Email Includes:
- ✅ Beautiful HTML template with your branding
- ✅ Complete order details with itemized list
- ✅ Customer name and email
- ✅ Order total in UGX
- ✅ Professional footer
- ✅ Admin notification copy

### Booking Confirmation Email Includes:
- ✅ Appointment date and time
- ✅ Customer message/notes
- ✅ Pre-appointment instructions
- ✅ Support contact information
- ✅ Admin notification copy

## 🔧 How It Works

1. **Customer places order** → JavaScript calls `sendOrderConfirmationEmail()`
2. **Function sends POST** → `api/send-order-email.php`
3. **PHP validates data** → Checks email, name, items
4. **PHPMailer sends** → SMTP to customer + admin
5. **Returns JSON** → Success or error message

## 💻 Code Usage

### In JavaScript (already added to shopping-cart.html):
```javascript
// Send order email
await sendOrderConfirmationEmail({
    customerName: 'John Doe',
    customerEmail: 'john@example.com',
    items: [...],
    total: 150000,
    orderDate: new Date().toISOString()
});

// Send booking email
await sendBookingConfirmationEmail({
    name: 'Jane Doe',
    email: 'jane@example.com',
    date: '2026-01-25',
    time: '14:00',
    message: 'Looking forward to the session'
});
```

## 🎨 Email Templates

Both emails use:
- **Colors**: #7A9B8E (green), #5B7C99 (blue)
- **Responsive**: Works on mobile and desktop
- **Professional**: Gradient headers, styled tables
- **Branded**: Reader's Haven logo and footer

## 🔐 Security Notes

- ✅ HTML escaping prevents XSS attacks
- ✅ Email validation filters
- ✅ CORS headers for API security
- ✅ POST-only endpoints
- ⚠️ **Never commit passwords to GitHub!**

## 🐛 Troubleshooting

**Email not sending?**
```
1. Check PHP error log
2. Verify SMTP credentials
3. Try port 465 with SSL instead of 587/TLS
4. Contact Hostinger support
5. Check spam folder
```

**"Network Error" in test page?**
```
Must run through PHP server, not file://
Use: php -S localhost:8000
Or upload to Hostinger to test
```

**Emails going to spam?**
```
Ask Hostinger to configure:
- SPF records
- DKIM signing
- Proper reverse DNS
```

## 📋 Pre-Deployment Checklist

- [ ] PHPMailer library installed
- [ ] `email-config.php` updated with real credentials
- [ ] `ENABLE_EMAILS` set to `true`
- [ ] Tested with `test-emails.html`
- [ ] Verified emails arrive in inbox (not spam)
- [ ] Admin email notifications working
- [ ] HTTPS enabled on domain
- [ ] File permissions set correctly (755/644)

## 🌐 Hostinger Deployment

1. **Upload files** via FTP or File Manager to `public_html/seee/`
2. **Set permissions**:
   - Folders: 755
   - PHP files: 644
3. **Test live**: `https://yourdomain.com/seee/test-emails.html`
4. **Check email**: Verify confirmations arrive
5. **Monitor**: Check cPanel error logs

## 📞 Support

**Hostinger Email Help:**
- Knowledge Base: https://support.hostinger.com
- Live Chat: Available 24/7
- Ask about: SMTP settings, email limits, SPF/DKIM

**PHPMailer Documentation:**
- GitHub: https://github.com/PHPMailer/PHPMailer
- Wiki: https://github.com/PHPMailer/PHPMailer/wiki

## ✨ What's Next?

After emails work:
1. ✅ Set up MySQL database for orders
2. ✅ Add payment gateway (Flutterwave/Pesapal)
3. ✅ Create admin dashboard
4. ✅ Add inventory management
5. ✅ Implement search functionality

---

**Need help?** Check `SETUP_INSTRUCTIONS.md` for detailed setup guide!
