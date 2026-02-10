<?php
/**
 * GET Orders API Endpoint
 * Retrieves all orders with their items from database with optional filtering
 * Requires: Authentication token in header
 * Returns: JSON array of orders with line items
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

    // Build query for orders
    $query = "SELECT * FROM orders WHERE 1=1";
    $params = [];
    $types = '';

    // Filter by status if provided
    if ($status) {
        $validator = new Validator();
        if (!$validator->validateStatus($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status filter']);
            exit();
        }
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    }

    // Search by order number, customer name or email if provided
    if ($search) {
        $validator = new Validator();
        $searchTerm = $validator->sanitizeText($search);
        $query .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $types .= 'sss';
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
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orderId = (int)$row['id'];

        // Get order items for this order
        $itemsStmt = $conn->prepare(
            "SELECT id, product_id, product_name, quantity, unit_price, total_price 
             FROM order_items WHERE order_id = ? ORDER BY id"
        );
        $itemsStmt->bind_param('i', $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $items = [];

        while ($itemRow = $itemsResult->fetch_assoc()) {
            $items[] = [
                'id' => (int)$itemRow['id'],
                'product_id' => (int)$itemRow['product_id'],
                'product_name' => $itemRow['product_name'],
                'quantity' => (int)$itemRow['quantity'],
                'unit_price' => (float)$itemRow['unit_price'],
                'total_price' => (float)$itemRow['total_price']
            ];
        }
        $itemsStmt->close();

        $orders[] = [
            'id' => $orderId,
            'order_number' => $row['order_number'],
            'customer_id' => (int)$row['customer_id'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'total_amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'payment_method' => $row['payment_method'],
            'payment_status' => $row['payment_status'],
            'shipping_address' => $row['shipping_address'],
            'notes' => $row['notes'],
            'items' => $items,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM orders WHERE 1=1";
    $countParams = [];
    $countTypes = '';

    if ($status) {
        $countQuery .= " AND status = ?";
        $countParams[] = $status;
        $countTypes .= 's';
    }

    if ($search) {
        $countQuery .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
        $countParams[] = "%$searchTerm%";
        $countParams[] = "%$searchTerm%";
        $countParams[] = "%$searchTerm%";
        $countTypes .= 'sss';
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
        $total = count($orders);
    }

    $stmt->close();

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $orders,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($orders)
        ]
    ]);

} catch (Exception $e) {
    // Log error
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('error', 'Failed to retrieve orders: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve orders']);
}

$conn->close();
?>
