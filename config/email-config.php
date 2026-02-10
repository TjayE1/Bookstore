<?php
/**
 * Email Configuration
 * Update these settings with your email credentials
 */

// Email Settings
define('SMTP_HOST', 'smtp.gmail.com'); // Gmail SMTP server
define('SMTP_PORT', 587); // Use 465 for SSL, 587 for TLS
define('SMTP_SECURE', 'tls'); // 'ssl' or 'tls'
define('SMTP_USERNAME', 'estherpotorico@gmail.com'); // Your Gmail email (FIXED: removed .com.com)
define('SMTP_PASSWORD', 'tfzk plnp jsos kypp'); // Your app-specific password

// Sender Information
define('FROM_EMAIL', 'estherpotorico@gmail.com'); // Email address that sends emails (FIXED)
define('FROM_NAME', "Reader's Haven"); // Sender name

// Admin Notification Email
define('ADMIN_EMAIL', 'estherpotorico@gmail.com'); // Where to receive order notifications

// Site Settings
define('SITE_NAME', "Reader's Haven");
define('SITE_URL', 'http://localhost:8080/seee'); // Your website URL
define('SUPPORT_EMAIL', 'estherpotorico@gmail.com'); // FIXED: use actual email

// Enable/Disable Email Sending (set to false for testing)
define('ENABLE_EMAILS', true);
?>
