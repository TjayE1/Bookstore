<?php
/**
 * UPDATE Order Status API Endpoint
 * Updates order status (pending, processing, shipped, delivered, cancelled)
 * Requires: Authentication token in header, order ID and new status in POST body
 * Returns: Updated order details with items
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/csrf.php';

// Check authentication
require_once '../includes/auth.php';

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - Please log in']);
        exit();
    }

    // Get request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body']);
        exit();
    }

    // Validate required fields
    $orderId = isset($input['id']) ? (int)$input['id'] : null;
    $newStatus = isset($input['status']) ? $input['status'] : null;
    $paymentStatus = isset($input['payment_status']) ? $input['payment_status'] : null;
    $notes = isset($input['notes']) ? $input['notes'] : null;

    if (!$orderId || !$newStatus) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: id and status']);
        exit();
    }

    // Validate status value
    $validator = new Validator();
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!$validator->validateStatus($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status. Must be: ' . implode(', ', $validStatuses)]);
        exit();
    }

    // Validate payment status if provided
    if ($paymentStatus) {
        $validPaymentStatuses = ['pending', 'completed', 'failed', 'refunded'];
        if (!$validator->validateStatus($paymentStatus, $validPaymentStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payment_status. Must be: ' . implode(', ', $validPaymentStatuses)]);
            exit();
        }
    }

    // Validate notes if provided
    if ($notes) {
        $notes = $validator->sanitizeText($notes);
        if (strlen($notes) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Notes cannot exceed 1000 characters']);
            exit();
        }
    }

    // Check if order exists
    $checkStmt = $conn->prepare("SELECT id, status, payment_status FROM orders WHERE id = ?");
    $checkStmt->bind_param('i', $orderId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $order = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update order
        $updateQuery = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$newStatus];
        $types = 's';

        if ($paymentStatus) {
            $updateQuery .= ", payment_status = ?";
            $params[] = $paymentStatus;
            $types .= 's';
        }

        if ($notes) {
            $updateQuery .= ", notes = ?";
            $params[] = $notes;
            $types .= 's';
        }

        $updateQuery .= " WHERE id = ?";
        $params[] = $orderId;
        $types .= 'i';

        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }

        $updateStmt->bind_param($types, ...$params);

        if (!$updateStmt->execute()) {
            throw new Exception('Update failed: ' . $updateStmt->error);
        }

        $updateStmt->close();

        // Commit transaction
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

    // Fetch updated order with items
    $fetchStmt = $conn->prepare(
        "SELECT id, order_number, customer_id, customer_name, customer_email, 
                total_amount, status, payment_method, payment_status, shipping_address, 
                notes, created_at, updated_at 
         FROM orders WHERE id = ?"
    );
    $fetchStmt->bind_param('i', $orderId);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
    $updatedOrder = $fetchResult->fetch_assoc();
    $fetchStmt->close();

    // Get order items
    $itemsStmt = $conn->prepare(
        "SELECT id, product_id, product_name, quantity, unit_price, total_price 
         FROM order_items WHERE order_id = ? ORDER BY id"
    );
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    $items = [];

    while ($itemRow = $itemsResult->fetch_assoc()) {
        $items[] = [
            'id' => (int)$itemRow['id'],
            'product_id' => (int)$itemRow['product_id'],
            'product_name' => $itemRow['product_name'],
            'quantity' => (int)$itemRow['quantity'],
            'unit_price' => (float)$itemRow['unit_price'],
            'total_price' => (float)$itemRow['total_price']
        ];
    }
    $itemsStmt->close();

    if ($updatedOrder) {
        $updatedOrder = [
            'id' => (int)$updatedOrder['id'],
            'order_number' => $updatedOrder['order_number'],
            'customer_id' => (int)$updatedOrder['customer_id'],
            'customer_name' => $updatedOrder['customer_name'],
            'customer_email' => $updatedOrder['customer_email'],
            'total_amount' => (float)$updatedOrder['total_amount'],
            'status' => $updatedOrder['status'],
            'payment_method' => $updatedOrder['payment_method'],
            'payment_status' => $updatedOrder['payment_status'],
            'shipping_address' => $updatedOrder['shipping_address'],
            'notes' => $updatedOrder['notes'],
            'items' => $items,
            'created_at' => $updatedOrder['created_at'],
            'updated_at' => $updatedOrder['updated_at']
        ];
    }

    // Log the update
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('info', "Order {$orderId} status updated from {$order['status']} to {$newStatus}");
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'data' => $updatedOrder
    ]);

} catch (Exception $e) {
    // Log error
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('error', 'Failed to update order status: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to update order status']);
}

$conn->close();
?>
