<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Teacher Registration | PSU</title>
<link rel="stylesheet" href="../assets/css/teacher-register.css">
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>
<body>

  <div class="header">
    <div class="headerLeft">
      <img src="../assets/images/psu-logo.png" alt="University Logo" class="logo">
    </div>
    <div class="headerCenter">
      <h1>indEx</h1>
    </div>
    <div class="headerRight"></div>
  </div>

  <div class="signup-container">
    <div class="form-section">
      <h1>Sign Up</h1>
      <p>Enter your teacher account information below.</p>

      <div id="toast" style="display: none; position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;"></div>

      <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 1rem; padding: 0.75rem; border-radius: 8px; border: 1px solid;">
          <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="../api/auth/register-handler.php" id="registerForm">
        <input type="hidden" name="role" value="teacher">

        <div class="input-group">
          <label for="invitation_code">🎟️ Teacher Invitation Code</label>
          <input type="text" id="invitation_code" name="invitation_code" placeholder="Enter invitation code (e.g., TEACH2025)" required />
          <small>Contact the admin to get your teacher code.</small>
        </div>

        <div class="name-row">
          <div class="input-group">
            <label for="first_name">👤 First Name</label>
            <input type="text" id="first_name" name="first_name" placeholder="First Name" required />
          </div>

          <div class="input-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" placeholder="Last Name" required />
          </div>
        </div>

        <div class="input-group">
          <label for="email">📧 Email</label>
          <input type="email" id="email" name="email" placeholder="Email" required />
        </div>

        <div class="input-group">
          <label for="contact_number">📞 Contact Number (Optional)</label>
          <input type="text" id="contact_number" name="contact_number" placeholder="09XXXXXXXXX" maxlength="11" />
        </div>

        <div class="input-group">
          <label for="password">🔒 Password</label>
          <input type="password" id="password" name="password" placeholder="Password (min. 8 characters)" required minlength="8" />
        </div>

        <div class="input-group">
          <label for="confirm_password">🔑 Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your Password" required minlength="8" />
        </div>

        <div class="btn-row">
          <button type="button" class="back-btn" onclick="window.location.href='login.php'">Back</button>
          <button type="submit" class="next-btn" id="submitBtn">Confirm</button>
        </div>
      </form>
    </div>

    <img src="../assets/images/signUp.png" alt="Sign Up Illustration" class="side-img">
  </div>

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

    document.getElementById('contact_number').addEventListener('input', function(e) {
      this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
    });

    form.addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const invitationCode = document.getElementById('invitation_code').value;

      if (!invitationCode.trim()) {
        e.preventDefault();
        showToast('Teacher invitation code is required!', 'error');
        return false;
      }

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
  </script>
</body>
</html>