(function() {
  'use strict';

  const validationState = {
    first_name: false,
    last_name: false,
    email: false,
    program: false,
    year_section: false,
    student_number: false,
    contact_number: true, 
    password: false,
    confirm_password: false
  };

  const form = document.getElementById('registerForm');
  const submitBtn = document.getElementById('submitBtn');

  function validateName(input, errorElementId, fieldName) {
    const value = input.value.trim();
    const errorElement = document.getElementById(errorElementId);
    const nameRegex = /^[a-zA-Z\s]+$/;

    input.classList.remove('valid', 'invalid');

    if (value === '' && input.hasAttribute('required')) {
      errorElement.textContent = `${fieldName} is required`;
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value !== '' && !nameRegex.test(value)) {
      errorElement.textContent = `${fieldName} must contain letters only`;
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value !== '' && value.length < 2) {
      errorElement.textContent = `${fieldName} must be at least 2 characters`;
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value !== '') {
      errorElement.textContent = `✓ Valid ${fieldName.toLowerCase()}`;
      errorElement.className = 'validation-message success';
      input.classList.add('valid');
      return true;
    }

    errorElement.style.display = 'none';
    return true;
  }

  function validateEmail(input) {
    const value = input.value.trim();
    const errorElement = document.getElementById('email_error');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    input.classList.remove('valid', 'invalid');

    if (value === '') {
      errorElement.textContent = 'Email is required';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (!emailRegex.test(value)) {
      errorElement.textContent = 'Please enter a valid email address';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    errorElement.textContent = '✓ Valid email address';
    errorElement.className = 'validation-message success';
    input.classList.add('valid');
    return true;
  }

  function validateProgram(select) {
    const value = select.value;
    const errorElement = document.getElementById('program_error');

    select.classList.remove('valid', 'invalid');

    if (value === '') {
      errorElement.textContent = 'Please select a program';
      errorElement.className = 'validation-message error';
      select.classList.add('invalid');
      return false;
    }

    errorElement.textContent = '✓ Program selected';
    errorElement.className = 'validation-message success';
    select.classList.add('valid');
    return true;
  }

  function validateYearSection(input) {
    const value = input.value.trim().toUpperCase();
    const errorElement = document.getElementById('year_section_error');
    
    // Format: 1-4 (year) + dash + A-Z (section letter)
    const yearSectionRegex = /^[1-4]-[A-Z]$/;

    input.classList.remove('valid', 'invalid');

    // Auto-format: convert to uppercase
    input.value = value;

    if (value === '') {
      errorElement.textContent = 'Year & Section is required';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (!yearSectionRegex.test(value)) {
      errorElement.textContent = 'Format must be: 1-A, 2-B, 3-C, or 4-D (year 1-4, section A-Z)';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    errorElement.textContent = '✓ Valid year & section format';
    errorElement.className = 'validation-message success';
    input.classList.add('valid');
    return true;
  }

  function validateStudentNumber(input) {
    const value = input.value.trim();
    const errorElement = document.getElementById('student_number_error');
    const counterElement = document.getElementById('student_number_counter');
    const digitRegex = /^\d+$/;

    input.classList.remove('valid', 'invalid');
    counterElement.textContent = `${value.length}/10 digits`;

    if (value !== '' && !digitRegex.test(value)) {
      input.value = value.replace(/\D/g, '');
      errorElement.textContent = 'Student number must contain numbers only';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value === '') {
      errorElement.textContent = 'Student number is required';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value.length < 10) {
      errorElement.textContent = `Must be exactly 10 digits (${10 - value.length} more needed)`;
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value.length === 10) {
      errorElement.textContent = '✓ Valid student number';
      errorElement.className = 'validation-message success';
      input.classList.add('valid');
      return true;
    }

    return false;
  }

  function validateContactNumber(input) {
    const value = input.value.trim();
    const errorElement = document.getElementById('contact_number_error');
    const counterElement = document.getElementById('contact_number_counter');
    const digitRegex = /^\d+$/;
    const philippineRegex = /^09\d{9}$/;

    input.classList.remove('valid', 'invalid');
    counterElement.textContent = `${value.length}/11 digits`;

    if (value !== '' && !digitRegex.test(value)) {
      input.value = value.replace(/\D/g, '');
      errorElement.textContent = 'Contact number must contain numbers only';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value === '') {
      errorElement.style.display = 'none';
      return true;
    }

    if (value.length < 11) {
      errorElement.textContent = `Must be 11 digits (${11 - value.length} more needed)`;
      errorElement.className = 'validation-message warning';
      input.classList.add('invalid');
      return false;
    }

    if (!philippineRegex.test(value)) {
      errorElement.textContent = 'Must start with 09 (Philippine format)';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    errorElement.textContent = '✓ Valid contact number';
    errorElement.className = 'validation-message success';
    input.classList.add('valid');
    return true;
  }

  function validatePasswordStrength(input) {
    const value = input.value;
    const errorElement = document.getElementById('password_error');
    const strengthBar = document.getElementById('password_strength');
    const strengthText = document.getElementById('password_strength_text');

    input.classList.remove('valid', 'invalid');

    if (value === '') {
      errorElement.textContent = 'Password is required';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      strengthBar.className = 'password-strength';
      strengthText.textContent = '';
      return false;
    }

    if (value.length < 8) {
      errorElement.textContent = `Must be at least 8 characters (${8 - value.length} more needed)`;
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      strengthBar.className = 'password-strength';
      strengthText.textContent = '';
      return false;
    }

    let strength = 0;
    if (/[a-z]/.test(value)) strength++;
    if (/[A-Z]/.test(value)) strength++;
    if (/\d/.test(value)) strength++;
    if (/[^a-zA-Z0-9]/.test(value)) strength++;
    if (value.length >= 12) strength++;

    let strengthLevel, strengthClass;
    if (strength <= 2) {
      strengthLevel = 'Weak';
      strengthClass = 'weak';
    } else if (strength === 3) {
      strengthLevel = 'Medium';
      strengthClass = 'medium';
    } else {
      strengthLevel = 'Strong';
      strengthClass = 'strong';
    }

    strengthBar.className = `password-strength ${strengthClass}`;
    strengthText.textContent = `Password strength: ${strengthLevel}`;
    strengthText.className = `password-strength-text ${strengthClass}`;

    if (strengthLevel === 'Weak') {
      errorElement.textContent = 'Tip: Add uppercase, numbers, or symbols';
      errorElement.className = 'validation-message warning';
      input.classList.add('invalid');
      return false;
    }

    errorElement.style.display = 'none';
    input.classList.add('valid');
    return true;
  }

  function validateConfirmPassword(input) {
    const value = input.value;
    const password = document.getElementById('password').value;
    const errorElement = document.getElementById('confirm_password_error');

    input.classList.remove('valid', 'invalid');

    if (value === '') {
      errorElement.textContent = 'Please confirm your password';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    if (value !== password) {
      errorElement.textContent = 'Passwords do not match';
      errorElement.className = 'validation-message error';
      input.classList.add('invalid');
      return false;
    }

    errorElement.textContent = '✓ Passwords match';
    errorElement.className = 'validation-message success';
    input.classList.add('valid');
    return true;
  }

  function updateSubmitButton() {
    const allValid = Object.values(validationState).every(v => v === true);
    submitBtn.disabled = !allValid;
    
    if (allValid) {
      submitBtn.style.opacity = '1';
      submitBtn.style.cursor = 'pointer';
    } else {
      submitBtn.style.opacity = '0.6';
      submitBtn.style.cursor = 'not-allowed';
    }
  }

  function showToast(message, type) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.style.display = 'block';
    
    if (type === 'success') {
      toast.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    } else {
      toast.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
    }

    setTimeout(() => {
      toast.style.transition = 'opacity 0.3s ease';
      toast.style.opacity = '0';
      setTimeout(() => {
        toast.style.display = 'none';
        toast.style.opacity = '1';
        toast.style.transition = '';
      }, 300);
    }, 3000);
  }

  function initializeValidation() {
    document.getElementById('first_name').addEventListener('input', function() {
      validationState.first_name = validateName(this, 'first_name_error', 'First name');
      updateSubmitButton();
    });

    const middleNameInput = document.getElementById('middle_name');
    if (middleNameInput) {
      middleNameInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value !== '') {
          validateName(this, 'middle_name_error', 'Middle name');
        } else {
          document.getElementById('middle_name_error').style.display = 'none';
          this.classList.remove('valid', 'invalid');
        }
      });
    }

    // Last Name
    document.getElementById('last_name').addEventListener('input', function() {
      validationState.last_name = validateName(this, 'last_name_error', 'Last name');
      updateSubmitButton();
    });

    // Email
    document.getElementById('email').addEventListener('input', function() {
      validationState.email = validateEmail(this);
      updateSubmitButton();
    });

    // Program
    document.getElementById('program').addEventListener('change', function() {
      validationState.program = validateProgram(this);
      updateSubmitButton();
    });

    // Year & Section - NEW VALIDATION
    document.getElementById('year_section').addEventListener('input', function() {
      validationState.year_section = validateYearSection(this);
      updateSubmitButton();
    });

    // Student Number
    document.getElementById('student_number').addEventListener('input', function() {
      validationState.student_number = validateStudentNumber(this);
      updateSubmitButton();
    });

    // Contact Number
    document.getElementById('contact_number').addEventListener('input', function() {
      validationState.contact_number = validateContactNumber(this);
      updateSubmitButton();
    });

    // Password
    document.getElementById('password').addEventListener('input', function() {
      validationState.password = validatePasswordStrength(this);
      
      const confirmPassword = document.getElementById('confirm_password');
      if (confirmPassword.value !== '') {
        validationState.confirm_password = validateConfirmPassword(confirmPassword);
      }
      
      updateSubmitButton();
    });

    // Confirm Password
    document.getElementById('confirm_password').addEventListener('input', function() {
      validationState.confirm_password = validateConfirmPassword(this);
      updateSubmitButton();
    });

    // Form Submission
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      // Final validation
      const finalValidation = {
        first_name: validateName(document.getElementById('first_name'), 'first_name_error', 'First name'),
        last_name: validateName(document.getElementById('last_name'), 'last_name_error', 'Last name'),
        email: validateEmail(document.getElementById('email')),
        program: validateProgram(document.getElementById('program')),
        year_section: validateYearSection(document.getElementById('year_section')),
        student_number: validateStudentNumber(document.getElementById('student_number')),
        contact_number: validateContactNumber(document.getElementById('contact_number')),
        password: validatePasswordStrength(document.getElementById('password')),
        confirm_password: validateConfirmPassword(document.getElementById('confirm_password'))
      };

      const allValid = Object.values(finalValidation).every(v => v === true);

      if (!allValid) {
        showToast('Please fix all validation errors before submitting', 'error');
        
        const firstError = document.querySelector('input.invalid, select.invalid');
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstError.focus();
        }
        
        return false;
      }

      submitBtn.textContent = 'Creating Account...';
      submitBtn.disabled = true;
      
      this.submit();
    });
  }

  // Initialize
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initializeValidation();
      updateSubmitButton();
    });
  } else {
    initializeValidation();
    updateSubmitButton();
  }

})();