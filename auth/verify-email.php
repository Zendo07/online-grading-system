<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

// Check if user is pending verification
if (!isset($_SESSION['pending_verification_user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$user_id = $_SESSION['pending_verification_user_id'];
$email = $_SESSION['pending_verification_email'];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - IndEX</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>login-new.css">
    <style>
        .verify-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        .verify-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .verify-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .verify-header h1 {
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
            border-color: #7b2d26;
            outline: none;
            box-shadow: 0 0 0 3px rgba(123, 45, 38, 0.1);
        }
        .resend-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .timer {
            font-weight: bold;
            color: #f59e0b;
        }
        .email-display {
            background: #f8f5f2;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <div class="verify-icon">📧</div>
            <h1>Verify Your Email</h1>
            <p>We've sent a 6-digit code to:</p>
            <div class="email-display">
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 12px; border-radius: 8px;">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>api/auth/verify-email-handler.php" method="POST" id="verifyForm">
            <div class="code-input-container">
                <input type="text" class="code-digit" maxlength="1" id="digit1" name="digit1" required>
                <input type="text" class="code-digit" maxlength="1" id="digit2" name="digit2" required>
                <input type="text" class="code-digit" maxlength="1" id="digit3" name="digit3" required>
                <input type="text" class="code-digit" maxlength="1" id="digit4" name="digit4" required>
                <input type="text" class="code-digit" maxlength="1" id="digit5" name="digit5" required>
                <input type="text" class="code-digit" maxlength="1" id="digit6" name="digit6" required>
            </div>

            <button type="submit" class="login-btn" style="width: 100%;">
                Verify Email
            </button>
        </form>

        <div class="resend-section">
            <p>Didn't receive the code?</p>
            <button id="resendBtn" class="login-btn" style="background: #6b7280; width: 100%;" onclick="resendCode()">
                Resend Code <span class="timer" id="timer"></span>
            </button>
            <p style="margin-top: 15px; font-size: 14px;">
                <a href="<?php echo BASE_URL; ?>auth/login.php" style="color: #7b2d26;">Back to Login</a>
            </p>
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

        // Resend timer
        let countdown = 60;
        let timerInterval;

        function startTimer() {
            const timerEl = document.getElementById('timer');
            const resendBtn = document.getElementById('resendBtn');
            
            resendBtn.disabled = true;
            resendBtn.style.opacity = '0.5';
            
            timerInterval = setInterval(() => {
                countdown--;
                timerEl.textContent = `(${countdown}s)`;
                
                if (countdown <= 0) {
                    clearInterval(timerInterval);
                    timerEl.textContent = '';
                    resendBtn.disabled = false;
                    resendBtn.style.opacity = '1';
                    countdown = 60;
                }
            }, 1000);
        }

        function resendCode() {
            fetch('<?php echo BASE_URL; ?>api/auth/resend-verification.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Verification code resent! Check your email.');
                    startTimer();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error resending code. Please try again.');
            });
        }

        // Start timer on page load
        startTimer();
    </script>
</body>
</html>