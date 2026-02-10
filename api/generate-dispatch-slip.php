<?php
/**
 * Generate Dispatch Slip API Endpoint
 * Creates a printable dispatch slip for an order
 * Requires: Authentication, order ID in query parameter
 * Returns: Dispatch slip number and printable HTML
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Get order ID from query or body
    $orderId = null;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = isset($input['order_id']) ? (int)$input['order_id'] : null;
    }

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameter: order_id']);
        exit();
    }

    // Fetch order with all details
    $stmt = $conn->prepare(
        "SELECT o.*, d.name as delivery_name, d.cost as delivery_cost, d.delivery_time_max
         FROM orders o
         LEFT JOIN delivery_options d ON o.delivery_method_id = d.id
         WHERE o.id = ?"
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    // Generate dispatch slip number if not exists
    if (empty($order['dispatch_slip_number'])) {
        $dispatchSlipNumber = 'DS-' . date('YmdHis') . '-' . $orderId;
        
        $updateStmt = $conn->prepare("UPDATE orders SET dispatch_slip_number = ? WHERE id = ?");
        $updateStmt->bind_param('si', $dispatchSlipNumber, $orderId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $order['dispatch_slip_number'] = $dispatchSlipNumber;
    }

    // Fetch order items
    $itemsStmt = $conn->prepare(
        "SELECT product_name, quantity, unit_price, total_price FROM order_items WHERE order_id = ?"
    );
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    $items = [];
    
    while ($itemRow = $itemsResult->fetch_assoc()) {
        $items[] = [
            'product_name' => $itemRow['product_name'],
            'quantity' => (int)$itemRow['quantity'],
            'unit_price' => (float)$itemRow['unit_price'],
            'total_price' => (float)$itemRow['total_price']
        ];
    }
    $itemsStmt->close();

    // Calculate estimated delivery date
    $orderDate = new DateTime($order['created_at']);
    $estimatedDeliveryDays = isset($order['delivery_time_max']) ? $order['delivery_time_max'] : 5;
    $estimatedDeliveryDate = clone $orderDate;
    $estimatedDeliveryDate->modify("+{$estimatedDeliveryDays} days");

    // Generate HTML dispatch slip
    $dispatchSlipHtml = generateDispatchSlipHTML(
        $order,
        $items,
        $estimatedDeliveryDate
    );

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Dispatch slip generated successfully',
        'data' => [
            'order_id' => (int)$order['id'],
            'order_number' => $order['order_number'],
            'dispatch_slip_number' => $order['dispatch_slip_number'],
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'delivery_method' => $order['delivery_name'] ?? 'Standard Delivery',
            'estimated_delivery_date' => $estimatedDeliveryDate->format('Y-m-d'),
            'total_amount' => (float)$order['total_amount'],
            'html' => $dispatchSlipHtml
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate dispatch slip: ' . $e->getMessage()]);
}

$conn->close();

/**
 * Generate HTML for dispatch slip
 */
function generateDispatchSlipHTML($order, $items, $estimatedDeliveryDate) {
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Dispatch Slip - {$order['dispatch_slip_number']}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .dispatch-slip {
            background: white;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #333;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #8b6f47;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .slip-number {
            font-size: 18px;
            font-weight: bold;
            color: #c1440e;
            padding: 10px;
            background: #fff3e0;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            background: #8b6f47;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .section-content {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .info-value {
            flex: 1;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .items-table th {
            background: #8b6f47;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .items-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-row {
            background: #e8d7c3;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .print-only {
            display: none;
        }
        @media print {
            body {
                background: white;
            }
            .dispatch-slip {
                box-shadow: none;
                border: 1px solid #ccc;
            }
            .print-only {
                display: block;
            }
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }
    </style>
</head>
<body>
    <div class="dispatch-slip">
        <div class="header">
            <h1>📦 DISPATCH SLIP</h1>
            <p>Reader's Haven - Order Fulfillment</p>
            <div class="slip-number">{$order['dispatch_slip_number']}</div>
        </div>

        <div class="section">
            <div class="section-title">Order Information</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Order #:</span>
                    <span class="info-value"><strong>{$order['order_number']}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value">{$order['created_at']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><span class="status-badge status-{$order['status']}">{$order['status']}</span></span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Customer Information</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{$order['customer_name']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{$order['customer_email']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Delivery Address:</span>
                    <span class="info-value">{$order['shipping_address'] ?? 'Not specified'}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Delivery Information</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Method:</span>
                    <span class="info-value">{$order['delivery_name'] ?? 'Standard Delivery'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estimated Delivery:</span>
                    <span class="info-value">{$estimatedDeliveryDate->format('l, F j, Y')}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Items to Ship</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="width: 80px;">Qty</th>
                        <th style="width: 100px;">Unit Price</th>
                        <th style="width: 100px;">Total</th>
                    </tr>
                </thead>
                <tbody>
HTML;

    // Add items rows
    foreach ($items as $item) {
        $itemTotal = number_format($item['total_price'], 2);
        $unitPrice = number_format($item['unit_price'], 2);
        $html .= <<<HTML
                    <tr>
                        <td>{$item['product_name']}</td>
                        <td style="text-align: center;">{$item['quantity']}</td>
                        <td style="text-align: right;">UGX {$unitPrice}</td>
                        <td style="text-align: right;">UGX {$itemTotal}</td>
                    </tr>
HTML;
    }

    // Add total row
    $total = number_format($order['total_amount'], 2);
    $html .= <<<HTML
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">UGX {$total}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Packing Instructions</div>
            <div class="section-content">
                <p>☐ Verify all items are included</p>
                <p>☐ Check product condition</p>
                <p>☐ Securely package items</p>
                <p>☐ Include packing slip</p>
                <p>☐ Apply shipping label</p>
                <p style="margin-top: 20px;"><strong>Notes:</strong> {$order['notes'] ?? 'None'}</p>
            </div>
        </div>

        <div class="footer">
            <p>📦 Reader's Haven | Dispatch Slip {$order['dispatch_slip_number']}</p>
            <p>Generated: {$estimatedDeliveryDate->format('Y-m-d H:i:s')}</p>
            <p class="print-only">Please print this slip and attach to the package.</p>
        </div>
    </div>

    <script>
        // Auto-print when opened in new window
        if (window.location.hash === '#print') {
            window.print();
        }
    </script>
</body>
</html>
HTML;

    return $html;
}
?>
