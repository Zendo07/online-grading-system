document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        // Password match validation
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirmPassword').classList.add('error');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.next-btn');
            submitBtn.textContent = 'Creating Account...';
            submitBtn.disabled = true;
        });
        
        // Remove error class on input
        document.getElementById('confirmPassword').addEventListener('input', function() {
            this.classList.remove('error');
        });
    }
    
    // Auto-dismiss alerts
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
    
    // Phone number validation
    const contactNumber = document.getElementById('contactNumber');
    if (contactNumber) {
        contactNumber.addEventListener('input', function() {
            // Remove non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 11 digits (Philippine format)
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
    }
});