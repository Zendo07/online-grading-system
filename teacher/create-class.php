<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>Create New Class</h1>
                    <p class="breadcrumb">Home / Create Class</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
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
                            <h2 class="card-title">Class Information</h2>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>api/teacher/create-class-handler.php" method="POST" id="createClassForm">
                                <div class="form-group">
                                    <label for="className" class="form-label">Class Name <span style="color: red;">*</span></label>
                                    <input 
                                        type="text" 
                                        id="className" 
                                        name="class_name" 
                                        class="form-control" 
                                        placeholder="e.g., Computer Programming 101"
                                        required
                                        maxlength="100"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject" class="form-label">Subject <span style="color: red;">*</span></label>
                                    <input 
                                        type="text" 
                                        id="subject" 
                                        name="subject" 
                                        class="form-control" 
                                        placeholder="e.g., Computer Science"
                                        required
                                        maxlength="100"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="section" class="form-label">Section <span style="color: red;">*</span></label>
                                    <input 
                                        type="text" 
                                        id="section" 
                                        name="section" 
                                        class="form-control" 
                                        placeholder="e.g., A, B, 1-A"
                                        required
                                        maxlength="50"
                                    >
                                </div>
                                
                                <div class="alert alert-info">
                                    <strong>📌 Note:</strong> A unique class code will be automatically generated. Share this code with your students so they can join the class.
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    Create Class
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Instructions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">📖 Instructions</h2>
                        </div>
                        <div class="card-body">
                            <h4>How to create a class:</h4>
                            <ol style="padding-left: 1.5rem; line-height: 2;">
                                <li>Enter the class name (e.g., "Math 101")</li>
                                <li>Specify the subject</li>
                                <li>Add the section</li>
                                <li>Click "Create Class"</li>
                                <li>A unique class code will be generated</li>
                                <li>Share the code with students</li>
                            </ol>
                            
                            <hr style="margin: 1.5rem 0;">
                            
                            <h4>What happens next?</h4>
                            <ul style="padding-left: 1.5rem; line-height: 2;">
                                <li>System generates a unique class code</li>
                                <li>Students can join using the code</li>
                                <li>No manual approval needed</li>
                                <li>Students are automatically enrolled</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        // Form validation
        document.getElementById('createClassForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>