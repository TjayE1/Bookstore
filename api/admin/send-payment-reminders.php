<?php
/**
 * API: Send Payment Reminders (Admin Trigger)
 * Endpoint: POST /api/admin/send-payment-reminders.php
 */

require_once __DIR__ . '/../../includes/security-headers.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/email-config.php';
require_once __DIR__ . '/../../includes/send-email.php';
require_once __DIR__ . '/../../config/payment-config.php';
require_once __DIR__ . '/../../config/payment-reminders.php';
require_once __DIR__ . '/../includes/auth.php';

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

if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!PAYMENT_REMINDERS_ENABLED) {
    echo json_encode(['success' => true, 'message' => 'Reminders are disabled', 'sent' => 0]);
    exit();
}

function buildReminderEmailBody($order, $bankDetails, $mobileMoney) {
    $amount = number_format((float)$order['total_amount'], 0);
    $orderNumber = $order['order_number'];
    $paymentMethod = $order['payment_method'] ?? 'bank_transfer';

    $body = "Dear {$order['customer_name']},<br><br>";
    $body .= "This is a friendly reminder that payment is still pending for your order.<br><br>";
    $body .= "Order Number: {$orderNumber}<br>";
    $body .= "Amount Due: UGX {$amount}<br>";
    $body .= "Payment Method: " . ucwords(str_replace('_', ' ', $paymentMethod)) . "<br><br>";

    if ($paymentMethod === 'bank_transfer') {
        $body .= "<strong>Bank Transfer Details</strong><br>";
        $body .= "Bank: {$bankDetails['bank_name']}<br>";
        $body .= "Account Name: {$bankDetails['account_name']}<br>";
        $body .= "Account Number: {$bankDetails['account_number']}<br>";
        if (!empty($bankDetails['swift_code'])) {
            $body .= "SWIFT Code: {$bankDetails['swift_code']}<br>";
        }
        if (!empty($bankDetails['iban'])) {
            $body .= "IBAN: {$bankDetails['iban']}<br>";
        }
        $body .= "Reference: {$orderNumber}<br><br>";
    } else {
        $body .= "<strong>Mobile Money Details</strong><br>";
        $body .= "MTN: {$mobileMoney['mtn']['number']} ({$mobileMoney['mtn']['name']})<br>";
        $body .= "Airtel: {$mobileMoney['airtel']['number']} ({$mobileMoney['airtel']['name']})<br>";
        $body .= "Reference: {$orderNumber}<br><br>";
    }

    $body .= "If you have already paid, please ignore this message.<br>";
    $body .= "If you need help, reply to this email and we will assist you.<br><br>";
    $body .= "Thank you,<br>Reader's Haven";

    return $body;
}

try {
    $columnsQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'";
    $columns = getRows($columnsQuery);
    $columnSet = [];
    foreach ($columns as $col) {
        $columnSet[$col['COLUMN_NAME']] = true;
    }

    $requiredColumns = [
        'payment_reminder_count',
        'payment_reminder_last_sent_at',
        'payment_status',
        'payment_method'
    ];

    $missing = [];
    foreach ($requiredColumns as $required) {
        if (!isset($columnSet[$required])) {
            $missing[] = $required;
        }
    }

    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing database columns. Run the payment reminder migration.',
            'missingColumns' => $missing
        ]);
        exit();
    }

    $firstMinutes = (int)PAYMENT_REMINDER_FIRST_MINUTES;
    $repeatMinutes = (int)PAYMENT_REMINDER_REPEAT_MINUTES;
    $maxCount = (int)PAYMENT_REMINDER_MAX_COUNT;
    $limit = (int)PAYMENT_REMINDER_BATCH_LIMIT;

    $query = "SELECT id, order_number, customer_name, customer_email, total_amount, payment_method, created_at, 
                     payment_reminder_count, payment_reminder_last_sent_at
              FROM orders
              WHERE payment_status = 'awaiting_confirmation'
                AND payment_method IN ('bank_transfer', 'mobile_money')
                AND created_at <= (NOW() - INTERVAL ? MINUTE)
                AND (payment_reminder_last_sent_at IS NULL OR payment_reminder_last_sent_at <= (NOW() - INTERVAL ? MINUTE))
                AND (payment_reminder_count IS NULL OR payment_reminder_count < ?)
              ORDER BY created_at ASC
              LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiii', $firstMinutes, $repeatMinutes, $maxCount, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);

    $sent = 0;
    $failed = 0;

    if (empty($orders)) {
        echo json_encode(['success' => true, 'sent' => 0, 'failed' => 0, 'message' => 'No reminders due']);
        exit();
    }

    $updateQuery = "UPDATE orders
                    SET payment_reminder_count = COALESCE(payment_reminder_count, 0) + 1,
                        payment_reminder_last_sent_at = NOW()
                    WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);

    foreach ($orders as $order) {
        $emailBody = buildReminderEmailBody($order, $BANK_DETAILS, $MOBILE_MONEY);
        $emailResult = sendEmail(
            $order['customer_email'],
            $order['customer_name'],
            'Payment Reminder - Order ' . $order['order_number'],
            $emailBody
        );

        if (!empty($emailResult['success'])) {
            $sent++;
            $orderId = (int)$order['id'];
            $updateStmt->bind_param('i', $orderId);
            $updateStmt->execute();
        } else {
            $failed++;
        }
    }

    echo json_encode([
        'success' => true,
        'sent' => $sent,
        'failed' => $failed,
        'checked' => count($orders)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send reminders',
        'error' => $e->getMessage()
    ]);
}
