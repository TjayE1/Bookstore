<?php
/**
 * GET Bookings API Endpoint
 * Retrieves all bookings from database with optional filtering
 * Requires: Authentication token in header
 * Returns: JSON array of bookings
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

    // Get optional filters from query parameters
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // Validate limit and offset
    $limit = min($limit, 500); // Max 500 records per request
    $limit = max($limit, 1);   // Min 1 record
    $offset = max($offset, 0);

    // Build query
    $query = "SELECT * FROM bookings WHERE 1=1";
    $params = [];
    $types = '';

    // Filter by status if provided
    if ($status) {
        $validator = new Validator();
        if (!$validator->validateStatus($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status filter']);
            exit();
        }
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    }

    // Search by name or email if provided
    if ($search) {
        $validator = new Validator();
        $searchTerm = $validator->sanitizeText($search);
        $query .= " AND (customer_name LIKE ? OR customer_email LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $types .= 'ss';
    }

    // Add ordering and pagination
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    // Prepare and execute statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    // Bind parameters
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }

    // Execute
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    // Get results
    $result = $stmt->get_result();
    $bookings = [];

    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'id' => (int)$row['id'],
            'booking_number' => $row['booking_number'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'customer_phone' => $row['customer_phone'],
            'booking_date' => $row['booking_date'],
            'booking_time' => $row['booking_time'],
            'notes' => $row['notes'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM bookings WHERE 1=1";
    $countParams = [];
    $countTypes = '';

    if ($status) {
        $countQuery .= " AND status = ?";
        $countParams[] = $status;
        $countTypes .= 's';
    }

    if ($search) {
        $countQuery .= " AND (customer_name LIKE ? OR customer_email LIKE ?)";
        $countParams[] = "%$searchTerm%";
        $countParams[] = "%$searchTerm%";
        $countTypes .= 'ss';
    }

    $countStmt = $conn->prepare($countQuery);
    if ($countStmt) {
        if (count($countParams) > 0) {
            $countStmt->bind_param($countTypes, ...$countParams);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $total = (int)$countRow['total'];
        $countStmt->close();
    } else {
        $total = count($bookings);
    }

    $stmt->close();

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $bookings,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($bookings)
        ]
    ]);

} catch (Exception $e) {
    // Log error
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('error', 'Failed to retrieve bookings: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve bookings']);
}

$conn->close();
?>
