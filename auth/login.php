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
    <title>IndEX - Login to your Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>login-new.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="login-section">
            <h2>Login to your Account</h2>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 1rem; padding: 0.75rem; border-radius: 8px; background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3);">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" action="<?php echo BASE_URL; ?>api/auth/login-handler.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="email" 
                        id="username" 
                        name="email" 
                        placeholder="Enter your username" 
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <div class="forgot-password">
                    <a href="<?php echo BASE_URL; ?>auth/forgot-password.php">Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="role-selection">
                <p>New here? Choose your role to get started!</p>
                
                <div class="role-buttons">
                    <a href="register.php?role=student" class="role-btn" id="studentBtn">
                        <i class="fas fa-user-graduate"></i>
                        <h3>I'm a Student</h3>
                        <p>Track progress, access lessons, and grow your skills.</p>
                    </a>
                    
                    <a href="register.php?role=teacher" class="role-btn" id="professorBtn">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h3>I'm a Professor</h3>
                        <p>Create courses, manage students, and share knowledge.</p>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="welcome-section">
            <div class="floating-circles" id="circlesContainer"></div>
            
            <div class="welcome-content">
                <h1>Welcome to IndEX</h1>
                <p>Your pocket-sized library for a digital tomorrow—an online index card that keeps knowledge neat, fast, and always within reach.</p>
                <p class="tagline">Let's explore a new tomorrow!</p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>login-new.js?v=<?php echo time(); ?>"></script>
</body>
</html>