<?php
// Email Configuration using PHPMailer
// NOTE: This file should be included AFTER config.php and functions.php

// Load PHPMailer - try multiple possible locations
$phpmailer_loaded = false;
$phpmailer_paths = [
    __DIR__ . '/../PHPMailer-6.9.1/src/PHPMailer.php',  // Version-specific folder (6.9.1)
    __DIR__ . '/../PHPMailer/src/PHPMailer.php',         // Generic folder
    __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php' // Composer
];

foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        require_once dirname($path) . '/SMTP.php';
        require_once dirname($path) . '/Exception.php';
        $phpmailer_loaded = true;
        break;
    }
}

// If PHPMailer not found, show helpful error
if (!$phpmailer_loaded) {
    $error_msg = "
    <h2>❌ PHPMailer Not Found!</h2>
    <p><strong>To fix this error:</strong></p>
    <ol>
        <li>Download PHPMailer from: <a href='https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip' target='_blank'>Download Here</a></li>
        <li>Extract the ZIP file</li>
        <li>Copy the 'src' folder from the extracted files</li>
        <li>Create folder: <code>C:\\xampp\\htdocs\\online-grading-system\\PHPMailer\\</code></li>
        <li>Paste the 'src' folder inside the PHPMailer folder</li>
        <li>Refresh this page</li>
    </ol>
    <p><strong>Expected location:</strong> <code>" . __DIR__ . "/../PHPMailer/src/PHPMailer.php</code></p>
    <p><strong>Paths checked:</strong></p>
    <ul>";
    
    foreach ($phpmailer_paths as $path) {
        $exists = file_exists($path) ? '✅ Found' : '❌ Not found';
        $error_msg .= "<li>$exists: <code>$path</code></li>";
    }
    
    $error_msg .= "
    </ul>
    <hr>
    <p><small>Or install via Composer: <code>composer require phpmailer/phpmailer</code></small></p>
    ";
    
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>PHPMailer Not Found</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h2 { color: #ef4444; }
            code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; color: #d97706; }
            ol, ul { line-height: 1.8; }
            a { color: #7b2d26; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            $error_msg
        </div>
    </body>
    </html>
    ");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Email Configuration Constants
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);

// ⚠️ IMPORTANT: Replace these with your Gmail credentials
// Use Gmail App Password (not your regular password)
// Generate at: https://myaccount.google.com/apppasswords
define('SMTP_USERNAME', 'tracyannisip@gmail.com'); // Replace with your Gmail
define('SMTP_PASSWORD', 'ibetublhbvxradfm');     // Replace with Gmail App Password

define('SMTP_FROM_EMAIL', 'tracyannisip@gmail.com'); // Same as SMTP_USERNAME
define('SMTP_FROM_NAME', 'IndEx Online Grading System');

// Email Templates Configuration
define('VERIFICATION_CODE_LENGTH', 6);
define('VERIFICATION_CODE_EXPIRY', 15); // minutes
define('PASSWORD_RESET_EXPIRY', 15);    // minutes

/**
 * Initialize PHPMailer with Gmail SMTP settings
 */
function getMailer() {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Set charset
        $mail->CharSet = 'UTF-8';
        
        // Sender info
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return null;
    }
}

/**
 * Generate a random 6-digit verification code
 */
function generateVerificationCode() {
    return str_pad(rand(0, 999999), VERIFICATION_CODE_LENGTH, '0', STR_PAD_LEFT);
}

/**
 * Send email verification code
 */
function sendVerificationEmail($email, $name, $code) {
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - IndEx';
        
        $mail->Body = getVerificationEmailTemplate($name, $code);
        $mail->AltBody = "Hello $name,\n\nYour verification code is: $code\n\nThis code expires in " . VERIFICATION_CODE_EXPIRY . " minutes.\n\nIf you didn't create an account, please ignore this email.";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send password reset code
 */
function sendPasswordResetEmail($email, $name, $code) {
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - IndEX';
        
        $mail->Body = getPasswordResetEmailTemplate($name, $code);
        $mail->AltBody = "Hello $name,\n\nYour password reset code is: $code\n\nThis code expires in " . PASSWORD_RESET_EXPIRY . " minutes.\n\nIf you didn't request a password reset, please ignore this email.";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Verification Email HTML Template
 */
function getVerificationEmailTemplate($name, $code) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #7b2d26 0%, #D96C3D 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
        .code-box { background: #f8f5f2; border: 2px dashed #7b2d26; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .code { font-size: 32px; font-weight: bold; color: #7b2d26; letter-spacing: 5px; }
        .footer { background: #f8f5f2; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 indEx</h1>
            <p>Online Grading System</p>
        </div>
        <div class="content">
            <h2>Hello, {$name}!</h2>
            <p>Thank you for registering with indEx. Please verify your email address to complete your registration.</p>
            
            <div class="code-box">
                <p style="margin: 0; font-size: 14px; color: #666;">Your Verification Code</p>
                <div class="code">{$code}</div>
            </div>
            
            <p><strong>This code will expire in 15 minutes.</strong></p>
            
            <p>Enter this code on the verification page to activate your account.</p>
            
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
            
            <p style="font-size: 12px; color: #666;">
                <strong>Security Note:</strong> If you didn't create an account with indEx, please ignore this email.
            </p>
        </div>
        <div class="footer">
            <p>&copy; 2025 Pampanga State University. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Password Reset Email HTML Template
 */
function getPasswordResetEmailTemplate($name, $code) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #7b2d26 0%, #D96C3D 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
        .code-box { background: #fff3cd; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .code { font-size: 32px; font-weight: bold; color: #f59e0b; letter-spacing: 5px; }
        .footer { background: #f8f5f2; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
        .warning { background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Password Reset</h1>
            <p>indEx - Online Grading System</p>
        </div>
        <div class="content">
            <h2>Hello, {$name}!</h2>
            <p>You have requested to reset your password. Use the code below to proceed:</p>
            
            <div class="code-box">
                <p style="margin: 0; font-size: 14px; color: #92400e;">Your Password Reset Code</p>
                <div class="code">{$code}</div>
            </div>
            
            <p><strong>This code will expire in 15 minutes.</strong></p>
            
            <div class="warning">
                <strong>⚠️ Security Alert:</strong> If you didn't request a password reset, please ignore this email.
            </div>
            
            <p>Enter this code on the password reset page to create a new password.</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 Pampanga State University. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
}