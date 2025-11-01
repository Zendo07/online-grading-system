/**
 * Student Profile Page - Interactive Functionality
 */

(function() {
    'use strict';

    /**
     * Initialize profile page on DOM load
     */
    function initProfile() {
        setupTabNavigation();
        setupAnimations();
    }

    /**
     * Setup tab navigation functionality
     */
    function setupTabNavigation() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');

                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Add active class to clicked button and corresponding content
                this.classList.add('active');
                const activeContent = document.getElementById(`tab-${tabName}`);
                if (activeContent) {
                    activeContent.classList.add('active');
                }

                // Log tab change for analytics (optional)
                console.log(`Switched to: ${tabName} tab`);
            });
        });
    }

    /**
     * Setup smooth animations for elements
     */
    function setupAnimations() {
        // Add intersection observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = entry.target.getAttribute('data-animation') || 'fadeIn 0.5s ease';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe overview items
        const overviewItems = document.querySelectorAll('.overview-item');
        overviewItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.animationDelay = `${index * 100}ms`;
            observer.observe(item);
        });

        // Observe help items
        const helpItems = document.querySelectorAll('.help-item');
        helpItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.animationDelay = `${index * 100}ms`;
            observer.observe(item);
        });

        // Observe setting items
        const settingItems = document.querySelectorAll('.setting-item');
        settingItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.animationDelay = `${index * 100}ms`;
            observer.observe(item);
        });
    }

    /**
     * Handle profile image preview (for future use)
     */
    function setupProfileImagePreview() {
        const profileImage = document.getElementById('profileImage');
        const changePhotoBtn = document.querySelector('.btn-change-photo');

        if (changePhotoBtn && !changePhotoBtn.disabled) {
            changePhotoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Future implementation for profile photo change
                console.log('Profile photo change will be implemented');
            });
        }
    }

    /**
     * Smooth scroll to element
     */
    function smoothScroll(element) {
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    /**
     * Handle responsive tab behavior
     */
    function handleResponsiveTabs() {
        const tabsContainer = document.querySelector('.profile-tabs');
        const tabButtons = document.querySelectorAll('.tab-button');

        if (window.innerWidth <= 768) {
            // Mobile adjustments if needed
            console.log('Mobile view - tabs adjusted');
        }
    }

    /**
     * Setup click handlers for overview links
     */
    function setupOverviewLinks() {
        const overviewLinks = document.querySelectorAll('.overview-link');

        overviewLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Log click analytics (optional)
                const linkText = this.textContent;
                console.log(`Clicked: ${linkText.trim()}`);
            });
        });
    }

    /**
     * Monitor window resize for responsive behavior
     */
    function setupResizeListener() {
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                handleResponsiveTabs();
            }, 250);
        });
    }

    /**
     * Add keyboard navigation support
     */
    function setupKeyboardNavigation() {
        document.addEventListener('keydown', function(e) {
            const tabButtons = document.querySelectorAll('.tab-button');
            const activeButton = document.querySelector('.tab-button.active');
            const activeIndex = Array.from(tabButtons).indexOf(activeButton);

            // Arrow keys to navigate tabs
            if (e.key === 'ArrowRight' && activeIndex < tabButtons.length - 1) {
                tabButtons[activeIndex + 1].click();
                tabButtons[activeIndex + 1].focus();
            } else if (e.key === 'ArrowLeft' && activeIndex > 0) {
                tabButtons[activeIndex - 1].click();
                tabButtons[activeIndex - 1].focus();
            }
        });
    }

    /**
     * Auto-dismiss alerts
     */
    function setupAlertDismissal() {
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
    }

    /**
     * Setup tab button focus styles for accessibility
     */
    function setupA11y() {
        const tabButtons = document.querySelectorAll('.tab-button');

        tabButtons.forEach(button => {
            button.setAttribute('role', 'tab');
            button.setAttribute('tabindex', '0');

            button.addEventListener('focus', function() {
                this.style.outline = '2px solid var(--maroon)';
                this.style.outlineOffset = '-2px';
            });

            button.addEventListener('blur', function() {
                this.style.outline = 'none';
            });
        });
    }

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initProfile();
            setupProfileImagePreview();
            setupOverviewLinks();
            setupResizeListener();
            setupKeyboardNavigation();
            setupAlertDismissal();
            setupA11y();
        });
    } else {
        initProfile();
        setupProfileImagePreview();
        setupOverviewLinks();
        setupResizeListener();
        setupKeyboardNavigation();
        setupAlertDismissal();
        setupA11y();
    }

    /**
     * Export for global use
     */
    window.StudentProfile = {
        smoothScroll,
        handleResponsiveTabs
    };

})();