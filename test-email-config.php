<?php
/**
 * Test Email Configuration
 * Visit: http://localhost:8080/seee/test-emails.php
 */

require_once __DIR__ . '/config/email-config.php';
require_once __DIR__ . '/includes/PHPMailer.php';
require_once __DIR__ . '/includes/SMTP.php';
require_once __DIR__ . '/includes/Exception.php';
require_once __DIR__ . '/includes/send-email.php';

error_log("=== EMAIL CONFIGURATION TEST ===");
error_log("SMTP_HOST: " . SMTP_HOST);
error_log("SMTP_PORT: " . SMTP_PORT);
error_log("SMTP_SECURE: " . SMTP_SECURE);
error_log("SMTP_USERNAME: " . SMTP_USERNAME);
error_log("FROM_EMAIL: " . FROM_EMAIL);
error_log("ADMIN_EMAIL: " . ADMIN_EMAIL);
error_log("ENABLE_EMAILS: " . (ENABLE_EMAILS ? 'true' : 'false'));

echo "<h1>Email Configuration Test</h1>";
echo "<p>Check browser console (F12) and PHP server output for logs...</p>";

if (!ENABLE_EMAILS) {
    echo "<h2 style='color:red;'>❌ Email sending is DISABLED</h2>";
    echo "<p>Set ENABLE_EMAILS = true in config/email-config.php</p>";
} else {
    echo "<h2 style='color:green;'>✓ Email sending is ENABLED</h2>";
}

// Test sending email
echo "<h2>Attempting Test Email...</h2>";
$testResult = sendEmail(
    ADMIN_EMAIL,
    'Test User',
    'Test Email from Reader\'s Haven',
    '<h1>Test Email</h1><p>If you receive this, email is working!</p>'
);

if ($testResult['success']) {
    echo "<h2 style='color:green;'>✓ Email sent successfully!</h2>";
    echo "<p>Check your email: " . ADMIN_EMAIL . "</p>";
} else {
    echo "<h2 style='color:red;'>❌ Failed to send email</h2>";
    echo "<p>Error: " . ($testResult['error'] ?? $testResult['message']) . "</p>";
}

echo "<hr>";
echo "<h3>Configuration Details:</h3>";
echo "<pre>";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP Secure: " . SMTP_SECURE . "\n";
echo "Username: " . SMTP_USERNAME . "\n";
echo "From Email: " . FROM_EMAIL . "\n";
echo "</pre>";

?>
