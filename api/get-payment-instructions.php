<?php
/**
 * API: Generate Payment Instructions
 * Endpoint: GET /api/get-payment-instructions.php?orderId=123
 * Returns: Payment instructions for bank transfer or mobile money
 */

require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment-config.php';

header('Content-Type: application/json; charset=utf-8');
validateCORSOrigin();

try {
    $orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : null;
    
    if (!$orderId) {
        throw new Exception('Order ID is required');
    }
    
    // Get order details
    $orderQuery = "SELECT id, order_number, total_amount, payment_method, customer_name 
                   FROM orders WHERE id = ? LIMIT 1";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    $instructions = null;
    
    switch ($order['payment_method']) {
        case 'bank_transfer':
            $instructions = generateBankPaymentSlip($orderId, $order['total_amount'], $order['order_number']);
            break;
            
        case 'mobile_money':
            // Default to MTN, but could be enhanced to ask user preference
            $instructions = generateMobileMoneySlip($orderId, $order['total_amount'], $order['order_number'], 'mtn');
            break;
            
        default:
            throw new Exception('Payment instructions not available for this payment method');
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $instructions
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
