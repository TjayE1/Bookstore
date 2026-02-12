<?php
/**
 * API: Get Orders for Admin
 * Endpoint: GET /api/admin/get-orders.php
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

// Check authentication
if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    if ($status) {
        $query = "SELECT o.id, o.order_number, o.customer_name, o.customer_email, o.total_amount, 
                 o.status, o.created_at, o.notes, o.shipping_address, o.delivery_cost, o.payment_method, o.payment_status
                  FROM orders o
                  WHERE o.status = ?
                  ORDER BY o.created_at DESC";
        $orders = getRows($query, [$status]);
    } else {
        $query = "SELECT o.id, o.order_number, o.customer_name, o.customer_email, o.total_amount, 
                 o.status, o.created_at, o.notes, o.shipping_address, o.delivery_cost, o.payment_method, o.payment_status
                  FROM orders o
                  ORDER BY o.created_at DESC";
        $orders = getRows($query);
    }
    
    // Load items for each order
    foreach ($orders as &$order) {
        $orderId = (int)$order['id'];
        $itemsQuery = "SELECT product_id, product_name, quantity, unit_price, total_price FROM order_items WHERE order_id = ?";
        $items = getRows($itemsQuery, [$orderId]);
        $order['items'] = $items;
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching orders',
        'error' => $e->getMessage()
    ]);
}

?>
