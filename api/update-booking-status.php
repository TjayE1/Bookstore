<?php
/**
 * UPDATE Booking Status API Endpoint
 * Updates booking status (pending, confirmed, completed, cancelled)
 * Requires: Authentication token in header, booking ID and new status in POST body
 * Returns: Updated booking details
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body']);
        exit();
    }

    // Validate required fields
    $bookingId = isset($input['id']) ? (int)$input['id'] : null;
    $newStatus = isset($input['status']) ? $input['status'] : null;
    $notes = isset($input['notes']) ? $input['notes'] : null;

    if (!$bookingId || !$newStatus) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: id and status']);
        exit();
    }

    // Validate status value
    $validator = new Validator();
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!$validator->validateStatus($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status. Must be: ' . implode(', ', $validStatuses)]);
        exit();
    }

    // Validate notes if provided
    if ($notes) {
        $notes = $validator->sanitizeText($notes);
        if (strlen($notes) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Notes cannot exceed 1000 characters']);
            exit();
        }
    }

    // Check if booking exists
    $checkStmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ?");
    $checkStmt->bind_param('i', $bookingId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $booking = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        exit();
    }

    // Update booking
    $updateQuery = "UPDATE bookings SET status = ?, updated_at = CURRENT_TIMESTAMP";
    $params = [$newStatus];
    $types = 's';

    if ($notes) {
        $updateQuery .= ", notes = ?";
        $params[] = $notes;
        $types .= 's';
    }

    $updateQuery .= " WHERE id = ?";
    $params[] = $bookingId;
    $types .= 'i';

    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $updateStmt->bind_param($types, ...$params);

    if (!$updateStmt->execute()) {
        throw new Exception('Update failed: ' . $updateStmt->error);
    }

    $updateStmt->close();

    // Fetch updated booking
    $fetchStmt = $conn->prepare(
        "SELECT id, booking_number, customer_name, customer_email, customer_phone, 
                booking_date, booking_time, notes, status, created_at, updated_at 
         FROM bookings WHERE id = ?"
    );
    $fetchStmt->bind_param('i', $bookingId);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
    $updatedBooking = $fetchResult->fetch_assoc();
    $fetchStmt->close();

    if ($updatedBooking) {
        $updatedBooking = [
            'id' => (int)$updatedBooking['id'],
            'booking_number' => $updatedBooking['booking_number'],
            'customer_name' => $updatedBooking['customer_name'],
            'customer_email' => $updatedBooking['customer_email'],
            'customer_phone' => $updatedBooking['customer_phone'],
            'booking_date' => $updatedBooking['booking_date'],
            'booking_time' => $updatedBooking['booking_time'],
            'notes' => $updatedBooking['notes'],
            'status' => $updatedBooking['status'],
            'created_at' => $updatedBooking['created_at'],
            'updated_at' => $updatedBooking['updated_at']
        ];
    }

    // Log the update
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('info', "Booking {$bookingId} status updated from {$booking['status']} to {$newStatus}");
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully',
        'data' => $updatedBooking
    ]);

} catch (Exception $e) {
    // Log error
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('error', 'Failed to update booking status: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to update booking status']);
}

$conn->close();
?>
