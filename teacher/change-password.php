<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Online Grading System</title>
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
                    <h1>Change Password</h1>
                    <p class="breadcrumb">Home / Change Password</p>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card" style="max-width: 600px;">
                    <div class="card-header">
                        <h2 class="card-title">Update Your Password</h2>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo BASE_URL; ?>api/teacher/change-password-handler.php" method="POST" id="changePasswordForm">
                            <div class="form-group">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input 
                                    type="password" 
                                    id="currentPassword" 
                                    name="current_password" 
                                    class="form-control" 
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input 
                                    type="password" 
                                    id="newPassword" 
                                    name="new_password" 
                                    class="form-control" 
                                    minlength="8"
                                    required
                                >
                                <small class="form-text">Minimum 8 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input 
                                    type="password" 
                                    id="confirmPassword" 
                                    name="confirm_password" 
                                    class="form-control" 
                                    minlength="8"
                                    required
                                >
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>