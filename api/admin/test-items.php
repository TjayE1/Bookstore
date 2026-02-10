<?php
/**
 * TEST: Check if order items exist in database
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    // Check total items in database
    $totalQuery = "SELECT COUNT(*) as total FROM order_items";
    $totalResult = getRow($totalQuery);
    
    // Get all items
    $allItemsQuery = "SELECT order_id, product_id, product_name, quantity, unit_price, total_price FROM order_items LIMIT 20";
    $allItems = getRows($allItemsQuery);
    
    // Get all orders
    $ordersQuery = "SELECT id, order_number FROM orders LIMIT 5";
    $allOrders = getRows($ordersQuery);
    
    echo json_encode([
        'success' => true,
        'total_items_in_db' => $totalResult['total'] ?? 0,
        'sample_items' => $allItems,
        'sample_orders' => $allOrders
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
