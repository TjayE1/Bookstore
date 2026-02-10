<?php
/**
 * Send Order Confirmation Email
 * Receives order data and sends confirmation email to customer
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/email-config.php';
require_once __DIR__ . '/../includes/PHPMailer.php';
require_once __DIR__ . '/../includes/SMTP.php';
require_once __DIR__ . '/../includes/Exception.php';
require_once __DIR__ . '/../includes/send-email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input - from POST request OR from variable
if (isset($ORDER_EMAIL_DATA)) {
    // Called from create-order.php directly
    $data = $ORDER_EMAIL_DATA;
    error_log("send-order-email.php called directly with data");
} else {
    // Called via HTTP
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    error_log("send-order-email.php received HTTP request");
    error_log("Input data: " . substr($input, 0, 200));
}

error_log("Decoded data: " . json_encode($data));

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (empty($data['customerName']) || empty($data['customerEmail']) || empty($data['items']) || empty($data['total'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Extract basic data
$customerName = htmlspecialchars($data['customerName']);
$customerEmail = filter_var($data['customerEmail'], FILTER_VALIDATE_EMAIL);
$items = $data['items'];
$total = isset($data['total']) ? number_format($data['total'], 0) : 0;
$orderDate = $data['orderDate'] ?? date('Y-m-d H:i:s');

// Extract delivery information
$orderNumber = $data['orderNumber'] ?? 'N/A';
$customerPhone = $data['customerPhone'] ?? 'N/A';
$zone = $data['zone'] ?? 'N/A';
$street = htmlspecialchars($data['street'] ?? '');
$building = htmlspecialchars($data['building'] ?? '');
$area = htmlspecialchars($data['area'] ?? '');
$landmark = htmlspecialchars($data['landmark'] ?? '');
$directions = htmlspecialchars($data['directions'] ?? '');
$notes = htmlspecialchars($data['notes'] ?? '');
$mapsLink = $data['mapsLink'] ?? '';
$paymentMethod = $data['paymentMethod'] ?? 'N/A';
$deliveryFee = isset($data['deliveryFee']) ? number_format($data['deliveryFee'], 0) : '0';
$subtotal = isset($data['subtotal']) ? number_format($data['subtotal'], 0) : number_format($data['total'] - ($data['deliveryFee'] ?? 0), 0);

if (!$customerEmail) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

// Build items list HTML
$itemsHtml = '';
foreach ($items as $item) {
    $itemName = htmlspecialchars($item['name']);
    $quantity = (int)$item['quantity'];
    $price = number_format($item['price'], 0);
    $itemTotal = number_format($item['price'] * $quantity, 0);
    
    $itemsHtml .= "
        <tr>
            <td style='padding: 12px; border-bottom: 1px solid #e0e0e0;'>{$itemName}</td>
            <td style='padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: center;'>{$quantity}</td>
            <td style='padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: right;'>UGX {$price}</td>
            <td style='padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: right;'>UGX {$itemTotal}</td>
        </tr>";
}

// Email template for customer
$customerSubject = "Order Confirmation - " . SITE_NAME;
$customerMessage = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff;'>
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #7A9B8E 0%, #5B7C99 100%); padding: 30px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>📚 " . SITE_NAME . "</h1>
            <p style='color: #ffffff; margin: 10px 0 0 0; font-size: 16px;'>Order Confirmation</p>
        </div>
        
        <!-- Content -->
        <div style='padding: 30px;'>
            <h2 style='color: #2C2C2C; margin-top: 0;'>Thank you for your order, {$customerName}! ✅</h2>
            
            <p style='color: #5F5F5F; line-height: 1.6;'>
                We've received your order and it's being processed. You'll receive another email when your order ships.
            </p>
            
            <div style='background-color: #f8f6f3; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3 style='color: #2C2C2C; margin-top: 0;'>Order Details</h3>
                <p style='color: #5F5F5F; margin: 5px 0;'><strong>Order Date:</strong> {$orderDate}</p>
                <p style='color: #5F5F5F; margin: 5px 0;'><strong>Customer Email:</strong> {$customerEmail}</p>
            </div>
            
            <h3 style='color: #2C2C2C;'>Items Ordered:</h3>
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <thead>
                    <tr style='background-color: #7A9B8E; color: #ffffff;'>
                        <th style='padding: 12px; text-align: left;'>Item</th>
                        <th style='padding: 12px; text-align: center;'>Qty</th>
                        <th style='padding: 12px; text-align: right;'>Price</th>
                        <th style='padding: 12px; text-align: right;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
                <tfoot>
                    <tr style='background-color: #f0f0f0;'>
                        <td colspan='3' style='padding: 15px; text-align: right; font-weight: bold;'>Total:</td>
                        <td style='padding: 15px; text-align: right; font-weight: bold; color: #5B7C99; font-size: 18px;'>UGX {$total}</td>
                    </tr>
                </tfoot>
            </table>
            
            <div style='background-color: #e8f4f1; padding: 15px; border-radius: 8px; border-left: 4px solid #7A9B8E; margin: 20px 0;'>
                <p style='margin: 0; color: #2C2C2C;'>
                    <strong>What's Next?</strong><br>
                    We'll prepare your order and send you a shipping confirmation with tracking information soon.
                </p>
            </div>
            
            <p style='color: #5F5F5F; line-height: 1.6;'>
                If you have any questions about your order, please contact us at 
                <a href='mailto:" . SUPPORT_EMAIL . "' style='color: #5B7C99;'>" . SUPPORT_EMAIL . "</a>
            </p>
        </div>
        
        <!-- Footer -->
        <div style='background-color: #2C2C2C; padding: 20px; text-align: center;'>
            <p style='color: #ffffff; margin: 0; font-size: 14px;'>
                &copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.
            </p>
            <p style='color: #aaa; margin: 10px 0 0 0; font-size: 12px;'>
                Counselling & Books
            </p>
        </div>
    </div>
</body>
</html>
";

// Build admin notification email with maps link
$adminSubject = "New Order Received - " . SITE_NAME;
$mapsLinkHtml = $mapsLink ? "<a href='{$mapsLink}' target='_blank' style='background-color: #7A9B8E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>📍 View Location on Google Maps</a>" : '';

$adminMessage = "
<!DOCTYPE html>
<html>
<body style='font-family: Arial, sans-serif; background-color: #f5f5f5;'>
    <div style='max-width: 800px; margin: 0 auto; background-color: #ffffff; padding: 20px;'>
        <h2 style='color: #2C2C2C;'>🚀 New Order Received</h2>
        
        <h3 style='color: #7A9B8E; margin-top: 20px;'>Order Information</h3>
        <p><strong>Order #:</strong> {$orderNumber}</p>
        <p><strong>Order Date:</strong> {$orderDate}</p>
        
        <h3 style='color: #7A9B8E;'>Customer Details</h3>
        <p><strong>Name:</strong> {$customerName}</p>
        <p><strong>Phone:</strong> {$customerPhone}</p>
        <p><strong>Email:</strong> {$customerEmail}</p>
        
        <h3 style='color: #7A9B8E;'>Delivery Information</h3>
        <div style='background-color: #f8f6f3; padding: 15px; border-radius: 8px; border-left: 4px solid #7A9B8E;'>
            <p><strong>Zone:</strong> {$zone}</p>
            <p><strong>Street/Road:</strong> {$street}</p>
            " . (!empty($building) ? "<p><strong>Building/House:</strong> {$building}</p>" : "") . "
            <p><strong>Area:</strong> {$area}</p>
            <p><strong>Landmark:</strong> {$landmark}</p>
            " . (!empty($directions) ? "<p><strong>Directions:</strong> {$directions}</p>" : "") . "
            " . (!empty($notes) ? "<p><strong>Delivery Notes:</strong> {$notes}</p>" : "") . "
            <p style='margin-top: 15px;'>{$mapsLinkHtml}</p>
        </div>
        
        <h3 style='color: #7A9B8E;'>Order Items</h3>
        <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
            <thead>
                <tr style='background-color: #7A9B8E; color: #ffffff;'>
                    <th style='padding: 12px; text-align: left;'>Item</th>
                    <th style='padding: 12px; text-align: center;'>Qty</th>
                    <th style='padding: 12px; text-align: right;'>Price</th>
                    <th style='padding: 12px; text-align: right;'>Total</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
            <tfoot>
                <tr style='background-color: #f0f0f0;'>
                    <td colspan='3' style='padding: 12px; text-align: right;'><strong>Subtotal:</strong></td>
                    <td style='padding: 12px; text-align: right; font-weight: bold;'>UGX {$subtotal}</td>
                </tr>
                <tr style='background-color: #f0f0f0;'>
                    <td colspan='3' style='padding: 12px; text-align: right;'><strong>Delivery Fee:</strong></td>
                    <td style='padding: 12px; text-align: right; font-weight: bold;'>UGX {$deliveryFee}</td>
                </tr>
                <tr style='background-color: #e8f1f0;'>
                    <td colspan='3' style='padding: 15px; text-align: right;'><strong>TOTAL:</strong></td>
                    <td style='padding: 15px; text-align: right; font-weight: bold; color: #5B7C99; font-size: 18px;'>UGX {$total}</td>
                </tr>
            </tfoot>
        </table>
        
        <h3 style='color: #7A9B8E;'>Payment Method</h3>
        <p><strong>" . ($paymentMethod === 'pod' ? '💵 Pay on Delivery' : '📱 Mobile Money/Card') . "</strong></p>
        
        <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 20px;'>
            <p style='margin: 0; color: #333;'><strong>📝 Action Required:</strong> Prepare order for packaging and dispatch.</p>
        </div>
    </div>
</body>
</html>
";

// Send email to customer (NO admin CC)
error_log("Sending customer confirmation email to: $customerEmail");
$customerEmailResult = sendEmail($customerEmail, $customerName, $customerSubject, $customerMessage);
error_log("Customer email result: " . json_encode($customerEmailResult));

// Send separate notification to admin
error_log("Sending admin notification email to: " . ADMIN_EMAIL);
$adminEmailResult = sendEmail(ADMIN_EMAIL, SITE_NAME, $adminSubject, $adminMessage);
error_log("Admin email result: " . json_encode($adminEmailResult));

// Don't output JSON when included from create-order.php (parent handles response)
if (!isset($ORDER_EMAIL_DATA)) {
    // Called via HTTP request
    if ($customerEmailResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Order confirmation emails sent successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email. Please contact support.'
        ]);
    }}
?>