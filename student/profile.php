<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$user_id = $_SESSION['user_id'];

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirectWithMessage(BASE_URL . 'student/dashboard.php', 'danger', 'User not found.');
    }
} catch (PDOException $e) {
    error_log("Profile Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'student/dashboard.php', 'danger', 'An error occurred.');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - indEx</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Profile Page Styles - Inline to avoid 404 */
        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .profile-picture-card {
            position: sticky;
            top: calc(var(--navbar-height) + 24px);
            height: fit-content;
        }

        .profile-picture-wrapper {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 16px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #7b2d26;
            box-shadow: 0 8px 24px rgba(123, 45, 38, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-picture-wrapper:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 32px rgba(123, 45, 38, 0.3);
        }

        .profile-picture-large {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-picture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-picture-wrapper:hover .profile-picture-overlay {
            opacity: 1;
        }

        .profile-picture-overlay i {
            font-size: 2.5rem;
            color: white;
        }

        .profile-form-card {
            height: fit-content;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #202124;
            font-size: 0.875rem;
        }

        .form-label i {
            margin-right: 8px;
            color: #7b2d26;
            width: 16px;
            text-align: center;
        }

        .form-control,
        .form-control-file {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #7b2d26;
            box-shadow: 0 0 0 3px rgba(123, 45, 38, 0.1);
        }

        .form-control:disabled {
            background: #f8f9fa;
            cursor: not-allowed;
        }

        .form-control-file {
            padding: 10px;
            border-style: dashed;
            cursor: pointer;
        }

        .form-control-file:hover {
            border-color: #7b2d26;
            background: rgba(123, 45, 38, 0.02);
        }

        .form-text {
            display: block;
            margin-top: 6px;
            font-size: 0.8125rem;
            color: #5f6368;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px solid #e0e0e0;
        }

        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .profile-picture-card {
                position: relative;
                top: 0;
            }
        }

        @media (max-width: 768px) {
            .profile-picture-wrapper {
                width: 150px;
                height: 150px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-content">
                <div style="margin-bottom: 24px;">
                    <h1 style="font-size: 2rem; margin: 0 0 8px 0; color: #202124;">My Profile</h1>
                    <p style="color: #5f6368; margin: 0;">Manage your personal information</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 24px;">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-container">
                    <!-- Profile Picture Section -->
                    <div class="card profile-picture-card">
                        <div class="card-body">
                            <div class="profile-picture-wrapper" onclick="document.getElementById('profile_picture').click()">
                                <img src="<?php echo getProfilePicture($user['profile_picture'] ?? '', $user['full_name']); ?>" 
                                     alt="Profile Picture" 
                                     class="profile-picture-large"
                                     id="profilePicturePreview">
                                <div class="profile-picture-overlay">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </div>
                            <h3 style="text-align: center; margin: 16px 0 8px 0; color: #202124;">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </h3>
                            <p style="text-align: center; color: #5f6368; margin: 0;">
                                <span class="badge badge-info">Student</span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Profile Form Section -->
                    <div class="card profile-form-card">
                        <div class="card-header">
                            <h2 class="card-title">Update Profile Information</h2>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>api/student/update-profile.php" 
                                  method="POST" 
                                  enctype="multipart/form-data"
                                  id="profileForm">
                                
                                <div class="form-group">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-user"></i> Full Name
                                    </label>
                                    <input type="text" 
                                           id="full_name" 
                                           name="full_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                           required
                                           minlength="3"
                                           maxlength="100">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" 
                                           id="email" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           disabled>
                                    <small class="form-text">Email cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="profile_picture" class="form-label">
                                        <i class="fas fa-image"></i> Profile Picture
                                    </label>
                                    <input type="file" 
                                           id="profile_picture" 
                                           name="profile_picture" 
                                           class="form-control-file" 
                                           accept="image/jpeg,image/jpg,image/png,image/gif"
                                           style="display: none;">
                                    <div onclick="document.getElementById('profile_picture').click()" 
                                         style="padding: 16px; border: 2px dashed #e0e0e0; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s ease;"
                                         onmouseover="this.style.borderColor='#7b2d26'; this.style.background='rgba(123,45,38,0.02)'"
                                         onmouseout="this.style.borderColor='#e0e0e0'; this.style.background='transparent'">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #7b2d26; margin-bottom: 8px;"></i>
                                        <p style="margin: 0; color: #202124; font-weight: 600;">Click to upload profile picture</p>
                                        <small class="form-text">JPG, PNG, GIF (Max 5MB)</small>
                                    </div>
                                    <div id="selectedFileName" style="margin-top: 8px; color: #10b981; font-size: 0.875rem; display: none;">
                                        <i class="fas fa-check-circle"></i> <span></span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar"></i> Member Since
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           value="<?php echo formatDate($user['created_at']); ?>" 
                                           disabled>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        // Profile picture preview - Inline JS to avoid 404
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or GIF)');
                    this.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Show selected file name
                const fileNameDisplay = document.getElementById('selectedFileName');
                fileNameDisplay.style.display = 'block';
                fileNameDisplay.querySelector('span').textContent = file.name;
                
                // Preview the image
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('profilePicturePreview');
                    preview.style.opacity = '0';
                    
                    setTimeout(function() {
                        preview.src = event.target.result;
                        preview.style.transition = 'opacity 0.5s ease';
                        preview.style.opacity = '1';
                    }, 100);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Form submission
        document.getElementById('profileForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('.btn-primary');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        });
        
        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>