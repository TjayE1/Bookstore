<?php
/**
 * PayPal Integration Configuration
 * 
 * IMPORTANT: This file contains placeholders for PayPal credentials.
 * Follow the setup guide below to get your credentials from PayPal.
 * 
 * SETUP GUIDE:
 * ===========
 * 
 * 1. GO TO PAYPAL DEVELOPER SANDBOX (Testing):
 *    - Visit: https://developer.paypal.com/dashboard/
 *    - Sign up or login with your PayPal account
 *    - Go to "Sandbox" (left sidebar)
 *    - You'll see "Merchant" and "Buyer" test accounts
 *    - Click on "Merchant" account
 *    - Copy your Merchant Account ID
 * 
 * 2. GET YOUR API CREDENTIALS (Sandbox):
 *    - In Sandbox section, find "Apps & Credentials" tab
 *    - Select "Sandbox" at the top
 *    - Under "Client ID and Secret" section, you'll see your credentials
 *    - COPY CLIENT ID → Paste in PAYPAL_SANDBOX_CLIENT_ID below
 *    - COPY SECRET → Paste in PAYPAL_SANDBOX_SECRET below
 * 
 * 3. TEST CREDENTIALS (for sandbox testing):
 *    - Buyer Email: sb-foobar@personal.example.com (PayPal generates these)
 *    - Check dashboard for actual test buyer accounts
 * 
 * 4. GOING LIVE (Production):
 *    - When ready, go to "Live" tab (not Sandbox)
 *    - Get your LIVE Client ID and Secret
 *    - Paste them in PAYPAL_LIVE_CLIENT_ID and PAYPAL_LIVE_SECRET
 *    - Change IS_SANDBOX from true to false
 *    - Update this config file
 * 
 * 5. WEBHOOK SETUP (For Payment Confirmation):
 *    - Go to "Webhooks" in your PayPal dashboard
 *    - Add webhook URL: https://yourdomain.com/api/paypal-webhook.php
 *    - Select events: payment.capture.completed, payment.capture.refunded
 */

// ============================================
// SANDBOX MODE (Testing) - CHANGE THESE FIRST
// ============================================

// Set to TRUE for testing, FALSE for live
define('PAYPAL_IS_SANDBOX', true);

// Sandbox Credentials (Get from https://developer.paypal.com/dashboard/)
// Replace these with your actual sandbox credentials
define('PAYPAL_SANDBOX_CLIENT_ID', 'AWmmBGpdSY8oYNrSdJhUGmIRQB4RVOxqSQrq_QCazOUyN-0WZ-rJQNlMwR1ezJnoKor7CW6jqhKsS68W');
define('PAYPAL_SANDBOX_SECRET', 'EKmL-FUWzKe16xQcSVY9NkTWa0FCAJovJ3X8viyYP5VGVDUK178jV-cOuoRr2eIt1YcJYIcfvsejkVkQ');

// Sandbox API endpoint
define('PAYPAL_SANDBOX_API_URL', 'https://api-m.sandbox.paypal.com');

// ============================================
// LIVE MODE (Production) - FOR WHEN READY
// ============================================

// Live Credentials (Get from https://developer.paypal.com/dashboard/ - Live tab)
// You'll fill these when switching to production
define('PAYPAL_LIVE_CLIENT_ID', 'YOUR_LIVE_CLIENT_ID_HERE');
define('PAYPAL_LIVE_SECRET', 'YOUR_LIVE_SECRET_HERE');

// Live API endpoint
define('PAYPAL_LIVE_API_URL', 'https://api-m.paypal.com');

// ============================================
// AUTOMATIC CONFIGURATION (Based on IS_SANDBOX)
// ============================================

if (PAYPAL_IS_SANDBOX) {
    define('PAYPAL_CLIENT_ID', PAYPAL_SANDBOX_CLIENT_ID);
    define('PAYPAL_SECRET', PAYPAL_SANDBOX_SECRET);
    define('PAYPAL_API_URL', PAYPAL_SANDBOX_API_URL);
    define('PAYPAL_MODE', 'sandbox');
} else {
    define('PAYPAL_CLIENT_ID', PAYPAL_LIVE_CLIENT_ID);
    define('PAYPAL_SECRET', PAYPAL_LIVE_SECRET);
    define('PAYPAL_API_URL', PAYPAL_LIVE_API_URL);
    define('PAYPAL_MODE', 'live');
}

// ============================================
// PAYPAL PAYMENT CONFIGURATION
// ============================================

// Your return URLs (user gets sent here after PayPal)
define('PAYPAL_RETURN_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/seee/payment-success.html');
define('PAYPAL_CANCEL_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/seee/payment-cancelled.html');

// Store business information
define('PAYPAL_BUSINESS_NAME', 'Reader\'s Haven');
define('PAYPAL_BUSINESS_EMAIL', 'business@readers-haven.com'); // Your PayPal business email

// Currency (supported: USD, EUR, GBP, JPY, etc.)
define('PAYPAL_CURRENCY', 'USD');

// ============================================
// PAYMENT STATUS SETTINGS
// ============================================

// How long to wait for payment confirmation (in seconds)
define('PAYPAL_PAYMENT_TIMEOUT', 3600); // 1 hour

// Require IPN/Webhook verification (recommended: true)
define('PAYPAL_VERIFY_WEBHOOK', true);

// ============================================
// LOGGING & DEBUGGING
// ============================================

// Enable detailed PayPal logging (false in production)
define('PAYPAL_DEBUG_MODE', PAYPAL_IS_SANDBOX);

// Log file location
define('PAYPAL_LOG_FILE', __DIR__ . '/../logs/paypal.log');

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get PayPal Access Token (needed for API calls)
 */
function getPayPalAccessToken() {
    try {
        $GLOBALS['PAYPAL_LAST_AUTH_ERROR'] = null;
        $clientId = trim(PAYPAL_CLIENT_ID);
        $secret = trim(PAYPAL_SECRET);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorMessage = "Failed to get access token. HTTP Code: $httpCode. Response: $response";
            $parsedError = json_decode($response, true);
            if (!empty($parsedError['error_description'])) {
                $errorMessage = "Failed to get access token. HTTP Code: $httpCode. Error: {$parsedError['error_description']}";
            }
            $GLOBALS['PAYPAL_LAST_AUTH_ERROR'] = $errorMessage;
            logPayPalError($errorMessage);
            return false;
        }
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? false;
        
    } catch (Exception $e) {
        $GLOBALS['PAYPAL_LAST_AUTH_ERROR'] = 'Error getting access token: ' . $e->getMessage();
        logPayPalError($GLOBALS['PAYPAL_LAST_AUTH_ERROR']);
        return false;
    }
}

/**
 * Get last PayPal auth error (for user-friendly messaging)
 */
function getPayPalLastAuthError() {
    return $GLOBALS['PAYPAL_LAST_AUTH_ERROR'] ?? null;
}

/**
 * Log PayPal errors for debugging
 */
function logPayPalError($message) {
    if (defined('PAYPAL_LOG_FILE')) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        error_log($logMessage, 3, PAYPAL_LOG_FILE);
    }
}

/**
 * Log PayPal info for debugging
 */
function logPayPalInfo($message) {
    if (defined('PAYPAL_DEBUG_MODE') && PAYPAL_DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] INFO: $message\n";
        error_log($logMessage, 3, PAYPAL_LOG_FILE);
    }
}

// Verify credentials are filled in
if (PAYPAL_CLIENT_ID === 'YOUR_SANDBOX_CLIENT_ID_HERE' || 
    PAYPAL_CLIENT_ID === 'YOUR_LIVE_CLIENT_ID_HERE' ||
    PAYPAL_SECRET === 'YOUR_SANDBOX_SECRET_HERE' ||
    PAYPAL_SECRET === 'YOUR_LIVE_SECRET_HERE') {
    
    logPayPalError('CRITICAL: PayPal credentials not configured! Update config/paypal-config.php with your credentials.');
}

?>
