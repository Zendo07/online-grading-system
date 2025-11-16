document.addEventListener('DOMContentLoaded', function() {
    initializeForgotPassword();
    autoDismissAlerts();
});

function initializeForgotPassword() {
    const form = document.getElementById('forgotForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');

    if (emailInput) {
        emailInput.focus();
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            // Validate email before submission
            if (!validateEmailFormat(emailInput.value)) {
                e.preventDefault();
                showErrorMessage('Please enter a valid email address');
                emailInput.focus();
                return false;
            }
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Sending Code...</span>';
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            validateEmailInput(this);
        });

        emailInput.addEventListener('blur', function() {
            validateEmailInput(this);
        });
    }
}

function validateEmailFormat(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validateEmailInput(input) {
    if (input.value.length === 0) {
        input.style.borderColor = '#f0e6df'; 
        return;
    }

    if (validateEmailFormat(input.value)) {
        input.style.borderColor = '#10b981'; 
    } else {
        input.style.borderColor = '#ef4444'; 
    }
}

function showErrorMessage(message) {
    const existingErrors = document.querySelectorAll('.error-message');
    existingErrors.forEach(error => error.remove());

    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger error-message';
    errorDiv.style.animation = 'slideDown 0.4s ease-out';
    
    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('viewBox', '0 0 24 24');
    icon.innerHTML = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
    
    const span = document.createElement('span');
    span.textContent = message;
    
    errorDiv.appendChild(icon);
    errorDiv.appendChild(span);

    const form = document.getElementById('forgotForm');
    const container = document.querySelector('.forgot-container');
    const header = document.querySelector('.forgot-header');
    
    if (container && header) {
        header.parentNode.insertBefore(errorDiv, header.nextSibling);
    } else {
        form.parentNode.insertBefore(errorDiv, form);
    }

    setTimeout(() => {
        errorDiv.style.opacity = '0';
        errorDiv.style.transition = 'opacity 0.3s ease';
        setTimeout(() => errorDiv.remove(), 300);
    }, 5000);
}

function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.error-message)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
}