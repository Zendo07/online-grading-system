<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
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
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>My Profile</h1>
                    <p class="breadcrumb">Home / Profile</p>
                </div>
            </header>
            
            <div class="dashboard-content">
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
                                    src="<?php echo getProfilePicture($teacher['profile_picture'], $teacher['full_name']); ?>" 
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
                        
                        <form action="<?php echo BASE_URL; ?>api/teacher/update-profile.php" method="POST">
                            <div class="form-group">
                                <label for="fullName" class="form-label">Full Name</label>
                                <input 
                                    type="text" 
                                    id="fullName" 
                                    name="full_name" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($teacher['full_name']); ?>"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($teacher['email']); ?>"
                                    disabled
                                >
                                <small class="form-text">Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="Teacher"
                                    disabled
                                >
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Account Created</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="<?php echo formatDateTime($teacher['created_at']); ?>"
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
    <script>
 function uploadProfilePicture(input) {
    if (input.files && input.files[0]) {
        const formData = new FormData();
        formData.append('profile_picture', input.files[0]);
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        
        // Upload via AJAX
        fetch('<?php echo BASE_URL; ?>api/teacher/upload-profile-picture.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Profile picture updated successfully!');
                
                
                const profileButton = document.querySelector('.profile-button img');
                if (profileButton && data.picture_url) {

                    profileButton.src = data.picture_url + '?t=' + new Date().getTime();
                }
                
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                alert('❌ Error: ' + data.message);
                document.getElementById('profilePreview').src = '<?php echo getProfilePicture($teacher['profile_picture'], $teacher['full_name']); ?>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Failed to upload profile picture');
            document.getElementById('profilePreview').src = '<?php echo getProfilePicture($teacher['profile_picture'], $teacher['full_name']); ?>';
        });
    }
}
    </script>
</body>
</html>