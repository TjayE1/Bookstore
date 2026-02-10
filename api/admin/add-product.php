<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    if (empty($data['name'])) {
        throw new Exception('Product name is required');
    }
    
    if (!isset($data['price']) || $data['price'] <= 0) {
        throw new Exception('Valid price is required');
    }
    
    // Sanitize inputs
    $name = trim($data['name']);
    $price = (int)$data['price'];
    $description = isset($data['description']) ? trim($data['description']) : '';
    $category = isset($data['category']) ? trim($data['category']) : 'Journals';
    $imageUrl = isset($data['imageUrl']) ? trim($data['imageUrl']) : '';
    $emoji = isset($data['emoji']) ? trim($data['emoji']) : '📔';
    
    // Insert product
    $query = "INSERT INTO products (name, price, description, category, image_url, emoji, in_stock, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('sissss', $name, $price, $description, $category, $imageUrl, $emoji);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully',
            'productId' => $newId
        ]);
    } else {
        throw new Exception('Failed to add product: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
