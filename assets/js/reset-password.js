/**
 * ========================================
 * RESET PASSWORD - JAVASCRIPT (FIXED)
 * ========================================
 */

const codeInputs = document.querySelectorAll('.code-input');
const verifyBtn = document.getElementById('verifyBtn');
const resendBtn = document.getElementById('resendBtn');
const verifyForm = document.getElementById('verifyCodeForm');
const resetForm = document.getElementById('resetForm');
const codeSection = document.getElementById('codeSection');
const passwordSection = document.getElementById('passwordSection');
const newPasswordInput = document.getElementById('new_password');
const confirmPasswordInput = document.getElementById('confirm_password');
const strengthIndicator = document.getElementById('strengthIndicator');
const matchIndicator = document.getElementById('matchIndicator');
const submitBtn = document.getElementById('submitBtn');

document.addEventListener('DOMContentLoaded', () => {
    setupCodeInputs();
    setupPasswordValidation();
    autoDismissAlerts();
});

// =====================================
// Code Input Handling
// =====================================
function setupCodeInputs() {
    codeInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            
            if (!/^\d*$/.test(value)) {
                e.target.value = '';
                return;
            }

            if (value.length === 1 && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                codeInputs[index - 1].focus();
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
            }
        });
    });

    if (codeInputs.length > 0) {
        codeInputs[0].focus();
    }
}

// =====================================
// Verify Code Form
// =====================================
if (verifyForm) {
    verifyForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const code = Array.from(codeInputs).map(input => input.value).join('');

        if (code.length !== 6) {
            showAlert('danger', 'Please enter the complete 6-digit code.');
            return;
        }

        verifyBtn.classList.add('loading');
        verifyBtn.disabled = true;

        try {
            const response = await fetch('../api/auth/verify-reset-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ code: code })
            });

            const result = await response.json();

            if (result.success) {
                codeInputs.forEach((input, index) => {
                    document.getElementById(`hidden_digit${index + 1}`).value = input.value;
                });

                codeSection.style.opacity = '0';
                codeSection.style.transition = 'opacity 0.3s ease';
                
                setTimeout(() => {
                    codeSection.classList.add('hidden');
                    passwordSection.classList.remove('hidden');
                    passwordSection.style.opacity = '0';
                    
                    setTimeout(() => {
                        passwordSection.style.opacity = '1';
                        passwordSection.style.transition = 'opacity 0.3s ease';
                        if (newPasswordInput) {
                            newPasswordInput.focus();
                        }
                    }, 10);
                }, 300);

                showAlert('success', 'Code verified! Now create your new password.');
            } else {
                showAlert('danger', result.message || 'Invalid verification code.');
                verifyBtn.classList.remove('loading');
                verifyBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('danger', 'Connection error. Please check your internet and try again.');
            verifyBtn.classList.remove('loading');
            verifyBtn.disabled = false;
        }
    });
}

// =====================================
// Resend Code
// =====================================
let resendCooldown = false;

if (resendBtn) {
    resendBtn.addEventListener('click', async function() {
        if (resendCooldown) return;

        resendCooldown = true;
        resendBtn.disabled = true;
        
        const originalHTML = resendBtn.innerHTML;
        let countdown = 60;
        
        const countdownInterval = setInterval(() => {
            resendBtn.innerHTML = `
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                </svg>
                Resend in ${countdown}s
            `;
            countdown--;

            if (countdown < 0) {
                clearInterval(countdownInterval);
                resendBtn.innerHTML = originalHTML;
                resendBtn.disabled = false;
                resendCooldown = false;
            }
        }, 1000);

        try {
            const response = await fetch('../api/auth/resend-reset-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'A new verification code has been sent to your email.');
            } else {
                showAlert('danger', result.message || 'Failed to resend code.');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('danger', 'Connection error. Please try again.');
        }
    });
}

// =====================================
// Password Validation
// =====================================
function setupPasswordValidation() {
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', () => {
            checkPasswordStrength();
            checkPasswordMatch();
            updateRequirements();
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', () => {
            checkPasswordMatch();
            updateRequirements();
        });
    }
}

function checkPasswordStrength() {
    const password = newPasswordInput.value;
    const strength = calculatePasswordStrength(password);

    strengthIndicator.className = 'password-strength';
    
    if (password.length === 0) {
        strengthIndicator.className = 'password-strength';
    } else if (strength < 3) {
        strengthIndicator.className = 'password-strength weak';
    } else if (strength < 4) {
        strengthIndicator.className = 'password-strength medium';
    } else {
        strengthIndicator.className = 'password-strength strong';
    }
}

function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    return strength;
}

function checkPasswordMatch() {
    const password = newPasswordInput.value;
    const confirm = confirmPasswordInput.value;

    if (confirm.length === 0) {
        matchIndicator.textContent = '';
        matchIndicator.className = 'password-match';
        return;
    }

    if (password === confirm) {
        matchIndicator.textContent = '✓ Passwords match';
        matchIndicator.className = 'password-match match';
    } else {
        matchIndicator.textContent = '✗ Passwords do not match';
        matchIndicator.className = 'password-match mismatch';
    }
}

function updateRequirements() {
    const password = newPasswordInput.value;
    const confirm = confirmPasswordInput.value;

    const lengthReq = document.getElementById('req-length');
    if (lengthReq) {
        if (password.length >= 8) {
            lengthReq.classList.add('valid');
            lengthReq.innerHTML = `
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="currentColor"/>
                    <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
                At least 8 characters
            `;
        } else {
            lengthReq.classList.remove('valid');
            lengthReq.innerHTML = `
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                </svg>
                At least 8 characters
            `;
        }
    }

    const matchReq = document.getElementById('req-match');
    if (matchReq) {
        if (password === confirm && password.length > 0) {
            matchReq.classList.add('valid');
            matchReq.innerHTML = `
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="currentColor"/>
                    <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
                Passwords match
            `;
        } else {
            matchReq.classList.remove('valid');
            matchReq.innerHTML = `
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                </svg>
                Passwords match
            `;
        }
    }
}

// =====================================
// Toggle Password
// =====================================
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.input-icon-right');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = `
            <svg class="eye-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.804 11.804 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
            </svg>
        `;
    } else {
        input.type = 'password';
        button.innerHTML = `
            <svg class="eye-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
            </svg>
        `;
    }
}

// =====================================
// Reset Form
// =====================================
if (resetForm) {
    resetForm.addEventListener('submit', (e) => {
        const password = newPasswordInput.value;
        const confirm = confirmPasswordInput.value;

        if (password.length < 8) {
            e.preventDefault();
            showAlert('danger', 'Password must be at least 8 characters long.');
            return;
        }

        if (password !== confirm) {
            e.preventDefault();
            showAlert('danger', 'Passwords do not match.');
            return;
        }

        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });
}

// =====================================
// Alert Helper
// =====================================
function showAlert(type, message) {
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    
    const iconSvg = type === 'danger' 
        ? '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>'
        : '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>';
    
    alertDiv.innerHTML = `
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            ${iconSvg}
        </svg>
        <span>${message}</span>
    `;

    const header = document.querySelector('.reset-header');
    header.parentNode.insertBefore(alertDiv, header.nextSibling);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 5000);
}

function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert');
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