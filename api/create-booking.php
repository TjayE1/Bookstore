<?php
/**
 * API: Create Booking Appointment - SECURE VERSION
 * Endpoint: POST /api/create-booking.php
 * Body: { name, email, phone, date, time, message }
 */

// Load security configuration first
require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
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

$logger = new SecurityLogger('bookings.log');

// Server-side validation
try {
    // Validate required fields
    $required = ['name', 'email', 'date', 'time'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || ($data[$field] === '' && $field !== 'phone' && $field !== 'message')) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate name
    $name = Validator::name($data['name'] ?? '');
    if ($name === false) {
        throw new Exception('Invalid name format. Use letters, spaces, hyphens, and apostrophes only.');
    }
    
    // Validate email
    $email = Validator::email($data['email'] ?? '');
    if ($email === false) {
        throw new Exception('Invalid email address');
    }
    
    // Validate phone (optional)
    $phone = '';
    if (!empty($data['phone'])) {
        $phone = Validator::phone($data['phone']);
        if ($phone === false) {
            throw new Exception('Invalid phone number');
        }
    }
    
    // Validate date
    $date = Validator::date($data['date'] ?? '');
    if ($date === false) {
        throw new Exception('Invalid date format or date is in the past');
    }
    
    // Validate time
    $time = Validator::time($data['time'] ?? '');
    if ($time === false) {
        throw new Exception('Invalid time format (HH:MM)');
    }
    
    // Validate message (optional)
    $message = '';
    if (!empty($data['message'])) {
        $message = Validator::text($data['message'], 1000, 0);
        if ($message === false) {
            throw new Exception('Message is too long (max 1000 characters)');
        }
    }

    
    // Database transaction
    $conn->begin_transaction();
    
    try {
        // Check if date is unavailable using prepared statement
        $unavailableQuery = "SELECT id FROM unavailable_dates WHERE unavailable_date = ? LIMIT 1";
        $isUnavailable = getRow($unavailableQuery, [$date]);
        
        if ($isUnavailable) {
            throw new Exception('This date is not available');
        }
        
        // Check if time slot is already booked for this date
        $bookingCheckQuery = "SELECT id FROM bookings WHERE booking_date = ? AND booking_time = ? AND status IN ('pending', 'confirmed') LIMIT 1";
        $existingBooking = getRow($bookingCheckQuery, [$date, $time]);
        
        if ($existingBooking) {
            throw new Exception('This time slot is already booked');
        }
        
        // Create booking with secure number generation
        $bookingNumber = 'BOOK-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
        
        $query = "INSERT INTO bookings (booking_number, customer_name, customer_email, customer_phone, booking_date, booking_time, notes, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = executeQuery($query, [
            $bookingNumber,
            $name,
            $email,
            $phone,
            $date,
            $time,
            $message
        ]);
        
        if (!$stmt) {
            throw new Exception('Failed to create booking');
        }
        
        $bookingId = getLastInsertId();
        
        // Commit transaction
        $conn->commit();
        
        // Log successful booking
        $logger->log('BOOKING_CREATED', [
            'booking_id' => $bookingId,
            'booking_number' => $bookingNumber,
            'customer_email' => $email,
            'booking_date' => $date
        ]);
        
        // Send confirmation email directly
        error_log("Sending booking confirmation email for: $bookingNumber");
        
        // Customer email - warm and encouraging
        $customerSubject = "Your Counselling Session is Confirmed ✨ - " . SITE_NAME;
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
            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>✨ Reader's Haven Counselling</h1>
            <p style='color: #ffffff; margin: 10px 0 0 0; font-size: 16px;'>Your Session is Confirmed</p>
        </div>
        
        <!-- Content -->
        <div style='padding: 30px;'>
            <h2 style='color: #2C2C2C; margin-top: 0;'>Hello $name,</h2>
            
            <p style='color: #5F5F5F; line-height: 1.6; font-size: 15px;'>
                We're grateful you've taken this important step. Your counselling session has been confirmed, and we're honored to be part of your journey. Remember: <strong>it's better to do life with someone</strong>. You're not alone, and we're here to support you.
            </p>
            
            <div style='background-color: #e8f4f1; padding: 20px; border-radius: 10px; border-left: 4px solid #7A9B8E; margin: 20px 0;'>
                <h3 style='color: #2C2C2C; margin-top: 0; margin-bottom: 15px;'>📅 Your Session Details</h3>
                <p style='color: #555; margin: 8px 0;'><strong>Session #:</strong> $bookingNumber</p>
                <p style='color: #555; margin: 8px 0;'><strong>Date:</strong> $date</p>
                <p style='color: #555; margin: 8px 0;'><strong>Time:</strong> $time</p>
            </div>
            
            <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                <p style='margin: 0; color: #333;'><strong>💡 What to Expect:</strong> Come as you are. Share what feels comfortable. There's no pressure to have it all figured out. We're here to listen, support, and help you find your way forward.</p>
            </div>
            
            <p style='color: #5F5F5F; line-height: 1.6; font-size: 15px; margin-top: 25px;'>
                If you have any questions or need to reschedule, please reach out at <a href='mailto:" . SUPPORT_EMAIL . "' style='color: #5B7C99;'>" . SUPPORT_EMAIL . "</a>
            </p>
            
            <p style='color: #5F5F5F; line-height: 1.6; font-size: 15px; margin-top: 15px;'>
                <strong>Taking care of yourself matters. We're excited to support you. ❤️</strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div style='background-color: #2C2C2C; padding: 20px; text-align: center;'>
            <p style='color: #ffffff; margin: 0; font-size: 14px;'>
                &copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.
            </p>
            <p style='color: #aaa; margin: 10px 0 0 0; font-size: 12px;'>
                Counselling & Books - Your Safe Space for Growth
            </p>
        </div>
    </div>
</body>
</html>
";
        sendEmail($email, $name, $customerSubject, $customerMessage);
        
        // Admin notification
        $adminSubject = "New Booking - " . SITE_NAME;
        $adminMessage = "
<!DOCTYPE html>
<html>
<body style='font-family: Arial, sans-serif;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2>New Booking Received</h2>
        <p><strong>Booking #:</strong> $bookingNumber</p>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Date:</strong> $date</p>
        <p><strong>Time:</strong> $time</p>
        <p><strong>Message:</strong> $message</p>
    </div>
</body>
</html>
";
        sendEmail(ADMIN_EMAIL, SITE_NAME, $adminSubject, $adminMessage);
        error_log("Booking emails sent");
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'bookingId' => $bookingId,
            'bookingNumber' => $bookingNumber
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        
        // Log error
        $logger->log('BOOKING_ERROR', [
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
