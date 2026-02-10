<?php
/**
 * Send Booking Confirmation Email
 * Receives booking data and sends confirmation email to customer
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

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (empty($data['name']) || empty($data['email']) || empty($data['date']) || empty($data['time'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$name = htmlspecialchars($data['name']);
$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
$date = htmlspecialchars($data['date']);
$time = htmlspecialchars($data['time']);
$message = htmlspecialchars($data['message'] ?? 'No additional message');

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

// Format date for display
$dateObj = new DateTime($date);
$formattedDate = $dateObj->format('l, F j, Y');

// Email template for customer
$customerSubject = "Counselling Appointment Confirmed - " . SITE_NAME;
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
            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>🧘 " . SITE_NAME . "</h1>
            <p style='color: #ffffff; margin: 10px 0 0 0; font-size: 16px;'>Appointment Confirmation</p>
        </div>
        
        <!-- Content -->
        <div style='padding: 30px;'>
            <h2 style='color: #2C2C2C; margin-top: 0;'>Your appointment is confirmed, {$name}! ✅</h2>
            
            <p style='color: #5F5F5F; line-height: 1.6;'>
                We're looking forward to your counselling session. Here are the details:
            </p>
            
            <div style='background: linear-gradient(135deg, #7A9B8E 0%, #5B7C99 100%); padding: 25px; border-radius: 12px; margin: 25px 0; color: #ffffff;'>
                <h3 style='margin-top: 0; color: #ffffff; font-size: 20px;'>📅 Appointment Details</h3>
                <div style='background-color: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; margin-top: 15px;'>
                    <p style='margin: 8px 0; font-size: 16px;'><strong>Date:</strong> {$formattedDate}</p>
                    <p style='margin: 8px 0; font-size: 16px;'><strong>Time:</strong> {$time}</p>
                    <p style='margin: 8px 0; font-size: 16px;'><strong>Contact:</strong> {$email}</p>
                </div>
            </div>
            
            <div style='background-color: #fff9e6; padding: 20px; border-radius: 10px; border-left: 4px solid #d4af37; margin: 20px 0;'>
                <h4 style='color: #2C2C2C; margin-top: 0;'>Your Message:</h4>
                <p style='color: #5F5F5F; margin: 0; line-height: 1.6;'>{$message}</p>
            </div>
            
            <div style='background-color: #e8f4f1; padding: 15px; border-radius: 8px; border-left: 4px solid #7A9B8E; margin: 20px 0;'>
                <p style='margin: 0; color: #2C2C2C;'>
                    <strong>Before Your Appointment:</strong><br>
                    • Please arrive 5 minutes early<br>
                    • Bring any relevant documents or notes<br>
                    • Feel free to prepare questions you'd like to discuss
                </p>
            </div>
            
            <p style='color: #5F5F5F; line-height: 1.6;'>
                If you need to reschedule or have any questions, please contact us at 
                <a href='mailto:" . SUPPORT_EMAIL . "' style='color: #5B7C99;'>" . SUPPORT_EMAIL . "</a>
            </p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <p style='color: #7A9B8E; font-size: 18px; margin: 0;'>We're here to support you! 💚</p>
            </div>
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

// Send email to customer
$emailSent = sendEmail($email, $name, $customerSubject, $customerMessage);

// Also send notification to admin
$adminSubject = "New Counselling Appointment - " . SITE_NAME;
$adminMessage = "
<!DOCTYPE html>
<html>
<body style='font-family: Arial, sans-serif;'>
    <h2>New Counselling Appointment Booked</h2>
    <p><strong>Client:</strong> {$name}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Date:</strong> {$formattedDate}</p>
    <p><strong>Time:</strong> {$time}</p>
    <h3>Client Message:</h3>
    <p>{$message}</p>
</body>
</html>
";

sendEmail(ADMIN_EMAIL, SITE_NAME, $adminSubject, $adminMessage);

// Return response
if ($emailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Booking confirmation email sent successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email. Please contact support.'
    ]);
}
?>
