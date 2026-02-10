<?php
/**
 * API: Create New Order - SECURE VERSION
 * Endpoint: POST /api/create-order.php
 * Body: { customerName, customerEmail, items: [{id, name, quantity, price}], total }
 */

// Load security configuration first
require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email-config.php';
require_once __DIR__ . '/../includes/PHPMailer.php';
require_once __DIR__ . '/../includes/SMTP.php';
require_once __DIR__ . '/../includes/Exception.php';
require_once __DIR__ . '/../includes/send-email.php';

header('Content-Type: application/json; charset=utf-8');

// Validate CORS
validateCORSOrigin();

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Rate limiting
$rateLimiter = new RateLimiter($conn);
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

if ($rateLimiter->isLimited($clientIP)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$logger = new SecurityLogger('orders.log');

try {
    // Validate required fields
    $required = ['customerName', 'customerEmail', 'items', 'total'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate customer name
    $customerName = Validator::name($data['customerName'] ?? '');
    if ($customerName === false) {
        throw new Exception('Invalid customer name format');
    }
    
    // Validate email
    $email = Validator::email($data['customerEmail'] ?? '');
    if ($email === false) {
        throw new Exception('Invalid email address');
    }
    
    // Validate items array
    if (!is_array($data['items']) || empty($data['items'])) {
        throw new Exception('Items array is required and cannot be empty');
    }
    
    if (count($data['items']) > 100) {
        throw new Exception('Too many items in order (max 100)');
    }
    
    // Validate total amount
    $total = Validator::price($data['total'], 0.01, 999999.99);
    if ($total === false) {
        throw new Exception('Invalid total amount');
    }
    
    // Validate payment method (optional, defaults to 'pod')
    $paymentMethod = isset($data['paymentMethod']) ? Validator::sanitizeText($data['paymentMethod']) : 'pod';
    $allowedMethods = ['pod', 'bank_transfer', 'mobile_money', 'paypal', 'stripe', 'card'];
    if (!in_array($paymentMethod, $allowedMethods)) {
        $paymentMethod = 'pod'; // Default to pay on delivery if invalid
    }
    
    // Determine initial payment status based on payment method
    $initialPaymentStatus = 'pending';
    if ($paymentMethod === 'pod') {
        $initialPaymentStatus = 'pending'; // Will be paid on delivery
    } elseif (in_array($paymentMethod, ['bank_transfer', 'mobile_money'])) {
        $initialPaymentStatus = 'awaiting_confirmation'; // Needs manual verification
    } elseif (in_array($paymentMethod, ['paypal', 'stripe', 'card'])) {
        $initialPaymentStatus = 'processing'; // Gateway will update
    }
    
    // Validate delivery method (optional)
    $deliveryMethodId = null;
    // default to frontend-provided deliveryFee when no method id
    $deliveryCost = isset($data['deliveryFee']) ? (float)$data['deliveryFee'] : 0;
    $deliveryAddress = isset($data['shippingAddress']) ? Validator::sanitizeText($data['shippingAddress']) : null;
    
    // Store delivery clarification details
    $customerPhone = isset($data['customerPhone']) ? Validator::sanitizeText($data['customerPhone']) : null;
    $deliveryInfo = json_encode([
        'zone' => isset($data['deliveryZone']) ? Validator::sanitizeText($data['deliveryZone']) : null,
        'street' => isset($data['deliveryStreet']) ? Validator::sanitizeText($data['deliveryStreet']) : null,
        'building' => isset($data['deliveryBuilding']) ? Validator::sanitizeText($data['deliveryBuilding']) : null,
        'area' => isset($data['deliveryArea']) ? Validator::sanitizeText($data['deliveryArea']) : null,
        'landmark' => isset($data['deliveryLandmark']) ? Validator::sanitizeText($data['deliveryLandmark']) : null,
        'directions' => isset($data['deliveryDirections']) ? Validator::sanitizeText($data['deliveryDirections']) : null,
        'notes' => isset($data['deliveryNotes']) ? Validator::sanitizeText($data['deliveryNotes']) : null,
        'phone' => $customerPhone
    ], JSON_UNESCAPED_UNICODE);
    
    if (isset($data['deliveryMethodId']) && !empty($data['deliveryMethodId'])) {
        $deliveryMethodId = Validator::integer($data['deliveryMethodId'], 1, 999999);
        if ($deliveryMethodId === false) {
            throw new Exception('Invalid delivery method');
        }
        
        // Fetch delivery method and cost
        $deliveryQuery = "SELECT id, cost FROM delivery_options WHERE id = ? AND is_active = 1 LIMIT 1";
        $deliveryMethod = getRow($deliveryQuery, [$deliveryMethodId]);
        if (!$deliveryMethod) {
            throw new Exception('Delivery method not found or is inactive');
        }
        
        $deliveryCost = (float)$deliveryMethod['cost'];
    }
    
    // Validate each item
    $validItems = [];
    $calculatedTotal = 0;
    
    foreach ($data['items'] as $item) {
        // Validate item fields
        if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
            throw new Exception('Invalid item format. Each item must have id, quantity, and price');
        }
        
        $itemId = Validator::integer($item['id'], 1, 999999);
        if ($itemId === false) {
            throw new Exception('Invalid product ID');
        }
        
        $quantity = Validator::integer($item['quantity'], 1, 1000);
        if ($quantity === false) {
            throw new Exception('Invalid quantity (1-1000)');
        }
        
        $price = Validator::price($item['price'], 0.01, 99999.99);
        if ($price === false) {
            throw new Exception('Invalid item price');
        }
        
        // Validate product exists and is in stock
        $productQuery = "SELECT id, name, price, in_stock FROM products WHERE id = ? LIMIT 1";
        $product = getRow($productQuery, [$itemId]);
        
        // If missing, auto-create a minimal product record so the order can proceed
        if (!$product) {
            $fallbackName = $item['name'] ?? ('Product ' . $itemId);
            $insertProductQuery = "INSERT INTO products (id, name, description, price, category, image_url, emoji, in_stock, created_at) VALUES (?, ?, '', ?, 'Journals', NULL, '📔', 1, NOW())";
            executeQuery($insertProductQuery, [$itemId, $fallbackName, $price]);
            $product = getRow($productQuery, [$itemId]);
        }

        // If still missing, fall back to the cart payload (do not block checkout)
        if (!$product) {
            $product = [
                'id' => $itemId,
                'name' => $item['name'] ?? ('Product ' . $itemId),
                'price' => $price,
                'in_stock' => 1
            ];
        }
        
        // Check if product is in stock
        if (!$product['in_stock']) {
            throw new Exception("STOCK_UNAVAILABLE:Product '$product[name]' is currently out of stock");
        }
        
        // Verify price matches (prevent price manipulation) with 1 UGX tolerance
        if (isset($product['price']) && abs($price - $product['price']) > 1.0) {
            throw new Exception("Price mismatch for product. Expected: {$product['price']}, Got: {$price}");
        }
        
        $itemTotal = $price * $quantity;
        $calculatedTotal += $itemTotal;
        
        $validItems[] = [
            'id' => $itemId,
            'name' => Validator::text($item['name'] ?? $product['name'], 255),
            'quantity' => $quantity,
            'price' => $price
        ];
    }
    
    // Align client total to backend-calculated items sum
    $total = $calculatedTotal;
    
    // Database transaction
    $conn->begin_transaction();
    
    try {
        // Check or create customer
        $customerQuery = "SELECT id FROM customers WHERE email = ? LIMIT 1";
        $customer = getRow($customerQuery, [$email]);
        
        if (!$customer) {
            // Try to insert with phone/address if columns exist, otherwise just name/email
            try {
                $insertCustomerQuery = "INSERT INTO customers (name, email, phone, address, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = executeQuery($insertCustomerQuery, [$customerName, $email, $customerPhone, $deliveryAddress]);
            } catch (Exception $e) {
                // Fallback if phone/address columns don't exist
                $insertCustomerQuery = "INSERT INTO customers (name, email, created_at) VALUES (?, ?, NOW())";
                $stmt = executeQuery($insertCustomerQuery, [$customerName, $email]);
            }
            if (!$stmt) {
                throw new Exception('Failed to create customer');
            }
            $customerId = getLastInsertId();
        } else {
            $customerId = $customer['id'];
            // Try to update phone/address if columns exist
            try {
                $updateCustomerQuery = "UPDATE customers SET phone = COALESCE(NULLIF(phone, ''), ?), address = COALESCE(NULLIF(address, ''), ?) WHERE id = ?";
                executeQuery($updateCustomerQuery, [$customerPhone, $deliveryAddress, $customerId]);
            } catch (Exception $e) {
                // Ignore if columns don't exist
            }
        }
        
        // Create order number
        $orderNumber = 'ORD-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
        
        // Use backend-calculated total to avoid frontend rounding mismatches
        $expectedTotal = $calculatedTotal + $deliveryCost;
        $total = $expectedTotal;
        
        // Create order with delivery method and notes
        $orderQuery = "INSERT INTO orders (order_number, customer_id, customer_name, customer_email, total_amount, delivery_method_id, delivery_cost, shipping_address, notes, payment_method, status, payment_status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
        $stmt = executeQuery($orderQuery, [
            $orderNumber,
            $customerId,
            $customerName,
            $email,
            $total,
            $deliveryMethodId,
            $deliveryCost,
            $deliveryAddress,
            $deliveryInfo,
            $paymentMethod,
            $initialPaymentStatus
        ]);
        
        if (!$stmt) {
            throw new Exception('Failed to create order');
        }
        
        $orderId = getLastInsertId();
        
        // Add order items
        foreach ($validItems as $item) {
            $itemQuery = "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = executeQuery($itemQuery, [
                $orderId,
                $item['id'],
                $item['name'],
                $item['quantity'],
                $item['price'],
                $item['price'] * $item['quantity']
            ]);
            
            if (!$stmt) {
                throw new Exception('Failed to add order item');
            }
            
            // Update inventory - deduct from quantity_in_stock
            $inventoryUpdateQuery = "UPDATE inventory SET quantity_in_stock = quantity_in_stock - ? WHERE product_id = ?";
            executeQuery($inventoryUpdateQuery, [$item['quantity'], $item['id']]);
            
            // Update product in_stock status if quantity reaches 0
            $checkStockQuery = "SELECT quantity_in_stock FROM inventory WHERE product_id = ?";
            $stockData = getRow($checkStockQuery, [$item['id']]);
            if ($stockData && (int)$stockData['quantity_in_stock'] <= 0) {
                $updateProductQuery = "UPDATE products SET in_stock = 0 WHERE id = ?";
                executeQuery($updateProductQuery, [$item['id']]);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log successful order
        $logger->log('ORDER_CREATED', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'customer_email' => $email,
            'total_amount' => $total,
            'items_count' => count($validItems)
        ]);
        
        // Send confirmation email to customer
        $deliveryInfoDecoded = json_decode($deliveryInfo, true);
        $subtotal = $total - $deliveryCost;
        
        // Build a comprehensive Google Maps search query
        $mapsQuery = '';
        if (!empty($deliveryInfoDecoded['street'])) {
            $mapsQuery .= $deliveryInfoDecoded['street'] . ', ';
        }
        if (!empty($deliveryInfoDecoded['building'])) {
            $mapsQuery .= $deliveryInfoDecoded['building'] . ', ';
        }
        if (!empty($deliveryInfoDecoded['area'])) {
            $mapsQuery .= $deliveryInfoDecoded['area'] . ', ';
        }
        if (!empty($deliveryInfoDecoded['landmark'])) {
            $mapsQuery .= $deliveryInfoDecoded['landmark'] . ', ';
        }
        $mapsQuery .= 'Uganda'; // Add country for better results
        $mapsQuery = rtrim($mapsQuery, ', ');
        
        $emailData = [
            'orderNumber' => $orderNumber,
            'customerName' => $customerName,
            'customerEmail' => $email,
            'customerPhone' => $customerPhone ?? '',
            'items' => array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }, $validItems),
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryCost,
            'total' => $total,
            'paymentMethod' => isset($data['paymentMethod']) ? Validator::sanitizeText($data['paymentMethod']) : 'unknown',
            'orderDate' => date('Y-m-d H:i:s'),
            'zone' => $deliveryInfoDecoded['zone'] ?? '',
            'street' => $deliveryInfoDecoded['street'] ?? '',
            'building' => $deliveryInfoDecoded['building'] ?? '',
            'area' => $deliveryInfoDecoded['area'] ?? '',
            'landmark' => $deliveryInfoDecoded['landmark'] ?? '',
            'directions' => $deliveryInfoDecoded['directions'] ?? '',
            'notes' => $deliveryInfoDecoded['notes'] ?? '',
            'mapsLink' => !empty($mapsQuery) ? "https://www.google.com/maps/search/" . urlencode($mapsQuery) : ''
        ];
        
        // ===== SEND RESPONSE IMMEDIATELY (Don't wait for emails) =====
        http_response_code(201);
        $response = [
            'success' => true,
            'message' => 'Order created successfully',
            'orderId' => $orderId,
            'orderNumber' => $orderNumber,
            'paymentMethod' => $paymentMethod,
            'paymentStatus' => $initialPaymentStatus
        ];
        
        echo json_encode($response);
        
        // Flush output to client immediately
        if (function_exists('flush')) {
            @flush();
        }
        
        // Close connection to client
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        
        // ===== SEND EMAILS ASYNCHRONOUSLY (After client receives response) =====
        
        error_log("Sending order confirmation email for order: $orderNumber (async)");
        $ORDER_EMAIL_DATA = $emailData; // Set global variable for email script
        ob_start(); // Capture output
        try {
            include __DIR__ . '/send-order-email.php';
        } catch (Exception $emailError) {
            error_log("Error in send-order-email.php: " . $emailError->getMessage());
        }
        ob_end_clean(); // Discard captured output
        error_log("Email script executed");
        
        // For bank transfer or mobile money, send payment instructions
        if (in_array($paymentMethod, ['bank_transfer', 'mobile_money'])) {
            try {
                require_once __DIR__ . '/../config/payment-config.php';
                
                // Ensure total is a numeric value for formatting
                $totalAmount = is_numeric($total) ? (float)$total : 0;
                
                $paymentInstructions = '';
                if ($paymentMethod === 'bank_transfer') {
                    $slip = generateBankPaymentSlip($orderId, $totalAmount, $orderNumber);
                    $paymentInstructions = "\n\n=== PAYMENT INSTRUCTIONS ===\n\n";
                    $paymentInstructions .= "Please transfer the exact amount to:\n\n";
                    $paymentInstructions .= "Bank: {$slip['bank_name']}\n";
                    $paymentInstructions .= "Account Name: {$slip['account_name']}\n";
                    $paymentInstructions .= "Account Number: {$slip['account_number']}\n";
                    $paymentInstructions .= "Amount: UGX " . number_format($totalAmount, 0) . "\n";
                    $paymentInstructions .= "Reference: {$orderNumber}\n\n";
                    $paymentInstructions .= "IMPORTANT: Please include the order number ({$orderNumber}) as your payment reference.\n";
                    $paymentInstructions .= "Once payment is verified, we will send you a confirmation and begin processing your order.\n";
                } elseif ($paymentMethod === 'mobile_money') {
                    $slip = generateMobileMoneySlip($orderId, $totalAmount, $orderNumber, 'mtn');
                    $paymentInstructions = "\n\n=== PAYMENT INSTRUCTIONS ===\n\n";
                    $paymentInstructions .= "Please send payment via Mobile Money:\n\n";
                    $paymentInstructions .= "{$slip['provider_name']}\n";
                    $paymentInstructions .= "Phone Number: {$slip['phone_number']}\n";
                    $paymentInstructions .= "Amount: UGX " . number_format($totalAmount, 0) . "\n";
                    $paymentInstructions .= "Reference: {$orderNumber}\n\n";
                    $paymentInstructions .= "IMPORTANT: Please include the order number ({$orderNumber}) in the memo/reference field.\n";
                    $paymentInstructions .= "Once payment is verified, we will send you a confirmation and begin processing your order.\n";
                }
                
                // Send payment slip email
                if ($paymentInstructions) {
                    $paymentEmailBody = "Dear {$customerName},\n\n";
                    $paymentEmailBody .= "Thank you for your order #{$orderNumber}!\n\n";
                    $paymentEmailBody .= $paymentInstructions;
                    $paymentEmailBody .= "\n\nOrder Summary:\n";
                    foreach ($validItems as $item) {
                        $itemTotal = (float)$item['price'] * (int)$item['quantity'];
                        $paymentEmailBody .= "- {$item['name']} × {$item['quantity']} = UGX " . number_format($itemTotal, 0) . "\n";
                    }
                    $subtotalAmount = is_numeric($subtotal) ? (float)$subtotal : 0;
                    $deliveryAmount = is_numeric($deliveryCost) ? (float)$deliveryCost : 0;
                    
                    $paymentEmailBody .= "\nSubtotal: UGX " . number_format($subtotalAmount, 0) . "\n";
                    $paymentEmailBody .= "Delivery: UGX " . number_format($deliveryAmount, 0) . "\n";
                    $paymentEmailBody .= "Total: UGX " . number_format($totalAmount, 0) . "\n";
                    $paymentEmailBody .= "\n\nBest regards,\nReader's Haven";
                    
                    sendEmail($email, $customerName, "Payment Instructions - Order {$orderNumber}", $paymentEmailBody);
                }
            } catch (Exception $paymentEmailError) {
                // Log but don't fail - order was already created and response sent
                error_log("Failed to send payment instructions email: " . $paymentEmailError->getMessage());
            }
        }
        
        // Exit script - emails are sent in background
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        
        // Log error
        $logger->log('ORDER_ERROR', [
            'error' => $e->getMessage(),
            'email' => $email ?? 'unknown'
        ]);
    }
    
} catch (Exception $e) {
    // Validation errors
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    $logger->log('VALIDATION_ERROR', [
        'error' => $e->getMessage()
    ]);
}

?>
