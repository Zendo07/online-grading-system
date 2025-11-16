(function() {
    'use strict';
    
    console.log('=== MY COURSES JS INITIALIZED ===');
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('Initializing My Courses page...');
        
        initAlerts();
        initDropdowns();
        initKeyboardNav();
        initUnenrollModal();
        
        console.log('My Courses initialization complete');
    }
    
    function initAlerts() {
        const alerts = document.querySelectorAll('.alert');
        console.log(`Found ${alerts.length} alert(s)`);
        
        alerts.forEach((alert, index) => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    alert.remove();
                    console.log(`Alert ${index + 1} dismissed`);
                }, 500);
            }, 5000);
        });
    }
    
    function initDropdowns() {
        console.log('Initializing dropdown menus...');
        
        // Handle dropdown toggle clicks
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('.course-menu-toggle');
            
            if (toggle) {
                handleDropdownToggle(e, toggle);
                return;
            }
            
            if (!e.target.closest('.course-menu') && !e.target.closest('.course-menu-toggle')) {
                closeAllDropdowns();
            }
        });
        
        console.log('Dropdown menus initialized');
    }
    
    function handleDropdownToggle(event, toggle) {
        event.preventDefault();
        event.stopPropagation();
        
        const courseId = toggle.getAttribute('data-course-id');
        console.log(`Dropdown toggle clicked for course ${courseId}`);
        
        const menu = document.querySelector(`.course-menu[data-course-id="${courseId}"]`);
        
        if (!menu) {
            console.error(`Menu not found for course ${courseId}`);
            return;
        }
        
        document.querySelectorAll('.course-menu').forEach(m => {
            if (m !== menu) {
                m.classList.remove('show');
            }
        });
        
        const isShowing = menu.classList.toggle('show');
        console.log(`Menu for course ${courseId} is now ${isShowing ? 'open' : 'closed'}`);
        
        toggle.setAttribute('aria-expanded', isShowing);
        menu.setAttribute('aria-hidden', !isShowing);
    }
    
    function closeAllDropdowns() {
        const openMenus = document.querySelectorAll('.course-menu.show');
        if (openMenus.length > 0) {
            console.log(`Closing ${openMenus.length} open menu(s)`);
            openMenus.forEach(menu => {
                menu.classList.remove('show');
                menu.setAttribute('aria-hidden', 'true');
            });
            
            document.querySelectorAll('.course-menu-toggle').forEach(toggle => {
                toggle.setAttribute('aria-expanded', 'false');
            });
        }
    }

    function initKeyboardNav() {
        console.log('Initializing keyboard navigation...');
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openMenus = document.querySelectorAll('.course-menu.show');
                if (openMenus.length > 0) {
                    console.log('ESC pressed - closing menus');
                    closeAllDropdowns();
                    
                    const lastToggle = document.querySelector('.course-menu-toggle[aria-expanded="true"]');
                    if (lastToggle) {
                        lastToggle.focus();
                    }
                }
                
                const modal = document.getElementById('unenrollModal');
                if (modal && modal.classList.contains('show')) {
                    closeUnenrollModal();
                }
            }
            
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                const activeMenu = document.querySelector('.course-menu.show');
                if (activeMenu) {
                    e.preventDefault();
                    handleMenuArrowNavigation(e, activeMenu);
                }
            }
        });
        
        console.log('Keyboard navigation initialized');
    }

    function handleMenuArrowNavigation(event, menu) {
        const items = Array.from(menu.querySelectorAll('.course-menu-item'));
        const currentIndex = items.findIndex(item => item === document.activeElement);
        
        let nextIndex;
        if (event.key === 'ArrowDown') {
            nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
        } else {
            nextIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
        }
        
        items[nextIndex].focus();
    }

    function initUnenrollModal() {
        console.log('Initializing unenroll modal...');
        
        createUnenrollModal();
        
        document.querySelectorAll('.unenroll-form').forEach(form => {
            const submitBtn = form.querySelector('.course-menu-item');
            if (submitBtn) {
                submitBtn.removeAttribute('onclick');
                const newBtn = submitBtn.cloneNode(true);
                submitBtn.parentNode.replaceChild(newBtn, submitBtn);
            }
        });
        
        document.querySelectorAll('.unenroll-form').forEach(form => {
            form.onsubmit = null;
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const classId = this.querySelector('input[name="class_id"]').value;
                const className = this.querySelector('.course-menu-item').dataset.className;
                
                console.log('Unenroll clicked - showing custom modal');
                console.log('Class ID:', classId);
                console.log('Class Name:', className);
                
                showUnenrollModal(classId, className, this);
                
                return false;
            }, true);
            
            const submitBtn = form.querySelector('.course-menu-item');
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    const classId = form.querySelector('input[name="class_id"]').value;
                    const className = this.dataset.className;
                    
                    showUnenrollModal(classId, className, form);
                    
                    return false;
                }, true);
            }
        });
        
        console.log('Unenroll modal initialized - browser alerts disabled');
    }

    function createUnenrollModal() {
        // Check if modal already exists
        if (document.getElementById('unenrollModal')) {
            console.log('Modal already exists');
            return;
        }
        
        const modal = document.createElement('div');
        modal.id = 'unenrollModal';
        modal.className = 'unenroll-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'modalTitle');
        
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 id="modalTitle" class="modal-title">Unenroll from Class?</h2>
                <p class="modal-message">
                    Are you sure you want to unenroll from 
                    <span class="modal-class-name" id="modalClassName"></span>?
                    <br><br>
                    You will lose access to all course materials and your progress. 
                    You can rejoin anytime using the class code.
                </p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" id="modalCancel">
                        Cancel
                    </button>
                    <button type="button" class="modal-btn modal-btn-confirm" id="modalConfirm">
                        Yes, Unenroll
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        console.log('Modal created and added to DOM');
        
        // Add event listeners
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeUnenrollModal();
            }
        });
        
        document.getElementById('modalCancel').addEventListener('click', closeUnenrollModal);
    }
    
    function showUnenrollModal(classId, className, form) {
        const modal = document.getElementById('unenrollModal');
        const classNameElement = document.getElementById('modalClassName');
        const confirmBtn = document.getElementById('modalConfirm');
        
        if (!modal || !classNameElement || !confirmBtn) {
            console.error('Modal elements not found');
            return;
        }
        
        // Set class name
        classNameElement.textContent = className;
        
        // Close any open dropdowns
        closeAllDropdowns();
        
        // Show modal
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Focus on cancel button for accessibility
        setTimeout(() => {
            document.getElementById('modalCancel').focus();
        }, 100);
        
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.addEventListener('click', function() {
            console.log('Unenroll confirmed - submitting form');
            modal.classList.remove('show');
            document.body.style.overflow = '';
            
            form.submit();
        });
        
        console.log('Modal shown for class:', className);
    }
    
    function closeUnenrollModal() {
        const modal = document.getElementById('unenrollModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            console.log('Modal closed');
        }
    }
    
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    window.closeAllDropdowns = closeAllDropdowns;
    window.scrollToTop = scrollToTop;
    window.closeUnenrollModal = closeUnenrollModal;
    
    console.log('=== MY COURSES JS READY ===');
    
})();