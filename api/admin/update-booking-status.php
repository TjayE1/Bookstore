<?php
/**
 * API: Update Booking Status
 * Endpoint: POST /api/admin/update-booking-status.php
 * Body: { bookingId, status }
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

$bookingId = $data['bookingId'] ?? $data['id'] ?? null;

if (!isset($bookingId) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($data['status'], $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $query = "UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?";
    executeQuery($query, [$data['status'], (int)$bookingId]);
    
    if (getAffectedRows() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking status updated successfully'
        ]);
    } else {
        // If status didn't change, still return success if booking exists
        $exists = getRow("SELECT id FROM bookings WHERE id = ? LIMIT 1", [(int)$bookingId]);
        if ($exists) {
            echo json_encode([
                'success' => true,
                'message' => 'Booking status updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating booking',
        'error' => $e->getMessage()
    ]);
}

?>
