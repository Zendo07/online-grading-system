<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Get flash message if any
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>auth.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <!-- Left Panel -->
        <div class="auth-left">
            <div class="auth-logo-section">
                <img src="<?php echo IMG_PATH; ?>psu-logo.png" alt="PSU Logo" class="auth-logo-img" onerror="this.style.display='none'">
                <h1 class="auth-title-large">Welcome to indEx</h1>
                <p class="auth-subtitle-large">
                    Your pocket-sized library for a digital tomorrow—an online index card that keeps knowledge neat, fast, and always within reach.
                </p>
            </div>
            
            <div class="auth-role-cards">
                <a href="register.php?role=student" class="role-card">
                    <div class="role-card-icon">🎓</div>
                    <div class="role-card-title">I'm a Student</div>
                    <p class="role-card-desc">Track progress, access lessons, and grow your skills</p>
                </a>
                
                <a href="register.php?role=teacher" class="role-card">
                    <div class="role-card-icon">👨‍🏫</div>
                    <div class="role-card-title">I'm a Professor</div>
                    <p class="role-card-desc">Create courses, manage students, and share knowledge</p>
                </a>
            </div>
        </div>
        
        <!-- Right Panel -->
        <div class="auth-right">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h2 class="auth-title">Login to your Account</h2>
                    <p class="auth-subtitle">Enter your credentials to access your account</p>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>api/auth/login-handler.php" method="POST" class="auth-form" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Username</label>
                        <div class="input-with-icon">
                            <span class="input-icon">👤</span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="Enter your email"
                                required
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                    </div>

                    <div class="auth-actions">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-link">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-auth btn-auth-primary" style="background: linear-gradient(135deg, #8B4049 0%, #6B3039 100%); color: white; box-shadow: 0 4px 12px rgba(139, 64, 73, 0.3);">
                        Login
                    </button>
                </form>

                <div class="auth-divider">
                    <span>New here? Choose your role to get started!</span>
                </div>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo JS_PATH; ?>auth.js"></script>
</body>
</html>