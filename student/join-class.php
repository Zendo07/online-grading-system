<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Class - indEx</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/join-class.css?v=<?php echo time(); ?>">
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

                <div class="content-grid">
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
                                    aria-label="Enter class code"
                                >
                                <span class="input-hint">Enter the 6-12 character code from your instructor</span>
                            </div>

                            <button type="submit" class="btn-submit" aria-label="Join class">
                                <span>Join Class Now</span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>

                    <div class="slider-container">
                        <div class="slider-wrapper">
                            <!-- Instructions Card -->
                            <div class="slider-card info-card active">
                                <div class="info-header">
                                    <i class="fas fa-lightbulb" aria-hidden="true"></i>
                                    <h3>How to Join</h3>
                                </div>

                                <div class="info-steps">
                                    <div class="step-item">
                                        <div class="step-number" aria-label="Step 1">1</div>
                                        <div class="step-content">
                                            <h4>Get Your Code</h4>
                                            <p>Ask your instructor for the unique class code</p>
                                        </div>
                                    </div>

                                    <div class="step-item">
                                        <div class="step-number" aria-label="Step 2">2</div>
                                        <div class="step-content">
                                            <h4>Enter Code</h4>
                                            <p>Type the code in the form on the left</p>
                                        </div>
                                    </div>

                                    <div class="step-item">
                                        <div class="step-number" aria-label="Step 3">3</div>
                                        <div class="step-content">
                                            <h4>Start Learning</h4>
                                            <p>Access course materials and track your progress</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-tip">
                                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                                    <div>
                                        <strong>Pro Tip:</strong> You can join multiple classes. Each class has its own unique code.
                                    </div>
                                </div>
                            </div>

                            <!-- Benefits Card -->
                            <div class="slider-card benefits-card">
                                <div class="benefits-header">
                                    <i class="fas fa-star" aria-hidden="true"></i>
                                    <h3>What You'll Get</h3>
                                </div>

                                <div class="benefits-list">
                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-chart-line" aria-hidden="true"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>Real-Time Grades</h4>
                                            <p>Track your academic performance instantly</p>
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>Attendance Records</h4>
                                            <p>Monitor your class participation</p>
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-clock" aria-hidden="true"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>24/7 Access to Your Academic Records</h4>
                                            <p>View your grades and progress anytime, anywhere</p>
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-chart-pie" aria-hidden="true"></i>
                                        </div>
                                        <div class="benefit-content">
                                            <h4>Performance Analytics Dashboard</h4>
                                            <p>Visualize your academic trends and insights</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Slider Navigation -->
                        <button class="slider-nav prev" id="prevBtn" aria-label="Previous slide">
                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <button class="slider-nav next" id="nextBtn" aria-label="Next slide">
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>

                        <!-- Slider Indicators -->
                        <div class="slider-indicators" role="tablist" aria-label="Slide navigation">
                            <button class="indicator active" data-slide="0" aria-label="Go to slide 1" role="tab" aria-selected="true"></button>
                            <button class="indicator" data-slide="1" aria-label="Go to slide 2" role="tab" aria-selected="false"></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo JS_PATH; ?>main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>navigation.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>student-pages/join-class.js?v=<?php echo time(); ?>"></script>
</body>
</html>