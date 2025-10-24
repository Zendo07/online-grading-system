<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Check for logout message
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']); // remove after displaying
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Grading System - Home</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>landing.css?v=<?php echo time(); ?>">
</head>
<body class="landing-page">
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="index.php" class="navbar-brand">📚 indEx</a>
            <ul class="navbar-nav">
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="auth/login.php" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #8B4049 0%, #6B3039 100%); color: white;">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" style="background: linear-gradient(135deg, #7b2d26 0%, #D96C3D 100%);">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome to IndEX</h1>
                <p>Your pocket-sized library for a digital tomorrow—an online index card that keeps knowledge neat, fast, and always within reach.</p>
                <div class="hero-buttons">
                    <a href="auth/login.php" class="btn btn-light btn-lg" style="background: white; color: #7b2d26;">Get Started</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Our System?</h2>
                <p>Powerful features designed for teachers and students</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3>For Teachers</h3>
                    <p>Create classes, manage students, track attendance, and input grades efficiently.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👨‍🎓</div>
                    <h3>For Students</h3>
                    <p>View grades, check attendance, and stay updated on your academic progress.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Real-time Updates</h3>
                    <p>Get instant access to grades and attendance records anytime, anywhere.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Private</h3>
                    <p>Your data is protected with industry-standard security measures.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile Friendly</h3>
                    <p>Access the system from any device - desktop, tablet, or smartphone.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3>Easy to Use</h3>
                    <p>Intuitive interface designed for users of all technical levels.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section" id="about">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of teachers and students using our platform</p>
            <div class="hero-buttons">
                <a href="auth/login.php" class="btn btn-light btn-lg">Sign Up Now</a>
                <a href="auth/login.php" class="btn btn-outline btn-lg">Login</a>
            </div>
        </div>
    </section>

    <div class="footer">
    <p>&copy; 2025 Pampanga State University. All rights reserved.</p>
    <a href="#">FAQs</a>
  </div>
</body>
</html>