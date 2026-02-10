<?php
/**
 * API: Get All Products
 * Endpoint: GET /api/get-products.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Return all products with inventory info
    $query = "SELECT p.id, p.name, p.description, p.price, p.category, p.image_url, p.emoji, p.in_stock,
              COALESCE(i.quantity_in_stock, 0) as quantity_in_stock,
              COALESCE(i.quantity_available, 0) as quantity_available
              FROM products p
              LEFT JOIN inventory i ON p.id = i.product_id
              ORDER BY p.category, p.name";
    $products = getRows($query);
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching products',
        'error' => $e->getMessage()
    ]);
}

?>
