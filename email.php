<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure this path is correct based on your setup

// ✅ EMAIL CONFIGURATION (Update with your actual SMTP details)
define('SMTP_HOST', 'smtp.gmail.com');       // e.g., smtp.gmail.com, smtp.sendgrid.net
define('SMTP_PORT', 587);                    // 587 for TLS, 465 for SSL
define('SMTP_USER', 'simplestsynergy@gmail.com'); // Your sending email address
define('SMTP_PASS', 'xojwztwlqswqrpwu');    // Gmail App Password (NOT your regular password)
define('SMTP_FROM_NAME', 'campus_delivery');

/**
 * Send an HTML Email
 */
function sendCampusEmail($toEmail, $toName, $subject, $htmlContent) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Wrap content in our branded template
        $template = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
                .header { background-color: #dc3545; color: #ffffff; padding: 25px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; color: #333333; line-height: 1.6; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef; }
                .btn { display: inline-block; padding: 12px 24px; background-color: #dc3545; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'><h1>🚀 Campus Delivery</h1></div>
                <div class='content'>{$htmlContent}</div>
                <div class='footer'>&copy; " . date('Y') . " Campus Delivery. All rights reserved.<br>Please do not reply to this automated email.</div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $template;
        $mail->AltBody = strip_tags($htmlContent); // Fallback for plain text email clients
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error, but don't break the main app flow
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>