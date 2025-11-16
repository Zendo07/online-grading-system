// DOM Elements
const codeInputs = document.querySelectorAll('.code-input');
const verifyForm = document.getElementById('verifyForm');
const submitBtn = document.getElementById('submitBtn');
const resendForm = document.getElementById('resendForm');
const resendBtn = document.getElementById('resendBtn');

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    initializeCodeInputs();
    initializeFormSubmission();
    initializeResendButton();
    autoFocusFirstInput();
    autoDismissAlerts();
});

function initializeCodeInputs() {
    if (!codeInputs || codeInputs.length === 0) return;
    
    codeInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            
            // Only allow numbers
            if (!/^\d*$/.test(value)) {
                e.target.value = '';
                return;
            }

            if (value.length === 1 && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
                codeInputs[index + 1].select();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                codeInputs[index - 1].focus();
                codeInputs[index - 1].select();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');
            
            if (pastedData.length === 6) {
                codeInputs.forEach((inp, i) => {
                    inp.value = pastedData[i] || '';
                });
                codeInputs[5].focus();
                
                codeInputs.forEach(inp => {
                    inp.style.borderColor = '#10b981';
                });
                setTimeout(() => {
                    codeInputs.forEach(inp => {
                        inp.style.borderColor = '';
                    });
                }, 1000);
            }
        });

        input.addEventListener('focus', (e) => {
            e.target.select();
        });
    });
}

function initializeFormSubmission() {
    if (!verifyForm) return;
    
    verifyForm.addEventListener('submit', (e) => {
        const allFilled = Array.from(codeInputs).every(input => input.value.length === 1);
        
        if (!allFilled) {
            e.preventDefault();
            showAlert('danger', 'Please enter the complete 6-digit verification code.');
            codeInputs[0].focus();
            return false;
        }

        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Verifying...';
        }

        return true;
    });
}

function initializeResendButton() {
    if (!resendForm) return;
    resendForm.addEventListener('submit', (e) => {
        e.preventDefault();
        resendCode();
    });
}

async function resendCode() {
    if (!resendBtn) return;
    if (resendBtn.disabled) return;
    
    resendBtn.disabled = true;
    const originalHTML = resendBtn.innerHTML;
    
    resendBtn.innerHTML = '<span class="spinner"></span> Sending...';
    
    try {
        const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.indexOf('/auth/'));
        const apiUrl = `${baseUrl}/api/auth/resend-code-handler.php`;
        
        console.log('Sending request to:', apiUrl); 
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Get response text first to debug
        const text = await response.text();
        console.log('Response text:', text); 
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', text);
            throw new Error('Invalid response from server. Please try again.');
        }

        if (result.success) {
            showAlert('success', result.message || 'A new verification code has been sent to your email.');
            
            // Clear code inputs for new code entry
            codeInputs.forEach(input => input.value = '');
            codeInputs[0].focus();
            
            // Start countdown
            startResendCountdown(60);
        } else {
            showAlert('danger', result.message || 'Failed to resend code. Please try again.');
            resendBtn.innerHTML = originalHTML;
            resendBtn.disabled = false;
        }
    } catch (error) {
        console.error('Resend error:', error);
        showAlert('danger', error.message || 'An error occurred. Please try again.');
        resendBtn.innerHTML = originalHTML;
        resendBtn.disabled = false;
    }
}

function startResendCountdown(seconds) {
    let countdown = seconds;
    
    const countdownInterval = setInterval(() => {
        resendBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" fill="currentColor"/>
            </svg>
            <span>Resend in ${countdown}s</span>
        `;
        countdown--;

        if (countdown < 0) {
            clearInterval(countdownInterval);
            resendBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" fill="currentColor"/>
                </svg>
                <span>Resend Code</span>
            `;
            resendBtn.disabled = false;
        }
    }, 1000);
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    
    const iconSvg = type === 'danger' 
        ? '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>'
        : '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>';
    
    alertDiv.innerHTML = `
        <svg class="alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${iconSvg}
        </svg>
        <span>${message}</span>
    `;

    const header = document.querySelector('.verification-header');
    header.parentNode.insertBefore(alertDiv, header.nextSibling);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 5000);
}

function autoFocusFirstInput() {
    if (codeInputs && codeInputs.length > 0) {
        setTimeout(() => {
            codeInputs[0].focus();
            codeInputs[0].select();
        }, 100);
    }
}

function autoDismissAlerts() {
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        }, 5000);
    });
}