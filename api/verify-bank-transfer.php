<?php
/**
 * API: Verify Bank Transfer Payment
 * Endpoint: POST /api/verify-bank-transfer.php
 * 
 * This endpoint allows admins to verify and confirm bank transfer payments
 * When called, it updates the order payment_status from 'awaiting_confirmation' to 'completed'
 * 
 * Request body:
 * {
 *   "orderId": 123,
 *   "orderNumber": "ORD-20260210120000-abc123",
 *   "verifyNotes": "Payment confirmed - Reference: 12345678"
 * }
 */

require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email-config.php';

header('Content-Type: application/json; charset=utf-8');

// Validate CORS
validateCORSOrigin();

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$logger = new SecurityLogger('bank-transfer-verification.log');

try {
    // Validate required fields
    $orderId = isset($data['orderId']) ? (int)$data['orderId'] : null;
    $orderNumber = isset($data['orderNumber']) ? Validator::sanitizeText($data['orderNumber']) : null;
    $verifyNotes = isset($data['verifyNotes']) ? Validator::sanitizeText($data['verifyNotes']) : 'Bank transfer verified by admin';
    
    if (!$orderId || !$orderNumber) {
        throw new Exception('Missing required fields: orderId and orderNumber');
    }
    
    // Fetch the order to verify it exists and check its current payment status
    $orderQuery = "SELECT id, order_number, customer_name, customer_email, payment_method, payment_status, total_amount, status FROM orders WHERE id = ? AND order_number = ? LIMIT 1";
    $order = getRow($orderQuery, [$orderId, $orderNumber]);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Check if payment method is bank_transfer
    if ($order['payment_method'] !== 'bank_transfer') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This order was not placed with bank transfer payment method']);
        exit();
    }
    
    // Check if payment_status is awaiting_confirmation
    if ($order['payment_status'] !== 'awaiting_confirmation') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This order payment is already verified or has a different status: ' . $order['payment_status']]);
        exit();
    }
    
    // Database transaction for atomic update
    $conn->begin_transaction();
    
    try {
        // Update payment_status to 'completed'
        $updateQuery = "UPDATE orders SET payment_status = 'completed', notes = CONCAT(COALESCE(notes, ''), '\n[BANK TRANSFER VERIFIED] ', ?) WHERE id = ?";
        $updateStmt = executeQuery($updateQuery, [$verifyNotes, $orderId]);
        
        if (!$updateStmt) {
            throw new Exception('Failed to update order payment status');
        }
        
        // Optionally update order status to 'processing' if it's still 'pending'
        if ($order['status'] === 'pending') {
            $statusUpdateQuery = "UPDATE orders SET status = 'processing' WHERE id = ?";
            executeQuery($statusUpdateQuery, [$orderId]);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log successful verification
        $logger->log('BANK_TRANSFER_VERIFIED', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'customer_email' => $order['customer_email'],
            'amount' => $order['total_amount'],
            'verified_by_ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
        
        // Send confirmation email to customer
        $emailSubject = "Payment Confirmed - Order " . $order['order_number'];
        $emailBody = "
Hello {$order['customer_name']},

Your bank transfer payment has been verified and confirmed! ✅

Order Details:
- Order Number: {$order['order_number']}
- Amount: UGX " . number_format($order['total_amount'], 0) . "
- Status: Payment Confirmed

Your order is now being processed for fulfillment and delivery.

Thank you for shopping with Reader's Haven!

Best regards,
Reader's Haven Team
";
        
        // Send email (using existing email function if available)
        if (function_exists('sendEmail')) {
            sendEmail($order['customer_email'], $emailSubject, $emailBody);
        }
        
        // Return success response with updated order details
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bank transfer payment verified successfully',
            'order' => [
                'id' => $order['id'],
                'orderNumber' => $order['order_number'],
                'customerName' => $order['customer_name'],
                'customerEmail' => $order['customer_email'],
                'totalAmount' => $order['total_amount'],
                'paymentStatus' => 'completed',
                'orderStatus' => $order['status'] === 'pending' ? 'processing' : $order['status']
            ],
            'verificationTime' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error verifying bank transfer: ' . $e->getMessage()
    ]);
    exit();
}
