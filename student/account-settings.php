<?php

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$student_info = null;

try {
    $stmt = $conn->prepare("
        SELECT 
            user_id,
            email,
            full_name,
            middle_name,
            student_number,
            program,
            year_section,
            contact_number,
            profile_picture,
            created_at
        FROM users 
        WHERE user_id = ? AND role = 'student'
    ");
    $stmt->execute([$student_id]);
    $student_info = $stmt->fetch();
    
    if (!$student_info) {
        redirectWithMessage(BASE_URL . 'student/dashboard.php', 'danger', 'Student information not found.');
    }
    
    $name_parts = explode(' ', $student_info['full_name']);
    $first_name = $name_parts[0] ?? '';
    $last_name = end($name_parts);
    
    // Remove first and last name from parts to get middle name
    if (count($name_parts) > 2) {
        array_shift($name_parts);
        array_pop($name_parts);
        $middle_name_from_full = implode(' ', $name_parts);
    } else {
        $middle_name_from_full = '';
    }
    
    // Use middle_name field if available, otherwise use extracted from full_name
    $middle_name = !empty($student_info['middle_name']) ? $student_info['middle_name'] : $middle_name_from_full;
    
    // Get profile picture
    $profile_pic_url = getProfilePicture($student_info['profile_picture'] ?? null, $student_info['full_name']);
    
} catch (PDOException $e) {
    error_log("Account Settings Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'student/dashboard.php', 'danger', 'Error loading settings.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - indEx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/account-settings.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="settings-container">
                <div class="settings-header">
                    <h1 class="settings-title">Account Settings</h1>
                    <p class="settings-subtitle">Manage your profile information and security settings</p>
                </div>

                <div class="settings-grid">
                    
                    <div class="left-column">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="settings-card-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <h2 class="settings-card-title">Change Profile</h2>
                            </div>

                            <div class="profile-picture-section-center">
                                <div class="profile-picture-preview" id="profilePicturePreview">
                                    <img src="<?php echo $profile_pic_url; ?>" alt="Profile Picture" id="profilePictureImage">
                                    <div class="profile-picture-overlay" id="uploadTrigger">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                                <div class="profile-picture-actions-center">
                                    <button type="button" class="btn-upload-picture" id="btnUploadPicture">
                                        <i class="fas fa-upload"></i> Upload Photo
                                    </button>
                                    <button type="button" class="btn-remove-picture" id="btnRemovePicture">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                                <input type="file" id="profilePictureInput" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                            </div>
                        </div>

                        <!-- Change Password Card -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="settings-card-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <h2 class="settings-card-title">Security Settings</h2>
                            </div>

                            <button type="button" class="btn-change-password" id="btnOpenPasswordModal">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <h2 class="settings-card-title">Edit Profile</h2>
                        </div>

                        <form id="profileForm">
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label class="form-label required">First Name</label>
                                    <input type="text" class="form-input" id="firstName" name="first_name" 
                                           value="<?php echo htmlspecialchars($first_name); ?>" required>
                                    <div class="form-error" id="firstNameError"></div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-input" id="middleName" name="middle_name" 
                                           value="<?php echo htmlspecialchars($middle_name); ?>">
                                    <div class="form-error" id="middleNameError"></div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Last Name</label>
                                    <input type="text" class="form-input" id="lastName" name="last_name" 
                                           value="<?php echo htmlspecialchars($last_name); ?>" required>
                                    <div class="form-error" id="lastNameError"></div>
                                </div>
                            </div>

                            <!-- Student Number -->
                            <div class="form-group">
                                <label class="form-label">Student Number</label>
                                <input type="text" class="form-input" id="studentNumber" name="student_number" 
                                       value="<?php echo htmlspecialchars($student_info['student_number'] ?? ''); ?>" 
                                       disabled>
                                <div class="form-error" id="studentNumberError"></div>
                            </div>

                            <!-- Course and Year Section -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label required">Course/Program</label>
                                    <input type="text" class="form-input" id="program" name="program" 
                                           value="<?php echo htmlspecialchars($student_info['program'] ?? ''); ?>" required>
                                    <div class="form-error" id="programError"></div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Year & Section</label>
                                    <input type="text" class="form-input" id="yearSection" name="year_section" 
                                           value="<?php echo htmlspecialchars($student_info['year_section'] ?? ''); ?>" 
                                           placeholder="e.g., 3-A" required>
                                    <div class="form-error" id="yearSectionError"></div>
                                </div>
                            </div>

                            <!-- Contact Number -->
                            <div class="form-group">
                                <label class="form-label required">Contact Number</label>
                                <input type="tel" class="form-input" id="contactNumber" name="contact_number" 
                                       value="<?php echo htmlspecialchars($student_info['contact_number'] ?? ''); ?>" 
                                       placeholder="+63 912 345 6789" required>
                                <div class="form-error" id="contactNumberError"></div>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label class="form-label required">Email Address</label>
                                <input type="email" class="form-input" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($student_info['email']); ?>" required>
                                <div class="form-error" id="emailError"></div>
                            </div>

                            <!-- Actions -->
                            <div class="settings-actions">
                                <button type="button" class="btn-cancel" id="btnCancelProfile">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="btn-save" id="btnSaveProfile">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                </div>
            </div>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div class="modal-overlay" id="passwordModal">
        <div class="modal-content">
            <button class="modal-close" id="btnClosePasswordModal">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-header">
                <h3 class="modal-title">Change Password</h3>
                <p class="modal-subtitle">Enter your current password and choose a new one</p>
            </div>

            <div class="modal-body">
                <form id="passwordForm">
                    <div class="form-group">
                        <label class="form-label required">Current Password</label>
                        <input type="password" class="form-input" id="currentPassword" name="current_password" required>
                        <div class="form-error" id="currentPasswordError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">New Password</label>
                        <input type="password" class="form-input" id="newPassword" name="new_password" required>
                        <div class="form-error" id="newPasswordError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Confirm New Password</label>
                        <input type="password" class="form-input" id="confirmPassword" name="confirm_password" required>
                        <div class="form-error" id="confirmPasswordError"></div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" id="btnCancelPassword">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn-save" id="btnSavePassword">
                            <i class="fas fa-check"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo JS_PATH; ?>main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>student-pages/account-settings.js?v=<?php echo time(); ?>"></script>
</body>
</html>