<?php
/**
 * DELETE Booking API Endpoint
 * Deletes a booking record from database
 * Requires: Authentication, booking ID in POST body
 * Returns: Success confirmation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/csrf.php';

// Check authentication
require_once '../includes/auth.php';

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - Please log in']);
        exit();
    }

    // Get request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required field: id']);
        exit();
    }

    $bookingId = (int)$input['id'];

    // Check if booking exists
    $checkStmt = $conn->prepare("SELECT id FROM bookings WHERE id = ?");
    $checkStmt->bind_param('i', $bookingId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();

    // Delete booking
    $deleteStmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    if (!$deleteStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $deleteStmt->bind_param('i', $bookingId);

    if (!$deleteStmt->execute()) {
        throw new Exception('Delete failed: ' . $deleteStmt->error);
    }

    $deleteStmt->close();

    // Log the deletion
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('info', "Booking {$bookingId} deleted");
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Booking deleted successfully'
    ]);

} catch (Exception $e) {
    // Log error
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('error', 'Failed to delete booking: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete booking']);
}

$conn->close();
?>
