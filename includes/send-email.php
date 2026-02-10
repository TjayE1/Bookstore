<?php
/**
 * Email Sending Helper Function
 * Uses PHPMailer to send emails via SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using PHPMailer
 */
function sendEmail($toEmail, $toName, $subject, $htmlBody) {
    // Check if emails are enabled
    if (!defined('ENABLE_EMAILS') || !ENABLE_EMAILS) {
        error_log("Email sending disabled. Would send to: $toEmail");
        return ['success' => false, 'message' => 'Email sending is disabled'];
    }

    error_log("sendEmail called - to: $toEmail, subject: $subject");

    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug = 0; // Disable debug output to avoid corrupting JSON responses
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Disable SSL certificate verification for local development
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        error_log("Connecting to SMTP: " . SMTP_HOST . ":" . SMTP_PORT);

        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        
        // Add admin CC for customer emails
        if (defined('ADMIN_EMAIL') && $toEmail !== ADMIN_EMAIL) {
            $mail->addCC(ADMIN_EMAIL);
            error_log("Added CC to admin: " . ADMIN_EMAIL);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        
        // Plain text version
        $mail->AltBody = strip_tags($htmlBody);

        // Send
        error_log("Attempting to send email...");
        $mail->send();
        
        error_log("Email sent successfully to: $toEmail");
        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        $errorMsg = "Email error: " . $e->getMessage();
        error_log($errorMsg);
        if (isset($mail)) {
            error_log("PHPMailer error: " . $mail->ErrorInfo);
        }
        return ['success' => false, 'message' => $errorMsg, 'error' => $e->getMessage()];
    }
}

?>
