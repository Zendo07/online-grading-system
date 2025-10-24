<?php
// auth/teacher-register.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Teacher Registration | PSU</title>

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
      <p>Enter your teacher account information below.</p>

      <!-- ✅ Correct Form Tag -->
      <form method="POST" action="../api/auth/register-handler.php">

        <!-- Hidden Role -->
        <input type="hidden" name="role" value="teacher">

        <!-- Invitation Code -->
        <div class="input-group">
          <label for="invitation_code">🎟️ Teacher Invitation Code</label>
          <input 
            type="text" 
            id="invitation_code" 
            name="invitation_code" 
            placeholder="Enter invitation code (e.g., PSU2025)" 
            required
          />
          <small>Contact the admin to get your teacher code.</small>
        </div>

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

        <!-- Username -->
        <div class="input-group">
          <label for="username">🧑‍💻 Username</label>
          <input 
            type="text" 
            id="username" 
            name="username" 
            placeholder="Username" 
            required
          />
        </div>

        <!-- Contact Number -->
        <div class="input-group">
          <label for="contact_number">📞 Contact Number</label>
          <input 
            type="text" 
            id="contact_number" 
            name="contact_number" 
            placeholder="09XXXXXXXXX"
          />
        </div>

        <!-- Password -->
        <div class="input-group">
          <label for="password">🔒 Password</label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            placeholder="Password" 
            required
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
          />
        </div>

        <!-- Buttons -->
        <div class="btn-row">
          <button type="button" class="back-btn" onclick="window.location.href='register.php'">Back</button>
          <button type="submit" class="next-btn">Confirm</button>
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

</body>
</html>
