<?php
/**
 * API: Delete Booking
 * Endpoint: DELETE /api/admin/delete-booking.php
 * Body: { bookingId }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
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
$bookingNumber = $data['bookingNumber'] ?? null;

if (!isset($bookingId) && !isset($bookingNumber)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID or number required']);
    exit();
}

try {
    $deleted = 0;

    if (isset($bookingId)) {
        $query = "DELETE FROM bookings WHERE id = ?";
        executeQuery($query, [(int)$bookingId]);
        $deleted = getAffectedRows();
    }

    if ($deleted === 0 && !empty($bookingNumber)) {
        $query = "DELETE FROM bookings WHERE booking_number = ?";
        executeQuery($query, [$bookingNumber]);
        $deleted = getAffectedRows();
    }
    
    if ($deleted > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting booking',
        'error' => $e->getMessage()
    ]);
}

?>
