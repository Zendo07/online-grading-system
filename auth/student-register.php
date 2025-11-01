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
<title>Student Registration | PSU</title>

<link rel="stylesheet" href="../assets/css/teacher-register.css">
<link rel="stylesheet" href="../assets/css/validation.css">

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
  rel="stylesheet"
/>
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
      <h1>Student Registration</h1>
      <p>Create your account to get started.</p>

      <div id="toast" style="display: none; position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;"></div>

      <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 1rem; padding: 0.75rem; border-radius: 8px; border: 1px solid;">
          <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="../api/auth/register-handler.php" id="registerForm" novalidate>

        <input type="hidden" name="role" value="student">

        <!-- Name Fields -->
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
            <div class="validation-message" id="first_name_error"></div>
          </div>

          <div class="input-group">
            <label for="middle_name">Middle Name</label>
            <input 
              type="text" 
              id="middle_name" 
              name="middle_name" 
              placeholder="Middle Name (Optional)"
            />
            <div class="validation-message" id="middle_name_error"></div>
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
            <div class="validation-message" id="last_name_error"></div>
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
          <div class="validation-message" id="email_error"></div>
        </div>

        <!-- Program & Student Number Row -->
        <div class="two-column-row">
          <div class="input-group">
            <label for="program">🎓 Course Program</label>
            <select 
              id="program" 
              name="program" 
              required
            >
              <option value="">Select your program</option>
              <option value="BSIT">BSIT - Bachelor of Science in Information Technology</option>
              <option value="BSCS">BSCS - Bachelor of Science in Computer Science</option>
              <option value="ACT">ACT - Associate in Computer Technology</option>
              <option value="BSIS">BSIS - Bachelor of Science in Information Systems</option>
            </select>
            <div class="validation-message" id="program_error"></div>
          </div>

          <div class="input-group">
            <label for="student_number">🎓 Student Number</label>
            <input 
              type="text" 
              id="student_number" 
              name="student_number" 
              placeholder="e.g., 2024123456"
              maxlength="10"
              required
            />
            <div class="char-counter" id="student_number_counter">0/10 digits</div>
            <div class="validation-message" id="student_number_error"></div>
          </div>
        </div>

        <!-- Year & Section and Contact Number Row -->
        <div class="two-column-row">
          <div class="input-group">
            <label for="year_section">🏫 Year & Section</label>
            <input 
              type="text" 
              id="year_section" 
              name="year_section" 
              placeholder="e.g., BSCS 3-A"
              maxlength="20"
              required
            />
            <div class="validation-message" id="year_section_error"></div>
          </div>

          <div class="input-group">
            <label for="contact_number">📞 Contact Number (Optional)</label>
            <input 
              type="text" 
              id="contact_number" 
              name="contact_number" 
              placeholder="09XXXXXXXXX"
              maxlength="11"
            />
            <div class="char-counter" id="contact_number_counter">0/11 digits</div>
            <div class="validation-message" id="contact_number_error"></div>
          </div>
        </div>

        <!-- Password Fields -->
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
          <div class="password-strength" id="password_strength">
            <span></span>
            <span></span>
            <span></span>
          </div>
          <div class="password-strength-text" id="password_strength_text"></div>
          <div class="validation-message" id="password_error"></div>
        </div>

        <div class="input-group">
          <label for="confirm_password">🔒 Confirm Password</label>
          <input 
            type="password" 
            id="confirm_password" 
            name="confirm_password" 
            placeholder="Confirm your Password" 
            required
            minlength="8"
          />
          <div class="validation-message" id="confirm_password_error"></div>
        </div>

        <!-- Submit Buttons -->
        <div class="btn-row">
          <button type="button" class="back-btn" onclick="window.location.href='login.php'">Back</button>
          <button type="submit" class="next-btn" id="submitBtn" disabled>Confirm</button>
        </div>

      </form>
    </div>

    <img src="../assets/images/signUp.png" alt="Sign Up Illustration" class="side-img">
  </div>

  <div class="footer">
    <p>&copy; 2025 Pampanga State University. All rights reserved.</p>
    <a href="#">FAQs</a>
  </div>

  <script src="../assets/js/student-register-validation.js"></script>
</body>
</html>