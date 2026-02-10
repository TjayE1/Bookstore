<?php
/**
 * API: Add Unavailable Date
 * Endpoint: POST /api/admin/add-unavailable-date.php
 * Body: { date, reason }
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

if (!isset($data['date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

try {
    $query = "INSERT INTO unavailable_dates (unavailable_date, reason) VALUES (?, ?)";
    executeQuery($query, [
        $data['date'],
        htmlspecialchars($data['reason'] ?? 'Blocked')
    ]);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Unavailable date added successfully'
    ]);
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This date is already blocked']);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error adding unavailable date',
            'error' => $e->getMessage()
        ]);
    }
}

?>
