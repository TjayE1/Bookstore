<?php
/**
 * GET Delivery Options API Endpoint
 * Retrieves all active delivery methods with pricing
 * Public endpoint - no authentication required
 * Returns: Array of delivery options
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once '../config/database.php';

try {
    // Get active delivery options
    $query = "SELECT id, name, description, delivery_time_min, delivery_time_max, cost 
              FROM delivery_options 
              WHERE is_active = TRUE 
              ORDER BY cost ASC";

    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $deliveryOptions = [];
    while ($row = $result->fetch_assoc()) {
        $deliveryOptions[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'delivery_time_min' => (int)$row['delivery_time_min'],
            'delivery_time_max' => (int)$row['delivery_time_max'],
            'cost' => (float)$row['cost']
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $deliveryOptions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to retrieve delivery options',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
