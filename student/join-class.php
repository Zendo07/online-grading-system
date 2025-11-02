<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Class - indEx</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>join-class.css?v=<?php echo time(); ?>">
    
    <style>
        /* Ensure body and main content don't scroll */
        body {
            overflow: hidden;
            height: 100vh;
        }
        
        .main-content {
            overflow: hidden;
            height: 100vh;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="join-class-container">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="page-title-section">
                            <h1 class="page-title">Join a New Class</h1>
                            <p class="page-subtitle">Enter the class code provided by your instructor to enroll</p>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="content-grid">
                    <!-- Join Form Card -->
                    <div class="join-card">
                        <div class="card-visual">
                            <div class="visual-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h2 class="visual-title">Ready to Learn?</h2>
                            <p class="visual-description">Join your class with a unique code</p>
                        </div>

                        <form action="<?php echo BASE_URL; ?>api/student/join-class-handler.php" method="POST" class="join-form" id="joinForm">
                            <div class="form-group">
                                <label for="class_code" class="form-label">
                                    <i class="fas fa-key"></i>
                                    Class Code
                                </label>
                                <input 
                                    type="text" 
                                    id="class_code" 
                                    name="class_code" 
                                    class="form-input" 
                                    placeholder="e.g., ABC-1234-XYZ"
                                    required
                                    maxlength="20"
                                    autocomplete="off"
                                >
                                <span class="input-hint">Enter the 6-12 character code from your instructor</span>
                            </div>

                            <button type="submit" class="btn-submit">
                                <span>Join Class Now</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Slider Container -->
                    <div class="slider-container">
                        <div class="slider-wrapper">
                            <!-- Instructions Card -->
                            <div class="slider-card info-card active">
                                <div class="info-header">
                                    <i class="fas fa-lightbulb"></i>
                                    <h3>How to Join</h3>
                                </div>

                                <div class="info-steps">
                                    <div class="step-item">
                                        <div class="step-number">1</div>
                                        <div class="step-content">
                                            <h4>Get Your Code</h4>
                                            <p>Ask your instructor for the unique class code</p>
                                        </div>
                                    </div>

                                    <div class="step-item">
                                        <div class="step-number">2</div>
                                        <div class="step-content">
                                            <h4>Enter Code</h4>
                                            <p>Type the code in the form on the left</p>
                                        </div>
                                    </div>

                                    <div class="step-item">
                                        <div class="step-number">3</div>
                                        <div class="step-content">
                                            <h4>Start Learning</h4>
                                            <p>Access course materials and track your progress</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-tip">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Pro Tip:</strong> You can join multiple classes. Each class has its own unique code.
                                    </div>
                                </div>
                            </div>

                            <!-- Benefits Card -->
                            <div class="slider-card benefits-card">
                                <div class="benefits-header">
                                    <i class="fas fa-star"></i>
                                    <h3>What You'll Get</h3>
                                </div>

                                <div class="benefits-list">
                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>Real-Time Grades</h4>
                                            <p>Track your academic performance instantly</p>
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>Attendance Records</h4>
                                            <p>Monitor your class participation</p>
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>Assignment Tracking</h4>
                                            <p>Never miss a submission deadline</p>
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-comments"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>Direct Communication</h4>
                                            <p>Stay connected with your instructor</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Slider Navigation -->
                        <button class="slider-nav prev" id="prevBtn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="slider-nav next" id="nextBtn">
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <!-- Slider Indicators -->
                        <div class="slider-indicators">
                            <button class="indicator active" data-slide="0"></button>
                            <button class="indicator" data-slide="1"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo JS_PATH; ?>main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>navigation.js?v=<?php echo time(); ?>"></script>
    <script>
        // Auto-uppercase and format class code
        document.getElementById('class_code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
        });

        // Form validation
        document.getElementById('joinForm').addEventListener('submit', function(e) {
            const classCode = document.getElementById('class_code').value.trim();
            
            if (classCode.length < 6) {
                e.preventDefault();
                alert('Class code must be at least 6 characters long.');
                return false;
            }
        });

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });

        // Slider functionality
        let currentSlide = 0;
        const sliderCards = document.querySelectorAll('.slider-card');
        const indicators = document.querySelectorAll('.indicator');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        function updateSlider(index) {
            // Remove active class from all cards and indicators
            sliderCards.forEach(card => card.classList.remove('active'));
            indicators.forEach(ind => ind.classList.remove('active'));

            // Add active class to current card and indicator
            sliderCards[index].classList.add('active');
            indicators[index].classList.add('active');

            // Update navigation buttons visibility
            prevBtn.style.opacity = index === 0 ? '0' : '1';
            prevBtn.style.pointerEvents = index === 0 ? 'none' : 'auto';
            nextBtn.style.opacity = index === sliderCards.length - 1 ? '0' : '1';
            nextBtn.style.pointerEvents = index === sliderCards.length - 1 ? 'none' : 'auto';

            currentSlide = index;
        }

        // Next button
        nextBtn.addEventListener('click', () => {
            if (currentSlide < sliderCards.length - 1) {
                updateSlider(currentSlide + 1);
            }
        });

        // Previous button
        prevBtn.addEventListener('click', () => {
            if (currentSlide > 0) {
                updateSlider(currentSlide - 1);
            }
        });

        // Indicator buttons
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                updateSlider(index);
            });
        });

        // Initialize slider
        updateSlider(0);

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' && currentSlide < sliderCards.length - 1) {
                updateSlider(currentSlide + 1);
            } else if (e.key === 'ArrowLeft' && currentSlide > 0) {
                updateSlider(currentSlide - 1);
            }
        });
    </script>
</body>
</html>