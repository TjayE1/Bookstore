<?php
/**
 * API: Get Available Time Slots for a Date
 * Endpoint: GET /api/get-available-slots.php?date=YYYY-MM-DD
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$date = isset($_GET['date']) ? $_GET['date'] : null;

if (!$date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date parameter required']);
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

try {
    // Check if date is a weekend
    $dateObj = new DateTime($date);
    $dayOfWeek = $dateObj->format('N'); // 1=Monday, 7=Sunday
    
    if ($dayOfWeek == 6 || $dayOfWeek == 7) {
        echo json_encode([
            'success' => false,
            'message' => 'This date is a weekend',
            'available_slots' => []
        ]);
        exit();
    }
    
    // Check if date is unavailable
    $unavailableQuery = "SELECT id FROM unavailable_dates WHERE unavailable_date = ?";
    $isUnavailable = getRow($unavailableQuery, [$date]);
    
    if ($isUnavailable) {
        echo json_encode([
            'success' => false,
            'message' => 'This date is not available',
            'available_slots' => []
        ]);
        exit();
    }
    
    // Available time slots
    $availableSlots = [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
        '11:00', '11:30', '14:00', '14:30', '15:00', '15:30',
        '16:00', '16:30', '17:00', '17:30'
    ];
    
    // Get booked slots for this date
    $bookedQuery = "SELECT booking_time FROM bookings WHERE booking_date = ? AND status IN ('pending', 'confirmed')";
    $bookedSlots = getRows($bookedQuery, [$date]);
    
    $bookedTimes = array_column($bookedSlots, 'booking_time');
    
    // Filter available slots
    $availableSlots = array_filter($availableSlots, function($slot) use ($bookedTimes) {
        return !in_array($slot, $bookedTimes);
    });
    
    echo json_encode([
        'success' => true,
        'date' => $date,
        'available_slots' => array_values($availableSlots)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching available slots',
        'error' => $e->getMessage()
    ]);
}

?>
