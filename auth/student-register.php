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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Student Registration | PSU</title>

<!-- Main Styles -->
<link rel="stylesheet" href="../assets/css/teacher-register.css">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
  rel="stylesheet"
/>
</head>
<body>

  <!-- ===== Header ===== -->
  <div class="header">
    <div class="headerLeft">
      <img src="../assets/images/psu-logo.png" alt="University Logo" class="logo">
    </div>
    <div class="headerCenter">
      <h1>indEx</h1>
    </div>
    <div class="headerRight"></div>
  </div>

  <!-- ===== Registration Form ===== -->
  <div class="signup-container">
    <div class="form-section">
      <h1>Sign Up</h1>
      <p>Enter your student account information below.</p>

      <!-- Success/Error Toast -->
      <div id="toast" style="display: none; position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;"></div>

      <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 1rem; padding: 0.75rem; border-radius: 8px; border: 1px solid;">
          <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <!-- ✅ Correct Form Tag -->
      <form method="POST" action="../api/auth/register-handler.php" id="registerForm">

        <!-- Hidden Role -->
        <input type="hidden" name="role" value="student">

        <!-- Name Row -->
        <div class="name-row">
          <div class="input-group">
            <label for="first_name">👤 First Name</label>
            <input 
              type="text" 
              id="first_name" 
              name="first_name" 
              placeholder="First Name" 
              required
            />
          </div>

          <div class="input-group">
            <label for="last_name">Last Name</label>
            <input 
              type="text" 
              id="last_name" 
              name="last_name" 
              placeholder="Last Name" 
              required
            />
          </div>
        </div>

        <!-- Email -->
        <div class="input-group">
          <label for="email">📧 Email</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="Email" 
            required
          />
        </div>

        <!-- Student Number (Optional) -->
        <div class="input-group">
          <label for="student_number">🎓 Student Number (Optional)</label>
          <input 
            type="text" 
            id="student_number" 
            name="student_number" 
            placeholder="e.g., 2024-12345"
          />
        </div>

        <!-- Contact Number (Optional) -->
        <div class="input-group">
          <label for="contact_number">📞 Contact Number (Optional)</label>
          <input 
            type="text" 
            id="contact_number" 
            name="contact_number" 
            placeholder="09XXXXXXXXX"
            maxlength="11"
          />
        </div>

        <!-- Password -->
        <div class="input-group">
          <label for="password">🔒 Password</label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            placeholder="Password (min. 8 characters)" 
            required
            minlength="8"
          />
        </div>

        <!-- Confirm Password -->
        <div class="input-group">
          <label for="confirm_password">🔑 Confirm Password</label>
          <input 
            type="password" 
            id="confirm_password" 
            name="confirm_password" 
            placeholder="Confirm your Password" 
            required
            minlength="8"
          />
        </div>

        <!-- Buttons -->
        <div class="btn-row">
          <button type="button" class="back-btn" onclick="window.location.href='login.php'">Back</button>
          <button type="submit" class="next-btn" id="submitBtn">Confirm</button>
        </div>

      </form>
    </div>

    <!-- Side Illustration -->
    <img src="../assets/images/signUp.png" alt="Sign Up Illustration" class="side-img">
  </div>

  <!-- ===== Footer ===== -->
  <div class="footer">
    <p>&copy; 2025 Pampanga State University. All rights reserved.</p>
    <a href="#">FAQs</a>
  </div>

  <style>
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    .alert {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.4) !important;
    }
    .alert-danger {
      background: rgba(239, 68, 68, 0.2);
      border-color: rgba(239, 68, 68, 0.5) !important;
      color: #dc2626;
    }
  </style>

  <script>
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');

    // Phone number auto-format
    document.getElementById('contact_number').addEventListener('input', function(e) {
      this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
    });

    // Form validation
    form.addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (password !== confirmPassword) {
        e.preventDefault();
        showToast('Passwords do not match!', 'error');
        return false;
      }

      if (password.length < 8) {
        e.preventDefault();
        showToast('Password must be at least 8 characters!', 'error');
        return false;
      }

      // Show loading state
      submitBtn.textContent = 'Creating Account...';
      submitBtn.disabled = true;
    });

    function showToast(message, type) {
      const toast = document.getElementById('toast');
      toast.textContent = message;
      toast.style.display = 'block';
      
      if (type === 'success') {
        toast.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
      } else {
        toast.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
      }

      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
          toast.style.display = 'none';
          toast.style.opacity = '1';
        }, 300);
      }, 3000);
    }

    // Check for success message in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
      showToast('Account created successfully! Redirecting...', 'success');
      setTimeout(() => {
        window.location.href = '../student/dashboard.php';
      }, 1500);
    }
  </script>
</body>
</html>