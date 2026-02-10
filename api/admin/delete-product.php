<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['productId'])) {
        throw new Exception('Product ID is required');
    }
    
    $productId = (int)$data['productId'];
    
    // Delete product
    $query = "DELETE FROM products WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $productId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete product: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
