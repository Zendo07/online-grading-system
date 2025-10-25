<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - IndEX</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>login-new.css">
    <style>
        .forgot-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forgot-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .forgot-header h1 {
            color: #7b2d26;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="forgot-icon">🔒</div>
            <h1>Forgot Password?</h1>
            <p>Enter your email address and we'll send you a code to reset your password.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 12px; border-radius: 8px;">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>api/auth/forgot-password-handler.php" method="POST" id="forgotForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email" 
                    required
                    class="form-control"
                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;"
                >
            </div>

            <button type="submit" class="login-btn" style="width: 100%; margin-top: 10px;">
                Send Reset Code
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p>Remember your password? <a href="<?php echo BASE_URL; ?>auth/login.php" style="color: #7b2d26; font-weight: 600;">Back to Login</a></p>
        </div>
    </div>

    <script>
        document.getElementById('forgotForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>