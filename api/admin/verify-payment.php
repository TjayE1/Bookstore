<?php
/**
 * API: Verify Payment (Admin)
 * Endpoint: POST /api/admin/verify-payment.php
 * Allows admin to mark manual payments as verified
 */

require_once __DIR__ . '/../../includes/security-headers.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/email-config.php';
require_once __DIR__ . '/../../includes/send-email.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
validateCORSOrigin();

// Check admin authentication
if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$logger = new SecurityLogger('payment_verification.log');

try {
    // Validate required fields
    if (!isset($data['orderId']) || empty($data['orderId'])) {
        throw new Exception('Order ID is required');
    }
    
    $orderId = Validator::integer($data['orderId'], 1, 999999999);
    if ($orderId === false) {
        throw new Exception('Invalid order ID');
    }
    
    // Optional verification details
    $verificationNotes = isset($data['notes']) ? Validator::sanitizeText($data['notes']) : '';
    $paymentReference = isset($data['reference']) ? Validator::sanitizeText($data['reference']) : '';
    $paymentDate = isset($data['paymentDate']) ? Validator::sanitizeText($data['paymentDate']) : date('Y-m-d H:i:s');
    $verifiedAmount = isset($data['amount']) ? (float)$data['amount'] : null;
    
    // Get order details
    $orderQuery = "SELECT id, order_number, customer_name, customer_email, total_amount, payment_method, payment_status 
                   FROM orders WHERE id = ? LIMIT 1";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Check if order payment is already verified
    if ($order['payment_status'] === 'completed') {
        throw new Exception('Payment already verified for this order');
    }
    
    // Verify amount if provided
    if ($verifiedAmount !== null && abs($verifiedAmount - $order['total_amount']) > 0.01) {
        throw new Exception('Payment amount mismatch. Expected: UGX ' . number_format($order['total_amount']) . 
                          ', Received: UGX ' . number_format($verifiedAmount));
    }
    
    // Update order payment status
    $updateQuery = "UPDATE orders 
                    SET payment_status = 'completed',
                        payment_verified_at = NOW(),
                        payment_verification_notes = ?,
                        payment_reference = ?
                    WHERE id = ?";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('ssi', $verificationNotes, $paymentReference, $orderId);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update payment status');
    }
    
    // Log the verification
    $logger->log('INFO', "Payment verified for order #{$order['order_number']} by admin", [
        'order_id' => $orderId,
        'amount' => $order['total_amount'],
        'payment_method' => $order['payment_method'],
        'reference' => $paymentReference,
        'admin_user' => $_SESSION['adminUsername'] ?? 'admin'
    ]);
    
    // Send payment confirmation email to customer
    try {
        $emailBody = "Dear {$order['customer_name']},\n\n";
        $emailBody .= "Your payment has been verified and confirmed!\n\n";
        $emailBody .= "Order Number: {$order['order_number']}\n";
        $emailBody .= "Amount Paid: UGX " . number_format($order['total_amount']) . "\n";
        $emailBody .= "Payment Method: " . ucwords(str_replace('_', ' ', $order['payment_method'])) . "\n";
        if ($paymentReference) {
            $emailBody .= "Reference: {$paymentReference}\n";
        }
        $emailBody .= "\nYour order is now being processed and will be shipped soon.\n\n";
        $emailBody .= "Thank you for your payment!\n\n";
        $emailBody .= "Best regards,\nReader's Haven";
        
        sendEmail(
            $order['customer_email'],
            $order['customer_name'],
            'Payment Confirmed - Order ' . $order['order_number'],
            $emailBody
        );
    } catch (Exception $emailError) {
        // Log email error but don't fail the verification
        $logger->log('WARNING', "Failed to send payment confirmation email", [
            'order_id' => $orderId,
            'error' => $emailError->getMessage()
        ]);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'data' => [
            'orderId' => $orderId,
            'orderNumber' => $order['order_number'],
            'verifiedAt' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    $logger->log('ERROR', 'Payment verification failed', [
        'error' => $e->getMessage(),
        'order_id' => $orderId ?? null
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
