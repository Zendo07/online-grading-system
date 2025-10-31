<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$student_id = $_SESSION['user_id'];

// Get student data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Profile Error: " . $e->getMessage());
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-content">
                <div style="margin-bottom: 24px;">
                    <h1 style="font-size: 2rem; margin: 0 0 8px 0; color: #202124;">My Profile</h1>
                    <p style="color: #5f6368; margin: 0;">Home / Profile</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Profile Information</h2>
                    </div>
                    <div class="card-body">
                        <!-- Profile Picture Section -->
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="position: relative; display: inline-block;">
                                <img 
                                    src="<?php echo getProfilePicture($student['profile_picture'], $student['full_name']); ?>" 
                                    alt="Profile Picture" 
                                    id="profilePreview"
                                    style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); box-shadow: var(--shadow-lg);"
                                >
                                <label for="profilePicInput" style="position: absolute; bottom: 5px; right: 5px; background: var(--primary-color); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: var(--shadow);">
                                    📷
                                </label>
                                <input 
                                    type="file" 
                                    id="profilePicInput" 
                                    accept="image/*" 
                                    style="display: none;"
                                    onchange="uploadProfilePicture(this)"
                                >
                            </div>
                            <p style="margin-top: 1rem; color: var(--text-light); font-size: 0.875rem;">Click camera icon to change profile picture</p>
                        </div>
                        
                        <form action="<?php echo BASE_URL; ?>api/student/update-profile.php" method="POST">
                            <div class="form-group">
                                <label for="fullName" class="form-label">Full Name</label>
                                <input 
                                    type="text" 
                                    id="fullName" 
                                    name="full_name" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($student['full_name']); ?>"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($student['email']); ?>"
                                    disabled
                                >
                                <small class="form-text">Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="Student"
                                    disabled
                                >
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Account Created</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="<?php echo formatDateTime($student['created_at']); ?>"
                                    disabled
                                >
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script src="<?php echo JS_PATH; ?>dashboard-nav.js"></script>
    <script>
        function uploadProfilePicture(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', input.files[0]);
                
                // Preview image immediately
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
                
                // Upload via AJAX
                fetch('<?php echo BASE_URL; ?>api/student/upload-profile-picture.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update preview
                        document.getElementById('profilePreview').src = data.picture_url;
                        
                        // Update navbar profile image
                        const navbarImg = document.querySelector('.profile-button img');
                        if (navbarImg) {
                            navbarImg.src = data.picture_url;
                        }
                        
                        // Update dashboard profile if on same tab
                        const dashboardImg = document.getElementById('dashboardProfilePic');
                        if (dashboardImg) {
                            dashboardImg.src = data.picture_url;
                        }
                        
                        // Store in localStorage to sync across pages
                        localStorage.setItem('profile_picture_updated', data.picture_url);
                        
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success';
                        alertDiv.style.position = 'fixed';
                        alertDiv.style.top = '20px';
                        alertDiv.style.right = '20px';
                        alertDiv.style.zIndex = '9999';
                        alertDiv.textContent = '✅ Profile picture updated successfully!';
                        document.body.appendChild(alertDiv);
                        
                        setTimeout(() => {
                            alertDiv.style.transition = 'opacity 0.5s';
                            alertDiv.style.opacity = '0';
                            setTimeout(() => alertDiv.remove(), 500);
                        }, 3000);
                    } else {
                        alert('❌ Error: ' + data.message);
                        // Reset preview on error
                        document.getElementById('profilePreview').src = '<?php echo getProfilePicture($student['profile_picture'], $student['full_name']); ?>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Failed to upload profile picture');
                    // Reset preview on error
                    document.getElementById('profilePreview').src = '<?php echo getProfilePicture($student['profile_picture'], $student['full_name']); ?>';
                });
            }
        }
        
        // Listen for profile updates from other tabs
        window.addEventListener('storage', function(e) {
            if (e.key === 'profile_picture_updated') {
                const newPicUrl = e.newValue;
                if (newPicUrl) {
                    document.getElementById('profilePreview').src = newPicUrl;
                    const navbarImg = document.querySelector('.profile-button img');
                    if (navbarImg) {
                        navbarImg.src = newPicUrl;
                    }
                }
            }
        });
    </script>
</body>
</html>