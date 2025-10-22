<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Get flash message if any
$flash = getFlashMessage();

// Get role from URL if provided
$preselected_role = isset($_GET['role']) ? $_GET['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>auth.css?v=<?php echo time(); ?>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <!-- Left Panel -->
        <div class="auth-left">
            <div class="auth-logo-section">
                <img src="<?php echo IMG_PATH; ?>psu-logo.png" alt="PSU Logo" class="auth-logo-img" onerror="this.style.display='none'">
                <h1 class="auth-title-large">Let's explore a new tomorrow!</h1>
                <p class="auth-subtitle-large">
                    Join thousands of students and professors in creating a better learning experience through digital innovation.
                </p>
            </div>
            
            <div class="auth-tagline" style="margin-top: 3rem; text-align: center; max-width: 500px;">
                <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--white-color);">🎓 Why Join Us?</h3>
                <ul style="text-align: left; list-style: none; padding: 0; line-height: 2;">
                    <li>✅ Track your academic progress</li>
                    <li>✅ Access grades instantly</li>
                    <li>✅ Monitor attendance records</li>
                    <li>✅ Stay organized and informed</li>
                </ul>
            </div>
        </div>
        
        <!-- Right Panel -->
        <div class="auth-right">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h2 class="auth-title">Create Account</h2>
                    <p class="auth-subtitle">Join our grading system</p>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>api/auth/register-handler.php" method="POST" class="auth-form" id="registerForm">
                    
                    <!-- Role Selection -->
                    <div class="form-group">
                        <label class="form-label">Select Your Role</label>
                        <div class="role-selector">
                            <div class="role-option">
                                <input 
                                    type="radio" 
                                    id="roleTeacher" 
                                    name="role" 
                                    value="teacher" 
                                    <?php echo ($preselected_role == 'teacher') ? 'checked' : ''; ?>
                                    required
                                >
                                <label for="roleTeacher" class="role-label">
                                    <span class="role-icon">👨‍🏫</span>
                                    <span class="role-name">Teacher</span>
                                </label>
                            </div>
                            <div class="role-option">
                                <input 
                                    type="radio" 
                                    id="roleStudent" 
                                    name="role" 
                                    value="student" 
                                    <?php echo ($preselected_role == 'student') ? 'checked' : ''; ?>
                                    required
                                >
                                <label for="roleStudent" class="role-label">
                                    <span class="role-icon">👨‍🎓</span>
                                    <span class="role-name">Student</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Invitation Code (Hidden by default) -->
                    <div class="form-group" id="invitationCodeGroup" style="display: none;">
                        <label for="invitationCode" class="form-label">Teacher Invitation Code</label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔑</span>
                            <input 
                                type="text" 
                                id="invitationCode" 
                                name="teacher_code" 
                                class="form-control" 
                                placeholder="Enter invitation code (e.g., PSU2025)"
                            >
                        </div>
                        <small class="form-text">Contact admin to get your teacher invitation code</small>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="fullName" class="form-label">Full Name</label>
                        <div class="input-with-icon">
                            <span class="input-icon">👤</span>
                            <input 
                                type="text" 
                                id="fullName" 
                                name="full_name" 
                                class="form-control" 
                                placeholder="Enter your full name"
                                required
                                minlength="3"
                            >
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-with-icon">
                            <span class="input-icon">📧</span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="Enter your email"
                                required
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Create a password"
                                required
                                minlength="6"
                            >
                        </div>
                        <small class="form-text">Minimum 6 characters</small>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input 
                                type="password" 
                                id="confirmPassword" 
                                name="confirm_password" 
                                class="form-control" 
                                placeholder="Confirm your password"
                                required
                                minlength="6"
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-auth btn-auth-primary" style="background: linear-gradient(135deg, #8B4049 0%, #6B3039 100%); color: white; box-shadow: 0 4px 12px rgba(139, 64, 73, 0.3);">
                        Create Account
                    </button>
                </form>

                <div class="auth-footer" style="margin-top: 2rem;">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle role selection and show/hide invitation code
        const roleTeacher = document.getElementById('roleTeacher');
        const roleStudent = document.getElementById('roleStudent');
        const invitationCodeGroup = document.getElementById('invitationCodeGroup');
        const invitationCodeInput = document.getElementById('invitationCode');

        function toggleInvitationCode() {
            if (roleTeacher.checked) {
                invitationCodeGroup.style.display = 'block';
                invitationCodeInput.setAttribute('required', 'required');
            } else {
                invitationCodeGroup.style.display = 'none';
                invitationCodeInput.removeAttribute('required');
                invitationCodeInput.value = '';
            }
        }

        // Add event listeners
        roleTeacher.addEventListener('change', toggleInvitationCode);
        roleStudent.addEventListener('change', toggleInvitationCode);

        // Check on page load
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const role = urlParams.get('role');
            
            if (role === 'teacher') {
                roleTeacher.checked = true;
            } else if (role === 'student') {
                roleStudent.checked = true;
            }
            
            toggleInvitationCode();
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (roleTeacher.checked && !invitationCodeInput.value.trim()) {
                e.preventDefault();
                alert('Teacher invitation code is required!');
                return false;
            }
        });
    </script>
</body>
</html>