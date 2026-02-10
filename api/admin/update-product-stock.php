<?php
/**
 * Admin: Update Product Stock Status
 * Endpoint: POST /api/admin/update-product-stock.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['productId']) || !isset($data['inStock'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$productId = (int)$data['productId'];
$inStock = (int)$data['inStock'];

try {
    $query = "UPDATE products SET in_stock = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $inStock, $productId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product stock status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update product');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
