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
    <title>Join Class - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>Join Class</h1>
                    <p class="breadcrumb">Home / Join Class</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Enter Class Code</h2>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>api/student/join-class-handler.php" method="POST">
                                <div class="form-group">
                                    <label for="classCode" class="form-label">Class Code <span style="color: red;">*</span></label>
                                    <input 
                                        type="text" 
                                        id="classCode" 
                                        name="class_code" 
                                        class="form-control" 
                                        placeholder="Enter class code (e.g., PSU123)"
                                        required
                                        maxlength="20"
                                        style="text-transform: uppercase;"
                                    >
                                    <small class="form-text">Ask your teacher for the class code</small>
                                </div>
                                
                                <div class="alert alert-info">
                                    <strong>📌 Note:</strong> Once you enter a valid class code, you will be automatically enrolled in the class. No approval needed!
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    Join Class
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Instructions -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">📖 How to Join a Class</h2>
                        </div>
                        <div class="card-body">
                            <ol style="padding-left: 1.5rem; line-height: 2;">
                                <li>Get the class code from your teacher</li>
                                <li>Enter the code in the form</li>
                                <li>Click "Join Class"</li>
                                <li>You'll be automatically enrolled!</li>
                            </ol>
                            
                            <hr style="margin: 1.5rem 0;">
                            
                            <h4>What happens after joining?</h4>
                            <ul style="padding-left: 1.5rem; line-height: 2;">
                                <li>You can view your grades</li>
                                <li>Check your attendance</li>
                                <li>See class information</li>
                                <li>Access all class materials</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        // Auto-uppercase class code input
        document.getElementById('classCode').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>