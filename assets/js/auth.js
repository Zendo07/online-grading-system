// Authentication JavaScript

// Toggle password visibility
function togglePassword(inputId = 'password') {
    const passwordInput = document.getElementById(inputId);
    const toggleBtn = passwordInput.nextElementSibling;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = '👁️';
    }
}

// Registration form - Show/Hide invitation code field based on role
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const invitationCodeGroup = document.getElementById('invitationCodeGroup');
        const invitationCodeInput = document.getElementById('invitationCode');
        
        // Listen for role change
        roleInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'teacher') {
                    invitationCodeGroup.classList.add('active');
                    invitationCodeInput.setAttribute('required', 'required');
                } else {
                    invitationCodeGroup.classList.remove('active');
                    invitationCodeInput.removeAttribute('required');
                    invitationCodeInput.value = '';
                }
            });
        });
        
        // Validate password match on submit
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        });
    }
    
    // Login form - Add loading state
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        });
    }
});

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});