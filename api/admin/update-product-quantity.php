<?php
/**
 * API: Update Product Quantity in Inventory
 * Endpoint: POST /api/admin/update-product-quantity.php
 * Body: { productId, quantity }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['productId']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$productId = (int)$data['productId'];
$quantity = (int)$data['quantity'];

if ($quantity < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quantity cannot be negative']);
    exit();
}

try {
    error_log("Update Qty Request: productId=$productId, quantity=$quantity");
    
    // First, ensure inventory record exists for this product
    $checkQuery = "SELECT id FROM inventory WHERE product_id = ?";
    $existing = getRow($checkQuery, [$productId]);
    
    if (!$existing) {
        error_log("Creating new inventory record for product $productId");
        // Create inventory record if it doesn't exist
        $insertQuery = "INSERT INTO inventory (product_id, quantity_in_stock, quantity_reserved, reorder_level) VALUES (?, ?, 0, 10)";
        executeQuery($insertQuery, [$productId, $quantity]);
    } else {
        error_log("Updating existing inventory record for product $productId");
        // Update existing inventory record
        $query = "UPDATE inventory SET quantity_in_stock = ? WHERE product_id = ?";
        executeQuery($query, [$quantity, $productId]);
        error_log("Affected rows: " . getAffectedRows());
    }
    
    // Update product in_stock status based on quantity
    $inStock = $quantity > 0 ? 1 : 0;
    $updateProductQuery = "UPDATE products SET in_stock = ? WHERE id = ?";
    executeQuery($updateProductQuery, [$inStock, $productId]);
    error_log("Updated product in_stock status to $inStock");
    
    // Verify the update
    $verifyQuery = "SELECT quantity_in_stock FROM inventory WHERE product_id = ?";
    $verify = getRow($verifyQuery, [$productId]);
    error_log("Verification - inventory qty in DB: " . ($verify ? $verify['quantity_in_stock'] : 'NULL'));
    
    echo json_encode([
        'success' => true,
        'message' => 'Product quantity updated successfully',
        'quantity' => $quantity,
        'verified' => $verify
    ]);
    
} catch (Exception $e) {
    error_log("Error in update-product-quantity: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating product quantity',
        'error' => $e->getMessage()
    ]);
}

?>
