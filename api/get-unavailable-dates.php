<?php
/**
 * API: Get Unavailable Dates
 * Endpoint: GET /api/get-unavailable-dates.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

try {
    $query = "SELECT id, unavailable_date, reason FROM unavailable_dates ORDER BY unavailable_date ASC";
    $dates = getRows($query);
    
    $unavailableDates = [];
    foreach ($dates as $date) {
        $unavailableDates[] = [
            'id' => $date['id'],
            'date' => $date['unavailable_date'],
            'reason' => $date['reason']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'unavailable_dates' => $unavailableDates
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching unavailable dates',
        'error' => $e->getMessage()
    ]);
}

?>
