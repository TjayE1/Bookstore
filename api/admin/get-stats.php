<?php
/**
 * API: Get Dashboard Statistics
 * Endpoint: GET /api/admin/get-stats.php
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

if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Total orders
    $totalOrdersQuery = "SELECT COUNT(*) as count FROM orders";
    $totalOrders = getRow($totalOrdersQuery)['count'];
    
    // Pending orders
    $pendingOrdersQuery = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $pendingOrders = getRow($pendingOrdersQuery)['count'];
    
    // Total revenue
    $totalRevenueQuery = "SELECT SUM(total_amount) as total FROM orders WHERE status IN ('processing', 'shipped', 'delivered')";
    $totalRevenue = getRow($totalRevenueQuery)['total'] ?? 0;
    
    // Total bookings
    $totalBookingsQuery = "SELECT COUNT(*) as count FROM bookings";
    $totalBookings = getRow($totalBookingsQuery)['count'];
    
    // Pending bookings
    $pendingBookingsQuery = "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'";
    $pendingBookings = getRow($pendingBookingsQuery)['count'];
    
    // Total customers
    $totalCustomersQuery = "SELECT COUNT(*) as count FROM customers";
    $totalCustomers = getRow($totalCustomersQuery)['count'];
    
    // Recent orders
    $recentOrdersQuery = "SELECT id, order_number, customer_name, total_amount, status, created_at 
                         FROM orders ORDER BY created_at DESC LIMIT 5";
    $recentOrders = getRows($recentOrdersQuery);
    
    // Upcoming bookings
    $upcomingBookingsQuery = "SELECT id, customer_name, booking_date, booking_time, status 
                             FROM bookings WHERE booking_date >= CURDATE() 
                             ORDER BY booking_date ASC, booking_time ASC LIMIT 5";
    $upcomingBookings = getRows($upcomingBookingsQuery);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalOrders' => (int)$totalOrders,
            'pendingOrders' => (int)$pendingOrders,
            'totalRevenue' => (float)$totalRevenue,
            'totalBookings' => (int)$totalBookings,
            'pendingBookings' => (int)$pendingBookings,
            'totalCustomers' => (int)$totalCustomers
        ],
        'recentOrders' => $recentOrders,
        'upcomingBookings' => $upcomingBookings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching statistics',
        'error' => $e->getMessage()
    ]);
}

?>
