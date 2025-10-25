<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Check if user has requested password reset
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
    <title>Reset Password - IndEX</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>login-new.css">
    <style>
        .reset-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .reset-header h1 {
            color: #7b2d26;
            margin-bottom: 10px;
        }
        .code-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }
        .code-digit {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .code-digit:focus {
            border-color: #f59e0b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        .email-display {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #f59e0b;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        .strength-weak { color: #ef4444; }
        .strength-medium { color: #f59e0b; }
        .strength-strong { color: #10b981; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="reset-icon">🔐</div>
            <h1>Reset Password</h1>
            <p>Enter the 6-digit code sent to:</p>
            <div class="email-display">
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 12px; border-radius: 8px;">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>api/auth/reset-password-handler.php" method="POST" id="resetForm">
            <label style="display: block; margin-bottom: 10px; font-weight: 600;">Reset Code:</label>
            <div class="code-input-container">
                <input type="text" class="code-digit" maxlength="1" id="digit1" name="digit1" required>
                <input type="text" class="code-digit" maxlength="1" id="digit2" name="digit2" required>
                <input type="text" class="code-digit" maxlength="1" id="digit3" name="digit3" required>
                <input type="text" class="code-digit" maxlength="1" id="digit4" name="digit4" required>
                <input type="text" class="code-digit" maxlength="1" id="digit5" name="digit5" required>
                <input type="text" class="code-digit" maxlength="1" id="digit6" name="digit6" required>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label for="new_password">New Password</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    placeholder="Enter new password" 
                    required
                    minlength="8"
                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;"
                >
                <div class="password-strength" id="strengthIndicator"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Confirm new password" 
                    required
                    minlength="8"
                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;"
                >
            </div>

            <button type="submit" class="login-btn" style="width: 100%; margin-top: 10px;">
                Reset Password
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p>Didn't receive the code? <a href="<?php echo BASE_URL; ?>auth/forgot-password.php" style="color: #7b2d26; font-weight: 600;">Request New Code</a></p>
            <p><a href="<?php echo BASE_URL; ?>auth/login.php" style="color: #7b2d26;">Back to Login</a></p>
        </div>
    </div>

    <script>
        // Auto-focus and move to next input
        const inputs = document.querySelectorAll('.code-digit');
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            // Only allow numbers
            input.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });

        // Auto-focus first input
        inputs[0].focus();

        // Password strength indicator
        const passwordInput = document.getElementById('new_password');
        const strengthIndicator = document.getElementById('strengthIndicator');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            if (strength <= 2) {
                strengthIndicator.textContent = '⚠️ Weak password';
                strengthIndicator.className = 'password-strength strength-weak';
            } else if (strength <= 3) {
                strengthIndicator.textContent = '✓ Medium strength';
                strengthIndicator.className = 'password-strength strength-medium';
            } else {
                strengthIndicator.textContent = '✓✓ Strong password';
                strengthIndicator.className = 'password-strength strength-strong';
            }
        });

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('❌ Passwords do not match!');
                return false;
            }

            if (newPassword.length < 8) {
                e.preventDefault();
                alert('❌ Password must be at least 8 characters long!');
                return false;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Resetting...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>