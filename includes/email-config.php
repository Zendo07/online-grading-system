<?php

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

// Generate at: https://myaccount.google.com/apppasswords
define('SMTP_USERNAME', 'tracyannisip@gmail.com'); 
define('SMTP_PASSWORD', 'ibetublhbvxradfm');     

// "indEx System <noreply@index-grading.edu.ph>" or similar
define('SMTP_FROM_EMAIL', 'noreply@index-system.edu'); 
define('SMTP_FROM_NAME', 'indEx System'); 

// Reply-to email (where users should reply)
define('SMTP_REPLY_TO_EMAIL', 'support@index-system.edu'); 
define('SMTP_REPLY_TO_NAME', 'indEx Support');

// Email Templates Configuration
define('VERIFICATION_CODE_LENGTH', 6);
define('VERIFICATION_CODE_EXPIRY', 15); 
define('PASSWORD_RESET_EXPIRY', 15);    

define('USE_EMBEDDED_LOGO', false); 
define('EMAIL_LOGO_URL', ''); 

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
        
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_REPLY_TO_EMAIL, SMTP_REPLY_TO_NAME);
        $mail->addCustomHeader('X-Mailer', 'indEx Grading System');
        $mail->addCustomHeader('X-Priority', '1'); 
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return null;
    }
}

function generateVerificationCode() {
    return str_pad(rand(0, 999999), VERIFICATION_CODE_LENGTH, '0', STR_PAD_LEFT);
}

function getEmbeddedLogo() {
    $svg_logo = '<svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#7b2d26;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#5a1f18;stop-opacity:1" />
            </linearGradient>
        </defs>
        <rect width="120" height="120" rx="24" fill="url(#grad1)"/>
        <text x="60" y="75" font-family="Arial, sans-serif" font-size="48" font-weight="bold" fill="white" text-anchor="middle">indEx</text>
        <circle cx="60" cy="30" r="3" fill="white" opacity="0.8"/>
    </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg_logo);
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
        $mail->Subject = '🔐 Verify Your Email - indEx';
        
        $mail->Body = getVerificationEmailTemplate($name, $code);
        $mail->AltBody = "Hello $name,\n\nWelcome to indEx - Online Grading System!\n\nYour verification code is: $code\n\nThis code expires in " . VERIFICATION_CODE_EXPIRY . " minutes.\n\nIf you didn't create an account, please ignore this email.\n\nBest regards,\nindEx Team\nPampanga State University";
        
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
        $mail->Subject = '🔒 Password Reset Code - indEx';
        
        $mail->Body = getPasswordResetEmailTemplate($name, $code);
        $mail->AltBody = "Hello $name,\n\nYour password reset code for indEx is: $code\n\nThis code expires in " . PASSWORD_RESET_EXPIRY . " minutes.\n\nIf you didn't request a password reset, please ignore this email and ensure your account is secure.\n\nBest regards,\nindEx Team\nPampanga State University";
        
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - indEx</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6; 
            color: #333333; 
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-wrapper { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header { 
            background: linear-gradient(135deg, #7b2d26 0%, #5a1f18 100%); 
            color: white; 
            padding: 50px 30px; 
            text-align: center;
        }
        .header h1 { 
            font-size: 36px; 
            font-weight: 700; 
            margin: 0 0 10px;
            letter-spacing: -0.5px;
        }
        .header p { 
            font-size: 16px; 
            opacity: 0.9;
            font-weight: 400;
        }
        .content { 
            background: #ffffff; 
            padding: 40px 30px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 15px;
        }
        .message {
            font-size: 15px;
            color: #666666;
            line-height: 1.7;
            margin-bottom: 30px;
        }
        .code-box { 
            background: linear-gradient(135deg, #fef5f3 0%, #fff9f8 100%);
            border: 2px dashed #7b2d26; 
            border-radius: 12px; 
            padding: 30px 20px; 
            text-align: center; 
            margin: 30px 0;
        }
        .code-label {
            font-size: 13px;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .code { 
            font-size: 42px; 
            font-weight: 700; 
            color: #7b2d26; 
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .expiry {
            background: #fff3cd;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin: 25px 0;
            font-size: 14px;
            color: #92400e;
        }
        .expiry strong {
            font-weight: 700;
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 30px 0;
        }
        .security-note {
            background: #f8f5f2;
            border-radius: 8px;
            padding: 20px;
            font-size: 13px;
            color: #666666;
            line-height: 1.6;
        }
        .security-note strong {
            color: #7b2d26;
            font-weight: 600;
        }
        .footer { 
            background: #f8f5f2; 
            padding: 30px; 
            text-align: center; 
            font-size: 13px; 
            color: #666666;
        }
        .footer-logo {
            font-size: 18px;
            font-weight: 700;
            color: #7b2d26;
            margin-bottom: 10px;
        }
        .footer p {
            margin: 8px 0;
            line-height: 1.6;
        }
        .footer a {
            color: #7b2d26;
            text-decoration: none;
            font-weight: 600;
        }
        .social-links {
            margin-top: 20px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #7b2d26;
            text-decoration: none;
            font-size: 12px;
        }
        @media only screen and (max-width: 600px) {
            body { padding: 10px; }
            .email-wrapper { border-radius: 12px; }
            .header { padding: 40px 20px; }
            .header h1 { font-size: 28px; }
            .content { padding: 30px 20px; }
            .code { font-size: 36px; letter-spacing: 6px; }
            .footer { padding: 25px 20px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>indEx</h1>
            <p>Online Grading System</p>
        </div>
        
        <div class="content">
            <div class="greeting">Hello, {$name}! 👋</div>
            
            <div class="message">
                Thank you for registering with <strong>indEx</strong>, Please verify your email address to complete your registration and access your account.
            </div>
            
            <div class="code-box">
                <div class="code-label">Your Verification Code</div>
                <div class="code">{$code}</div>
            </div>
            
            <div class="expiry">
                <strong>⏰ Time Sensitive:</strong> This verification code will expire in <strong>15 minutes</strong>. Please enter it promptly on the verification page.
            </div>
            
            <div class="divider"></div>
            
            <div class="security-note">
                <strong>🔒 Security Notice:</strong> If you didn't create an account with indEx, please ignore this email. Your email address may have been entered by mistake. No account will be created without verification.
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-logo">indEx</div>
            <p><strong>Pampanga State University</strong></p>
            <p>Online Grading System</p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                &copy; 2025 Pampanga State University. All rights reserved.
            </p>
            <p style="font-size: 12px; opacity: 0.8;">
                This is an automated message, please do not reply to this email.
            </p>
            <div class="social-links">
                <a href="#">Help Center</a> • 
                <a href="#">Contact Support</a>
            </div>
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - indEx</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6; 
            color: #333333; 
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-wrapper { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header { 
            background: linear-gradient(135deg, #7b2d26 0%, #5a1f18 100%); 
            color: white; 
            padding: 50px 30px; 
            text-align: center;
        }
        .header h1 { 
            font-size: 36px; 
            font-weight: 700; 
            margin: 0 0 10px;
            letter-spacing: -0.5px;
        }
        .header p { 
            font-size: 16px; 
            opacity: 0.9;
            font-weight: 400;
        }
        .content { 
            background: #ffffff; 
            padding: 40px 30px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 15px;
        }
        .message {
            font-size: 15px;
            color: #666666;
            line-height: 1.7;
            margin-bottom: 30px;
        }
        .code-box { 
            background: linear-gradient(135deg, #fff9f0 0%, #fff3cd 100%);
            border: 2px solid #f59e0b; 
            border-radius: 12px; 
            padding: 30px 20px; 
            text-align: center; 
            margin: 30px 0;
        }
        .code-label {
            font-size: 13px;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .code { 
            font-size: 42px; 
            font-weight: 700; 
            color: #f59e0b; 
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .warning {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 8px;
            margin: 25px 0;
            font-size: 14px;
            color: #991b1b;
        }
        .warning strong {
            font-weight: 700;
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 30px 0;
        }
        .security-note {
            background: #f8f5f2;
            border-radius: 8px;
            padding: 20px;
            font-size: 13px;
            color: #666666;
            line-height: 1.6;
        }
        .security-note strong {
            color: #7b2d26;
            font-weight: 600;
        }
        .footer { 
            background: #f8f5f2; 
            padding: 30px; 
            text-align: center; 
            font-size: 13px; 
            color: #666666;
        }
        .footer-logo {
            font-size: 18px;
            font-weight: 700;
            color: #7b2d26;
            margin-bottom: 10px;
        }
        .footer p {
            margin: 8px 0;
            line-height: 1.6;
        }
        .social-links {
            margin-top: 20px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #7b2d26;
            text-decoration: none;
            font-size: 12px;
        }
        @media only screen and (max-width: 600px) {
            body { padding: 10px; }
            .email-wrapper { border-radius: 12px; }
            .header { padding: 40px 20px; }
            .header h1 { font-size: 28px; }
            .content { padding: 30px 20px; }
            .code { font-size: 36px; letter-spacing: 6px; }
            .footer { padding: 25px 20px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>🔒 Password Reset</h1>
            <p>indEx - Online Grading System</p>
        </div>
        
        <div class="content">
            <div class="greeting">Hello, {$name}!</div>
            
            <div class="message">
                You have requested to reset your password for your <strong>indEx</strong> account. Use the verification code below to proceed with creating a new password.
            </div>
            
            <div class="code-box">
                <div class="code-label">Password Reset Code</div>
                <div class="code">{$code}</div>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Alert:</strong> This password reset code will expire in <strong>15 minutes</strong>. If you didn't request a password reset, please ignore this email and contact support immediately.
            </div>
            
            <div class="divider"></div>
            
            <div class="security-note">
                <strong>🔒 Account Security Tips:</strong>
                <ul style="margin: 10px 0 0 20px; padding: 0;">
                    <li style="margin: 5px 0;">Never share your verification codes with anyone</li>
                    <li style="margin: 5px 0;">Use a strong, unique password</li>
                    <li style="margin: 5px 0;">Enable two-factor authentication when available</li>
                    <li style="margin: 5px 0;">Contact support if you notice suspicious activity</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-logo">indEx</div>
            <p><strong>Pampanga State University</strong></p>
            <p>Online Grading System</p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                &copy; 2025 Pampanga State University. All rights reserved.
            </p>
            <p style="font-size: 12px; opacity: 0.8;">
                This is an automated message, please do not reply to this email.
            </p>
            <div class="social-links">
                <a href="#">Help Center</a> • 
                <a href="#">Contact Support</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}