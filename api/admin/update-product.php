<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    if (empty($data['productId']) || empty($data['name'])) {
        throw new Exception('Product ID and name are required');
    }
    
    if (!isset($data['price']) || $data['price'] <= 0) {
        throw new Exception('Valid price is required');
    }
    
    $productId = (int)$data['productId'];
    $name = trim($data['name']);
    $price = (int)$data['price'];
    $description = isset($data['description']) ? trim($data['description']) : '';
    $category = isset($data['category']) ? trim($data['category']) : 'Journals';
    $inStock = isset($data['inStock']) ? (int)$data['inStock'] : 1;
    $imageUrl = isset($data['imageUrl']) ? trim($data['imageUrl']) : '';
    $emoji = isset($data['emoji']) ? trim($data['emoji']) : '📔';
    
    // Update product
    $query = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, in_stock = ?, image_url = ?, emoji = ? WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('ssissssi', $name, $description, $price, $category, $inStock, $imageUrl, $emoji, $productId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update product: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
