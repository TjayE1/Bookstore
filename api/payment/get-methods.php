<?php
/**
 * API: Get Payment Methods
 * Endpoint: GET /api/payment/get-methods.php
 * Returns: Available payment methods for checkout
 */

require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/payment-config.php';

header('Content-Type: application/json; charset=utf-8');
validateCORSOrigin();

try {
    $methods = getEnabledPaymentMethods();
    
    $response = [];
    foreach ($methods as $key => $method) {
        $response[] = [
            'id' => $key,
            'name' => $method['name'],
            'icon' => $method['icon'],
            'description' => $method['description'],
            'requiresGateway' => $method['requires_gateway'] ?? false,
            'provider' => $method['provider'] ?? null,
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching payment methods: ' . $e->getMessage()
    ]);
}
