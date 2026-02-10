<?php
/**
 * API: Create PayPal Order
 * Endpoint: POST /api/create-paypal-order.php
 */

require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/paypal-config.php';

header('Content-Type: application/json; charset=utf-8');
validateCORSOrigin();

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }

    // Validate required fields
    if (!isset($data['total']) || !isset($data['items'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: total and items']);
        exit();
    }

    $total = is_numeric($data['total']) ? (float)$data['total'] : 0;
    if ($total <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid total amount']);
        exit();
    }

    // Ensure PayPal is configured
    if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_SECRET)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'PayPal credentials are missing in config/paypal-config.php'
        ]);
        exit();
    }

    // Get access token
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) {
        $errorMessage = getPayPalLastAuthError() ?: 'Unable to authenticate with PayPal';
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit();
    }

    // Build PayPal order payload
    $orderPayload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => PAYPAL_CURRENCY,
                    'value' => number_format($total, 2, '.', '')
                ]
            ]
        ],
        'application_context' => [
            'return_url' => PAYPAL_RETURN_URL,
            'cancel_url' => PAYPAL_CANCEL_URL,
            'brand_name' => PAYPAL_BUSINESS_NAME,
            'landing_page' => 'LOGIN',
            'user_action' => 'PAY_NOW'
        ]
    ];

    // Create order via PayPal API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderPayload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 201) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'PayPal order creation failed. HTTP ' . $httpCode,
            'details' => $response
        ]);
        exit();
    }

    $orderData = json_decode($response, true);
    $approvalUrl = null;

    if (!empty($orderData['links'])) {
        foreach ($orderData['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }
    }

    if (!$approvalUrl) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'PayPal approval URL not found. Please try again.'
        ]);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'order_id' => $orderData['id'] ?? null,
        'approval_url' => $approvalUrl
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error creating PayPal order: ' . $e->getMessage()
    ]);
}
