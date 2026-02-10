<?php
/**
 * API: Get Order Details
 * Endpoint: GET /api/admin/get-order-details.php?orderId=ID
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$orderNumber = isset($_GET['orderNumber']) ? trim($_GET['orderNumber']) : '';
$orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : 0;

if (!$orderId && !$orderNumber) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID or Order Number required']);
    exit();
}

// Log the request for debugging
error_log("get-order-details.php: Fetching order ID: " . $orderId);

try {
    // Get order - try with customer details first, fallback to basic query
    $order = null;
    try {
        $orderQuery = "SELECT o.*, c.phone AS customer_phone, c.address AS customer_address 
                       FROM orders o
                       LEFT JOIN customers c ON o.customer_id = c.id
                       WHERE " . ($orderNumber ? "TRIM(o.order_number) = ?" : "o.id = ?");
        $order = getRow($orderQuery, [$orderNumber ? $orderNumber : $orderId]);
        error_log("get-order-details.php: Query with customer details executed");
    } catch (Exception $e) {
        // Fallback if phone/address columns don't exist
        error_log("get-order-details.php: Customer columns error, falling back: " . $e->getMessage());
        $orderQuery = "SELECT o.* FROM orders o WHERE " . ($orderNumber ? "TRIM(o.order_number) = ?" : "o.id = ?");
        $order = getRow($orderQuery, [$orderNumber ? $orderNumber : $orderId]);
        error_log("get-order-details.php: Fallback query executed");
    }

    // Fallback: try ID if orderNumber lookup failed
    if (!$order && $orderNumber && $orderId) {
        $orderQuery = "SELECT o.* FROM orders o WHERE o.id = ?";
        $order = getRow($orderQuery, [$orderId]);
        error_log("get-order-details.php: Fallback to orderId executed");
    }
    
    error_log("get-order-details.php: Order found: " . ($order ? 'yes' : 'no'));
    
    if (!$order) {
        error_log("get-order-details.php: Order not found in database");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $itemsQuery = "SELECT * FROM order_items WHERE order_id = ?";
    $items = getRows($itemsQuery, [$orderId]);
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching order',
        'error' => $e->getMessage()
    ]);
}

?>
