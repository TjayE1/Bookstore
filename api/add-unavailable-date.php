<?php
/**
 * ADD Unavailable Date API Endpoint
 * Creates a new unavailable date record in database (blocks a date for counselling)
 * Requires: Authentication, date and optional reason in POST body
 * Returns: Created date ID and details
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
    $unavailableDate = isset($input['unavailable_date']) ? $input['unavailable_date'] : null;
    $reason = isset($input['reason']) ? $input['reason'] : null;

    if (!$unavailableDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required field: unavailable_date']);
        exit();
    }

    // Validate date format
    $validator = new Validator();
    if (!$validator->validateDate($unavailableDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format']);
        exit();
    }

    // Validate reason if provided
    if ($reason) {
        $reason = $validator->sanitizeText($reason);
        if (strlen($reason) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'Reason cannot exceed 255 characters']);
            exit();
        }
    }

    // Check if date is already unavailable
    $checkStmt = $conn->prepare("SELECT id FROM unavailable_dates WHERE unavailable_date = ?");
    $checkStmt->bind_param('s', $unavailableDate);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'This date is already blocked']);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();

    // Insert unavailable date
    $insertStmt = $conn->prepare(
        "INSERT INTO unavailable_dates (unavailable_date, reason) VALUES (?, ?)"
    );
    if (!$insertStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $insertStmt->bind_param('ss', $unavailableDate, $reason);

    if (!$insertStmt->execute()) {
        throw new Exception('Insert failed: ' . $insertStmt->error);
    }

    $dateId = $insertStmt->insert_id;
    $insertStmt->close();

    // Log the creation
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('info', "Unavailable date {$unavailableDate} created (ID: {$dateId})");
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Date blocked successfully',
        'id' => (int)$dateId,
        'data' => [
            'id' => (int)$dateId,
            'unavailable_date' => $unavailableDate,
            'reason' => $reason
        ]
    ]);

} catch (Exception $e) {
    // Log error
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('error', 'Failed to create unavailable date: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to block date']);
}

$conn->close();
?>
