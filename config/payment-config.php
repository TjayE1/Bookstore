<?php
/**
 * Payment Gateway Configuration
 * Supports: Bank Transfer, Card, Mobile Money, PayPal
 */

require_once __DIR__ . '/../includes/EnvironmentConfig.php';

// ===== PAYMENT METHODS CONFIGURATION =====
$PAYMENT_METHODS = [
    'bank_transfer' => [
        'enabled' => true,
        'name' => 'Bank Transfer',
        'icon' => '🏦',
        'description' => 'Direct bank transfer - Instructions will be sent to your email',
        'requires_gateway' => false,
        'manual_confirmation' => true,
    ],
    'card' => [
        'enabled' => true,
        'name' => 'Card Payment (Stripe)',
        'icon' => '💳',
        'description' => 'Pay securely with Visa, Mastercard, or other cards',
        'requires_gateway' => true,
        'provider' => 'stripe',
    ],
    'mobile_money' => [
        'enabled' => true,
        'name' => 'Mobile Money',
        'icon' => '📱',
        'description' => 'MTN or Airtel money transfer',
        'requires_gateway' => false,
        'manual_confirmation' => true,
    ],
    'paypal' => [
        'enabled' => true,
        'name' => 'PayPal',
        'icon' => '🅿️',
        'description' => 'Fast and secure payment via PayPal',
        'requires_gateway' => true,
        'provider' => 'paypal',
    ],
    'pod' => [
        'enabled' => true,
        'name' => 'Pay on Delivery',
        'icon' => '🛒',
        'description' => 'Pay when you receive your order',
        'requires_gateway' => false,
        'manual_confirmation' => false,
    ],
];

// ===== STRIPE CONFIGURATION =====
// Sign up at https://stripe.com (no registration required, works with personal account)
define('STRIPE_ENABLED', getenv('STRIPE_ENABLED') ?: false);
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

// ===== PAYPAL CONFIGURATION =====
// Personal Account: https://developer.paypal.com (no business registration needed)
define('PAYPAL_ENABLED', getenv('PAYPAL_ENABLED') ?: false);
define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: '');
define('PAYPAL_SECRET', getenv('PAYPAL_SECRET') ?: '');
define('PAYPAL_MODE', getenv('PAYPAL_MODE') ?: 'sandbox'); // 'sandbox' or 'live'

// ===== BANK TRANSFER CONFIGURATION =====
$BANK_DETAILS = [
    'bank_name' => getenv('BANK_NAME') ?: 'Your Bank Name',
    'account_name' => getenv('ACCOUNT_NAME') ?: 'Business Account Name',
    'account_number' => getenv('ACCOUNT_NUMBER') ?: 'XXXXXXXXXX',
    'currency' => getenv('BANK_CURRENCY') ?: 'UGX',
    'swift_code' => getenv('SWIFT_CODE') ?: '',
    'iban' => getenv('IBAN') ?: '',
    'instructions' => 'Please transfer the exact amount to the account below. Use order number as reference.'
];

// ===== MOBILE MONEY CONFIGURATION =====
$MOBILE_MONEY = [
    'mtn' => [
        'enabled' => true,
        'number' => getenv('MTN_NUMBER') ?: '+256700000000',
        'name' => 'MTN Mobile Money',
        'instructions' => 'Send to this MTN number. Use order number in the memo.'
    ],
    'airtel' => [
        'enabled' => true,
        'number' => getenv('AIRTEL_NUMBER') ?: '+256700000001',
        'name' => 'Airtel Money',
        'instructions' => 'Send to this Airtel number. Use order number in the memo.'
    ],
];

// ===== PAYMENT STATUS MAPPING =====
$PAYMENT_STATUS_MESSAGES = [
    'pending' => 'Awaiting payment',
    'processing' => 'Processing your payment',
    'completed' => 'Payment received',
    'failed' => 'Payment failed',
    'refunded' => 'Payment refunded',
];

/**
 * Get enabled payment methods
 */
function getEnabledPaymentMethods() {
    global $PAYMENT_METHODS;
    return array_filter($PAYMENT_METHODS, function($method) {
        return $method['enabled'] ?? false;
    });
}

/**
 * Get payment method details
 */
function getPaymentMethod($methodId) {
    global $PAYMENT_METHODS;
    return $PAYMENT_METHODS[$methodId] ?? null;
}

/**
 * Generate bank transfer payment slip
 */
function generateBankPaymentSlip($orderId, $amount, $orderNumber) {
    global $BANK_DETAILS;
    
    return [
        'type' => 'bank_transfer',
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'amount' => $amount,
        'currency' => $BANK_DETAILS['currency'],
        'bank_name' => $BANK_DETAILS['bank_name'],
        'account_name' => $BANK_DETAILS['account_name'],
        'account_number' => $BANK_DETAILS['account_number'],
        'swift_code' => $BANK_DETAILS['swift_code'],
        'iban' => $BANK_DETAILS['iban'],
        'reference' => $orderNumber,
        'instructions' => $BANK_DETAILS['instructions'],
        'generated_at' => date('Y-m-d H:i:s'),
    ];
}

/**
 * Generate mobile money payment slip
 */
function generateMobileMoneySlip($orderId, $amount, $orderNumber, $provider = 'mtn') {
    global $MOBILE_MONEY;
    
    $provider_data = $MOBILE_MONEY[$provider] ?? null;
    if (!$provider_data) {
        throw new Exception("Mobile money provider not found: $provider");
    }
    
    return [
        'type' => 'mobile_money',
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'amount' => $amount,
        'currency' => 'UGX',
        'provider' => $provider,
        'provider_name' => $provider_data['name'],
        'phone_number' => $provider_data['number'],
        'reference' => $orderNumber,
        'instructions' => $provider_data['instructions'],
        'generated_at' => date('Y-m-d H:i:s'),
    ];
}
