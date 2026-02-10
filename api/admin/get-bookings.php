<?php
/**
 * API: Get Bookings for Admin
 * Endpoint: GET /api/admin/get-bookings.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check authentication
if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    
    $query = "SELECT id, booking_number, customer_name, customer_email, booking_date, booking_time, 
                     status, created_at FROM bookings WHERE 1=1";
    $params = [];
    
    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
    }
    
    if ($startDate) {
        $query .= " AND booking_date >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND booking_date <= ?";
        $params[] = $endDate;
    }
    
    $query .= " ORDER BY booking_date ASC, booking_time ASC";
    
    $bookings = !empty($params) ? getRows($query, $params) : getRows($query);
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching bookings',
        'error' => $e->getMessage()
    ]);
}

?>
