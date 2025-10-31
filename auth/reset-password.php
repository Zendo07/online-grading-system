<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['reset_email'])) {
    header('Location: ' . BASE_URL . 'auth/forgot-password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - indEx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>reset-password.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="icon-wrapper">
                <svg class="lock-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm3 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                </svg>
            </div>
            <h1>Reset Your Password</h1>
            <p class="subtitle">Secure your <span class="brand">indEx</span> account</p>
            <div class="email-badge">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                <span><?php echo htmlspecialchars($email); ?></span>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <?php if ($flash['type'] === 'danger'): ?>
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    <?php else: ?>
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    <?php endif; ?>
                </svg>
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Step 1: Verify Code -->
        <div class="step-container" id="codeSection">
            <div class="step-header">
                <div class="step-badge">1</div>
                <h3>Verification Code</h3>
            </div>
            <p class="step-description">Enter the 6-digit code sent to your email</p>
            
            <form id="verifyCodeForm">
                <div class="code-input-wrapper">
                    <input type="text" class="code-input" maxlength="1" id="digit1" required autocomplete="off">
                    <input type="text" class="code-input" maxlength="1" id="digit2" required autocomplete="off">
                    <input type="text" class="code-input" maxlength="1" id="digit3" required autocomplete="off">
                    <input type="text" class="code-input" maxlength="1" id="digit4" required autocomplete="off">
                    <input type="text" class="code-input" maxlength="1" id="digit5" required autocomplete="off">
                    <input type="text" class="code-input" maxlength="1" id="digit6" required autocomplete="off">
                </div>

                <div class="resend-wrapper">
                    <p class="resend-text">Didn't receive the code?</p>
                    <button type="button" class="resend-btn" id="resendBtn">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                        </svg>
                        Resend Code
                    </button>
                </div>

                <button type="submit" class="btn-primary" id="verifyBtn">
                    <span>Verify Code</span>
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Step 2: New Password -->
        <div class="step-container hidden" id="passwordSection">
            <div class="step-header">
                <div class="step-badge">2</div>
                <h3>New Password</h3>
            </div>
            <p class="step-description">Create a strong and secure password</p>

            <form action="<?php echo BASE_URL; ?>api/auth/reset-password-handler.php" method="POST" id="resetForm">
                <input type="hidden" name="digit1" id="hidden_digit1">
                <input type="hidden" name="digit2" id="hidden_digit2">
                <input type="hidden" name="digit3" id="hidden_digit3">
                <input type="hidden" name="digit4" id="hidden_digit4">
                <input type="hidden" name="digit5" id="hidden_digit5">
                <input type="hidden" name="digit6" id="hidden_digit6">

                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon-left" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm3 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                        </svg>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-control"
                            placeholder="Enter new password"
                            required
                            minlength="8"
                        >
                        <button type="button" class="input-icon-right" onclick="togglePassword('new_password')">
                            <svg class="eye-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength" id="strengthIndicator"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon-left" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control"
                            placeholder="Confirm new password"
                            required
                            minlength="8"
                        >
                        <button type="button" class="input-icon-right" onclick="togglePassword('confirm_password')">
                            <svg class="eye-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="password-match" id="matchIndicator"></div>
                </div>

                <div class="password-requirements">
                    <p class="requirements-title">Password must contain:</p>
                    <ul>
                        <li id="req-length">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                            At least 8 characters
                        </li>
                        <li id="req-match">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                            Passwords match
                        </li>
                    </ul>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <span>Reset Password</span>
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="<?php echo BASE_URL; ?>auth/login.php">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
                Back to Login
            </a>
        </div>
    </div>

    <!-- Pass BASE_URL to JavaScript -->
    <script>
        window.APP_BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    </script>
    <script src="<?php echo JS_PATH; ?>reset-password.js?v=<?php echo time(); ?>"></script>
</body>
</html>