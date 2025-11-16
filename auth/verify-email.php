<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/email-config.php';

// Check if user has pending registration
if (!isset($_SESSION['pending_registration'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$email = $_SESSION['pending_registration']['email'];
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - indEx</title>
    <meta name="description" content="Enter the verification code sent to your email to complete your indEx registration.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>verify-email.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="verify-container">
        <div class="verification-header">
            <div class="verify-icon-wrapper">
                <svg class="verify-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/>
                </svg>
            </div>
            <h1>Verify Your Email</h1>
            <p>We've sent a 6-digit verification code from <strong>indEx</strong> to:</p>
            <div class="email-display">
                <svg class="email-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/>
                </svg>
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <?php if ($flash['type'] === 'danger'): ?>
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>
                    <?php else: ?>
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                    <?php endif; ?>
                </svg>
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <div class="code-input-section">
            <div class="section-label">
                <svg class="label-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" fill="currentColor"/>
                </svg>
                Enter Verification Code
            </div>
            
            <form action="<?php echo BASE_URL; ?>api/auth/verify-email-handler.php" method="POST" id="verifyForm">
                <div class="code-inputs">
                    <input type="text" class="code-input" maxlength="1" id="digit1" name="digit1" required autocomplete="off" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="code-input" maxlength="1" id="digit2" name="digit2" required autocomplete="off" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="code-input" maxlength="1" id="digit3" name="digit3" required autocomplete="off" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="code-input" maxlength="1" id="digit4" name="digit4" required autocomplete="off" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="code-input" maxlength="1" id="digit5" name="digit5" required autocomplete="off" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="code-input" maxlength="1" id="digit6" name="digit6" required autocomplete="off" inputmode="numeric" pattern="[0-9]">
                </div>

                <button type="submit" class="verify-btn" id="submitBtn">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                    </svg>
                    <span>Verify Email</span>
                </button>
            </form>
        </div>

        <div class="resend-section">
            <p>Didn't receive the code?</p>
            <form action="<?php echo BASE_URL; ?>api/auth/resend-code-handler.php" method="POST" id="resendForm">
                <button type="submit" class="resend-btn" id="resendBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" fill="currentColor"/>
                    </svg>
                    <span>Resend Code</span>
                </button>
            </form>
        </div>

        <div class="security-note">
            <svg class="security-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z" fill="currentColor"/>
            </svg>
            <p>For your security, this verification code will expire in <strong>15 minutes</strong>. If you didn't create an account, please ignore this email.</p>
        </div>

        <div class="back-link">
            <a href="<?php echo BASE_URL; ?>auth/login.php">
                <svg class="back-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" fill="currentColor"/>
                </svg>
                Back to Login
            </a>
        </div>
    </div>

    <script src="<?php echo JS_PATH; ?>verify-email.js?v=<?php echo time(); ?>"></script>
</body>
</html>