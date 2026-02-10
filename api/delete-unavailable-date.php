<?php
/**
 * DELETE Unavailable Date API Endpoint
 * Deletes an unavailable date record from database (unblocks a date)
 * Requires: Authentication, date ID in POST body
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

    $dateId = (int)$input['id'];

    // Check if unavailable date exists
    $checkStmt = $conn->prepare("SELECT id FROM unavailable_dates WHERE id = ?");
    $checkStmt->bind_param('i', $dateId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Unavailable date not found']);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();

    // Delete unavailable date
    $deleteStmt = $conn->prepare("DELETE FROM unavailable_dates WHERE id = ?");
    if (!$deleteStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $deleteStmt->bind_param('i', $dateId);

    if (!$deleteStmt->execute()) {
        throw new Exception('Delete failed: ' . $deleteStmt->error);
    }

    $deleteStmt->close();

    // Log the deletion
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('info', "Unavailable date {$dateId} unblocked");
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Date unblocked successfully'
    ]);

} catch (Exception $e) {
    // Log error
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('error', 'Failed to unblock date: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to unblock date']);
}

$conn->close();
?>
