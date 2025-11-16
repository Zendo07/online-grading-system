(function() {
    'use strict';

    let originalFormData = {};
    let currentProfilePicture = null;

    function init() {
        setupProfileForm();
        setupPasswordModal();
        setupProfilePicture();
        storeOriginalData();
        addNotificationStyles();
        clearOtherUserData(); 
    }

    function clearOtherUserData() {
        const currentUserId = getCurrentUserId();
        const storedUserId = localStorage.getItem('current_user_id');
        
        if (storedUserId && storedUserId !== currentUserId) {
            localStorage.removeItem('profile_picture_updated');
            localStorage.removeItem('profile_picture_timestamp');
            localStorage.removeItem('profile_name_updated');
            localStorage.removeItem('profile_update_timestamp');
        }
        
        localStorage.setItem('current_user_id', currentUserId);
    }

    function getCurrentUserId() {
        const metaUser = document.querySelector('meta[name="user-id"]');
        if (metaUser) {
            return metaUser.content;
        }
        
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user_id');
        if (userId) {
            return userId;
        }
        
        return 'user_' + Date.now();
    }

    function storeOriginalData() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        formData.forEach((value, key) => {
            originalFormData[key] = value;
        });
    }

    function hasFormChanges() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        
        for (let [key, value] of formData.entries()) {
            if (originalFormData[key] !== value) {
                return true;
            }
        }
        
        return currentProfilePicture !== null;
    }

    function setupProfileForm() {
        const form = document.getElementById('profileForm');
        const btnCancel = document.getElementById('btnCancelProfile');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateProfileForm()) {
                showNotification('Validation Error', 'Please fix the errors before saving', 'error');
                return;
            }

            if (!hasFormChanges()) {
                showNotification('No Changes', 'No changes detected to save', 'warning');
                return;
            }

            await saveProfileChanges();
        });

        btnCancel.addEventListener('click', function() {
            if (hasFormChanges()) {
                if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                    resetProfileForm();
                }
            } else {
                window.location.href = window.BASE_URL + 'student/profile.php';
            }
        });
    }

    function validateProfileForm() {
        let isValid = true;

        const firstName = document.getElementById('firstName');
        if (!firstName.value.trim()) {
            showFieldError('firstName', 'First name is required');
            isValid = false;
        } else {
            hideFieldError('firstName');
        }

        const lastName = document.getElementById('lastName');
        if (!lastName.value.trim()) {
            showFieldError('lastName', 'Last name is required');
            isValid = false;
        } else {
            hideFieldError('lastName');
        }

        const program = document.getElementById('program');
        if (!program.value.trim()) {
            showFieldError('program', 'Course/Program is required');
            isValid = false;
        } else {
            hideFieldError('program');
        }

        const yearSection = document.getElementById('yearSection');
        if (!yearSection.value.trim()) {
            showFieldError('yearSection', 'Year & Section is required');
            isValid = false;
        } else {
            hideFieldError('yearSection');
        }

        const contactNumber = document.getElementById('contactNumber');
        if (!contactNumber.value.trim()) {
            showFieldError('contactNumber', 'Contact number is required');
            isValid = false;
        } else if (!isValidPhone(contactNumber.value)) {
            showFieldError('contactNumber', 'Please enter a valid phone number');
            isValid = false;
        } else {
            hideFieldError('contactNumber');
        }

        const email = document.getElementById('email');
        if (!email.value.trim()) {
            showFieldError('email', 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        } else {
            hideFieldError('email');
        }

        return isValid;
    }

    async function saveProfileChanges() {
        const btnSave = document.getElementById('btnSaveProfile');
        const originalHTML = btnSave.innerHTML;
        
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btnSave.disabled = true;

        try {
            const formData = new FormData();
            formData.append('first_name', document.getElementById('firstName').value.trim());
            formData.append('middle_name', document.getElementById('middleName').value.trim());
            formData.append('last_name', document.getElementById('lastName').value.trim());
            formData.append('program', document.getElementById('program').value.trim());
            formData.append('year_section', document.getElementById('yearSection').value.trim());
            formData.append('contact_number', document.getElementById('contactNumber').value.trim());
            formData.append('email', document.getElementById('email').value.trim());

            const response = await fetch(window.BASE_URL + 'api/student/update-profile-settings.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Success!', 'Profile updated successfully', 'success');
                storeOriginalData();
                
                // Update session data everywhere
                updateNavbarInfo(data.full_name);
                
                // Store with user ID for isolation
                const userId = getCurrentUserId();
                localStorage.setItem('profile_name_updated_' + userId, data.full_name);
                localStorage.setItem('profile_update_timestamp_' + userId, Date.now().toString());
                
                localStorage.setItem('profile_name_updated', data.full_name);
                localStorage.setItem('profile_update_timestamp', Date.now().toString());
                
                window.dispatchEvent(new StorageEvent('storage', {
                    key: 'profile_name_updated',
                    newValue: data.full_name,
                    url: window.location.href
                }));
                
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showNotification('Update Failed', data.message || 'Failed to update profile', 'error');
            }
        } catch (error) {
            console.error('Save error:', error);
            showNotification('Error', 'An error occurred while saving', 'error');
        } finally {
            btnSave.innerHTML = originalHTML;
            btnSave.disabled = false;
        }
    }

    function resetProfileForm() {
        const form = document.getElementById('profileForm');
        form.reset();
        
        // Reset to original values
        Object.keys(originalFormData).forEach(key => {
            const input = form.elements[key];
            if (input) {
                input.value = originalFormData[key];
            }
        });

        currentProfilePicture = null;
        clearAllErrors();
    }

    function setupProfilePicture() {
        const btnUpload = document.getElementById('btnUploadPicture');
        const btnRemove = document.getElementById('btnRemovePicture');
        const fileInput = document.getElementById('profilePictureInput');
        const uploadTrigger = document.getElementById('uploadTrigger');

        btnUpload.addEventListener('click', () => fileInput.click());
        uploadTrigger.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];

                if (file.size > 5 * 1024 * 1024) {
                    showNotification('File Too Large', 'File size must be less than 5MB', 'error');
                    return;
                }

                if (!file.type.match('image/(jpeg|jpg|png)')) {
                    showNotification('Invalid File', 'Only JPG, JPEG, and PNG files are allowed', 'error');
                    return;
                }

                uploadProfilePicture(file);
            }
        });

        btnRemove.addEventListener('click', removeProfilePicture);
    }

    async function uploadProfilePicture(file) {
        const formData = new FormData();
        formData.append('profile_picture', file);

        const btnUpload = document.getElementById('btnUploadPicture');
        const originalHTML = btnUpload.innerHTML;
        
        btnUpload.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        btnUpload.disabled = true;

        try {
            const response = await fetch(window.BASE_URL + 'api/student/upload-profile-picture.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const img = document.getElementById('profilePictureImage');
                img.src = data.picture_url + '&cache=' + new Date().getTime();
                
                const navbarImg = document.querySelector('.profile-button img');
                if (navbarImg) {
                    navbarImg.src = data.picture_url + '&cache=' + new Date().getTime();
                }

                const userId = getCurrentUserId();
                localStorage.setItem('profile_picture_updated_' + userId, data.picture_url);
                localStorage.setItem('profile_picture_timestamp_' + userId, Date.now().toString());
                localStorage.setItem('profile_picture_updated', data.picture_url);
                localStorage.setItem('profile_picture_timestamp', Date.now().toString());
                
                window.dispatchEvent(new StorageEvent('storage', {
                    key: 'profile_picture_updated',
                    newValue: data.picture_url,
                    url: window.location.href
                }));

                currentProfilePicture = data.filename;
                showNotification('Success!', 'Profile picture updated successfully', 'success');
            } else {
                showNotification('Upload Failed', data.message || 'Failed to upload picture', 'error');
            }
        } catch (error) {
            console.error('Upload error:', error);
            showNotification('Error', 'An error occurred while uploading', 'error');
        } finally {
            btnUpload.innerHTML = originalHTML;
            btnUpload.disabled = false;
        }
    }

    async function removeProfilePicture() {
        if (!confirm('Are you sure you want to remove your profile picture?')) {
            return;
        }

        showNotification('Info', 'Profile picture removal will be implemented', 'warning');
    }

    function setupPasswordModal() {
        const modal = document.getElementById('passwordModal');
        const btnOpen = document.getElementById('btnOpenPasswordModal');
        const btnClose = document.getElementById('btnClosePasswordModal');
        const btnCancel = document.getElementById('btnCancelPassword');
        const form = document.getElementById('passwordForm');

        btnOpen.addEventListener('click', () => openPasswordModal());
        btnClose.addEventListener('click', () => closePasswordModal());
        btnCancel.addEventListener('click', () => closePasswordModal());

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePasswordModal();
            }
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validatePasswordForm()) {
                return;
            }

            await changePassword();
        });
    }

    function openPasswordModal() {
        const modal = document.getElementById('passwordModal');
        modal.classList.add('active');
        document.getElementById('currentPassword').focus();
    }

    function closePasswordModal() {
        const modal = document.getElementById('passwordModal');
        modal.classList.remove('active');
        resetPasswordForm();
    }

    function validatePasswordForm() {
        let isValid = true;

        const currentPassword = document.getElementById('currentPassword');
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');

        // Current password
        if (!currentPassword.value) {
            showFieldError('currentPassword', 'Current password is required');
            isValid = false;
        } else {
            hideFieldError('currentPassword');
        }

        // New password
        if (!newPassword.value) {
            showFieldError('newPassword', 'New password is required');
            isValid = false;
        } else if (newPassword.value.length < 8) {
            showFieldError('newPassword', 'Password must be at least 8 characters');
            isValid = false;
        } else {
            hideFieldError('newPassword');
        }

        // Confirm password
        if (!confirmPassword.value) {
            showFieldError('confirmPassword', 'Please confirm your new password');
            isValid = false;
        } else if (newPassword.value !== confirmPassword.value) {
            showFieldError('confirmPassword', 'Passwords do not match');
            isValid = false;
        } else {
            hideFieldError('confirmPassword');
        }

        return isValid;
    }

    /**
     * Change password
     */
    async function changePassword() {
        const btnSave = document.getElementById('btnSavePassword');
        const originalHTML = btnSave.innerHTML;
        
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        btnSave.disabled = true;

        try {
            const formData = new FormData();
            formData.append('current_password', document.getElementById('currentPassword').value);
            formData.append('new_password', document.getElementById('newPassword').value);
            formData.append('confirm_password', document.getElementById('confirmPassword').value);

            const response = await fetch(window.BASE_URL + 'api/student/change-password-settings.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Success!', 'Password changed successfully', 'success');
                closePasswordModal();
                
                // Clear all localStorage on logout
                clearAllProfileData();
                
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = window.BASE_URL + 'auth/logout.php';
                }, 2000);
            } else {
                showNotification('Update Failed', data.message || 'Failed to change password', 'error');
            }
        } catch (error) {
            console.error('Password change error:', error);
            showNotification('Error', 'An error occurred while changing password', 'error');
        } finally {
            btnSave.innerHTML = originalHTML;
            btnSave.disabled = false;
        }
    }

    function clearAllProfileData() {
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith('profile_') || key === 'current_user_id') {
                localStorage.removeItem(key);
            }
        });
    }

    function resetPasswordForm() {
        const form = document.getElementById('passwordForm');
        form.reset();
        clearAllErrors();
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');
        
        field.classList.add('error');
        error.textContent = message;
        error.classList.add('show');
    }

    function hideFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');
        
        field.classList.remove('error');
        error.textContent = '';
        error.classList.remove('show');
    }

    function clearAllErrors() {
        document.querySelectorAll('.form-input, .form-select').forEach(field => {
            field.classList.remove('error');
        });
        document.querySelectorAll('.form-error').forEach(error => {
            error.textContent = '';
            error.classList.remove('show');
        });
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidPhone(phone) {
        return /^[\d\s\+\-\(\)]+$/.test(phone) && phone.replace(/\D/g, '').length >= 10;
    }

    function updateNavbarInfo(fullName) {
        // Update sidebar name
        const sidebarName = document.querySelector('.sidebar-user-name');
        if (sidebarName) {
            sidebarName.textContent = fullName;
        }
        
        // Update navbar profile picture (if it was changed)
        const userId = getCurrentUserId();
        const profilePicUrl = localStorage.getItem('profile_picture_updated_' + userId) || 
                             localStorage.getItem('profile_picture_updated');
        if (profilePicUrl) {
            const navbarImg = document.querySelector('.profile-button img');
            if (navbarImg) {
                navbarImg.src = profilePicUrl + '?t=' + new Date().getTime();
            }
        }
    }

    function showNotification(title, message, type) {
        const existingNotifications = document.querySelectorAll('.toast-notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = `toast-notification toast-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle'
        };
        
        notification.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icons[type] || icons.success}"></i>
            </div>
            <div class="toast-content">
                <strong class="toast-title">${title}</strong>
                <p class="toast-message">${message}</p>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    function addNotificationStyles() {
        if (!document.getElementById('toast-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-notification-styles';
            style.textContent = `
                .toast-notification {
                    position: fixed;
                    top: 24px;
                    right: 24px;
                    min-width: 350px;
                    max-width: 420px;
                    padding: 20px;
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                    z-index: 10001;
                    display: flex;
                    align-items: flex-start;
                    gap: 16px;
                    transform: translateX(500px);
                    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                    border-left: 4px solid;
                }
                
                .toast-notification.show {
                    transform: translateX(0);
                }
                
                .toast-success { border-left-color: #10b981; }
                .toast-error { border-left-color: #ef4444; }
                .toast-warning { border-left-color: #f59e0b; }
                
                .toast-icon {
                    width: 44px;
                    height: 44px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    flex-shrink: 0;
                    font-size: 22px;
                }
                
                .toast-success .toast-icon {
                    background: rgba(16, 185, 129, 0.15);
                    color: #10b981;
                }
                
                .toast-error .toast-icon {
                    background: rgba(239, 68, 68, 0.15);
                    color: #ef4444;
                }
                
                .toast-warning .toast-icon {
                    background: rgba(245, 158, 11, 0.15);
                    color: #f59e0b;
                }
                
                .toast-content { flex: 1; }
                
                .toast-title {
                    display: block;
                    margin-bottom: 6px;
                    color: #1f2937;
                    font-size: 16px;
                    font-weight: 700;
                }
                
                .toast-message {
                    margin: 0;
                    color: #6b7280;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .toast-close {
                    width: 28px;
                    height: 28px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent;
                    border: none;
                    color: #9ca3af;
                    cursor: pointer;
                    border-radius: 8px;
                    transition: all 0.2s ease;
                    flex-shrink: 0;
                }
                
                .toast-close:hover {
                    background: rgba(0, 0, 0, 0.05);
                    color: #1f2937;
                }
                
                @media (max-width: 768px) {
                    .toast-notification {
                        top: 16px;
                        right: 16px;
                        left: 16px;
                        min-width: auto;
                        max-width: none;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();