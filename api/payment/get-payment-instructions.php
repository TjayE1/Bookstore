<?php
/**
 * API: Get Payment Instructions
 * Endpoint: GET /api/payment/get-payment-instructions.php?orderId=123&method=bank_transfer
 * Returns: Payment instructions for specific method
 */

require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment-config.php';

header('Content-Type: application/json; charset=utf-8');
validateCORSOrigin();

try {
    $orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : null;
    $method = isset($_GET['method']) ? trim($_GET['method']) : null;
    
    if (!$orderId || !$method) {
        throw new Exception('Missing orderId or payment method');
    }
    
    // Get order details
    $orderQuery = "SELECT id, order_number, total_amount FROM orders WHERE id = ? LIMIT 1";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    $paymentData = null;
    
    switch ($method) {
        case 'bank_transfer':
            $paymentData = generateBankPaymentSlip($orderId, $order['total_amount'], $order['order_number']);
            break;
            
        case 'mobile_money':
            $provider = isset($_GET['provider']) ? $_GET['provider'] : 'mtn';
            $paymentData = generateMobileMoneySlip($orderId, $order['total_amount'], $order['order_number'], $provider);
            break;
            
        case 'paypal':
            // PayPal button will be handled client-side
            $paymentData = [
                'type' => 'paypal',
                'order_id' => $orderId,
                'order_number' => $order['order_number'],
                'amount' => $order['total_amount'],
                'currency' => 'USD', // PayPal typically uses USD
                'clientId' => PAYPAL_CLIENT_ID,
                'mode' => PAYPAL_MODE,
            ];
            break;
            
        case 'card':
            // Stripe setup
            $paymentData = [
                'type' => 'card',
                'order_id' => $orderId,
                'order_number' => $order['order_number'],
                'amount' => (int)($order['total_amount'] * 100), // Stripe uses cents
                'currency' => 'ugx',
                'publishableKey' => STRIPE_PUBLIC_KEY,
            ];
            break;
            
        case 'pod':
            $paymentData = [
                'type' => 'pod',
                'order_id' => $orderId,
                'order_number' => $order['order_number'],
                'message' => 'Please have payment ready when your delivery arrives.',
            ];
            break;
            
        default:
            throw new Exception('Unsupported payment method: ' . $method);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $paymentData
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
