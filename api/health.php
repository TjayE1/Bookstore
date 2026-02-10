<?php
/**
 * Simple API Health Check
 * Endpoint: GET /api/health.php
 * Returns: { success: true, message: "API is working", timestamp: "..." }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'API is working',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion()
]);
