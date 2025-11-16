(function() {
    'use strict';

    function initProfile() {
        setupProfilePictureUpload();
        setupFlashMessages();
        setupButtonAnimations();
        addNotificationStyles();
    }

    function setupProfilePictureUpload() {
        const uploadBtn = document.querySelector('.btn-change-photo');
        
        if (uploadBtn && !uploadBtn.disabled) {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/jpeg,image/png,image/jpg';
            fileInput.style.display = 'none';
            fileInput.id = 'profilePictureInput';
            document.body.appendChild(fileInput);
            
            uploadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showNotification('File Too Large', 'File size must be less than 5MB', 'error');
                        return;
                    }
                    
                    // Validate file type
                    if (!file.type.match('image/(jpeg|jpg|png)')) {
                        showNotification('Invalid File', 'Only JPG, JPEG, and PNG files are allowed', 'error');
                        return;
                    }
                    
                    uploadProfilePicture(file);
                }
            });
        }
    }

    function uploadProfilePicture(file) {
        const formData = new FormData();
        formData.append('profile_picture', file);
        
        const uploadBtn = document.querySelector('.btn-change-photo');
        const originalHTML = uploadBtn.innerHTML;
        
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        uploadBtn.disabled = true;
        uploadBtn.style.cursor = 'not-allowed';
        
        fetch(window.BASE_URL + 'api/student/upload-profile-picture.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const timestamp = new Date().getTime();
                
                const profileImg = document.getElementById('profileImage');
                if (profileImg) {
                    profileImg.style.transition = 'opacity 0.5s ease';
                    profileImg.style.opacity = '0';
                    setTimeout(() => {
                        profileImg.src = data.picture_url + '&cache=' + timestamp;
                        profileImg.style.opacity = '1';
                    }, 500);
                }
                
                const navbarImg = document.querySelector('.profile-button img');
                if (navbarImg) {
                    navbarImg.src = data.picture_url + '&cache=' + timestamp;
                }
                
                const sidebarImg = document.querySelector('.sidebar-user-avatar img');
                if (sidebarImg) {
                    sidebarImg.src = data.picture_url + '&cache=' + timestamp;
                }
                
                showNotification('Success!', 'Profile picture updated successfully', 'success');
            } else {
                showNotification('Upload Failed', data.message || 'Failed to upload profile picture', 'error');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            showNotification('Error', 'An error occurred while uploading. Please try again.', 'error');
        })
        .finally(() => {
            uploadBtn.innerHTML = originalHTML;
            uploadBtn.disabled = false;
            uploadBtn.style.cursor = 'pointer';
        });
    }

    function showNotification(title, message, type) {
        const existingNotifications = document.querySelectorAll('.profile-notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = `profile-notification profile-notification-${type}`;
        
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="notification-content">
                <strong class="notification-title">${title}</strong>
                <p class="notification-message">${message}</p>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
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
        if (!document.getElementById('profile-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'profile-notification-styles';
            style.textContent = `
                .profile-notification {
                    position: fixed;
                    top: 24px;
                    right: 24px;
                    min-width: 350px;
                    max-width: 420px;
                    padding: 20px;
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                    z-index: 10000;
                    display: flex;
                    align-items: flex-start;
                    gap: 16px;
                    transform: translateX(500px);
                    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                    border-left: 4px solid;
                }
                
                .profile-notification.show {
                    transform: translateX(0);
                }
                
                .profile-notification-success {
                    border-left-color: #10b981;
                }
                
                .profile-notification-error {
                    border-left-color: #ef4444;
                }
                
                .notification-icon {
                    width: 44px;
                    height: 44px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    flex-shrink: 0;
                    font-size: 22px;
                }
                
                .profile-notification-success .notification-icon {
                    background: rgba(16, 185, 129, 0.15);
                    color: #10b981;
                }
                
                .profile-notification-error .notification-icon {
                    background: rgba(239, 68, 68, 0.15);
                    color: #ef4444;
                }
                
                .notification-content {
                    flex: 1;
                }
                
                .notification-title {
                    display: block;
                    margin-bottom: 6px;
                    color: #1f2937;
                    font-size: 16px;
                    font-weight: 700;
                }
                
                .notification-message {
                    margin: 0;
                    color: #6b7280;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .notification-close {
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
                
                .notification-close:hover {
                    background: rgba(0, 0, 0, 0.05);
                    color: #1f2937;
                }
                
                @media (max-width: 768px) {
                    .profile-notification {
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

    function setupFlashMessages() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(alert => {
            if (!alert.querySelector('.alert-close')) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'alert-close';
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.style.cssText = `
                    background: none;
                    border: none;
                    color: inherit;
                    cursor: pointer;
                    padding: 0;
                    margin-left: auto;
                    opacity: 0.7;
                    transition: opacity 0.2s;
                    font-size: 16px;
                `;
                closeBtn.onmouseover = () => closeBtn.style.opacity = '1';
                closeBtn.onmouseout = () => closeBtn.style.opacity = '0.7';
                closeBtn.onclick = function() {
                    alert.style.transform = 'translateX(500px)';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                };
                alert.appendChild(closeBtn);
            }
            
            setTimeout(() => {
                alert.style.transition = 'transform 0.5s ease, opacity 0.5s ease';
                alert.style.transform = 'translateX(500px)';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    }

    function setupButtonAnimations() {
        const buttons = document.querySelectorAll('.action-button-compact');
        
        buttons.forEach((button, index) => {
            // Staggered fade-in animation
            button.style.opacity = '0';
            button.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                button.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                button.style.opacity = '1';
                button.style.transform = 'translateY(0)';
            }, 200 * (index + 1));

            // Add keyboard support
            button.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProfile);
    } else {
        initProfile();
    }

    window.StudentProfile = {
        showNotification: showNotification
    };

})();