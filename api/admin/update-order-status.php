<?php
/**
 * API: Update Order Status
 * Endpoint: POST /api/admin/update-order-status.php
 * Body: { orderId, status }
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

if (!isset($data['orderId']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($data['status'], $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    executeQuery($query, [$data['status'], (int)$data['orderId']]);
    
    if (getAffectedRows() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating order',
        'error' => $e->getMessage()
    ]);
}

?>
