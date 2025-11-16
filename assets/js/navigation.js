(function() {
    'use strict';
    
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    const navLinks = document.querySelectorAll('.nav-item a:not(.nav-dropdown-toggle)');
    
    let isSidebarOpen = false;
    let hoverTimeout = null;
    let closeTimeout = null;
    let isHoverMode = window.innerWidth > 768;
    let isMouseOverNav = false;
    function updateDeviceMode() {
        isHoverMode = window.innerWidth > 768;
    }
    
    function openSidebar() {
        if (isSidebarOpen) return;
        if (closeTimeout) {
            clearTimeout(closeTimeout);
            closeTimeout = null;
        }
        
        sidebar.classList.add('show');
        sidebarOverlay.classList.add('show');
        isSidebarOpen = true;
        
        if (!isHoverMode) {
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeSidebar() {
        if (!isSidebarOpen) return;
        if (hoverTimeout) {
            clearTimeout(hoverTimeout);
            hoverTimeout = null;
        }
        
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
        isSidebarOpen = false;
        document.body.style.overflow = '';
    }
    
    function toggleSidebar(forceClose = false) {
        if (forceClose || isSidebarOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }
    
    
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('mouseenter', () => {
            if (!isHoverMode) return;
            if (closeTimeout) {
                clearTimeout(closeTimeout);
                closeTimeout = null;
            }
            openSidebar();
        });
        
        hamburgerMenu.addEventListener('mouseleave', () => {
            if (!isHoverMode) return;
            
            if (!isMouseOverNav) {
                closeTimeout = setTimeout(() => {
                    closeSidebar();
                }, 200); 
            }
        });
        
        hamburgerMenu.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    if (sidebar) {
        sidebar.addEventListener('mouseenter', () => {
            if (!isHoverMode) return;
            
            isMouseOverNav = true;
            
            if (closeTimeout) {
                clearTimeout(closeTimeout);
                closeTimeout = null;
            }
        });
        
        sidebar.addEventListener('mouseleave', () => {
            if (!isHoverMode) return;
            isMouseOverNav = false;
            closeTimeout = setTimeout(() => {
                closeSidebar();
            }, 200);
        });
        
        sidebar.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            closeSidebar();
        });
        
        sidebarOverlay.addEventListener('mouseenter', () => {
            if (isHoverMode) {
                closeTimeout = setTimeout(() => {
                    closeSidebar();
                }, 100);
            }
        });
    }
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
        
        if (toggle) {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('active');
                    }
                });
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // ==================== LOGOUT MODAL FUNCTIONALITY ====================
    const logoutTriggers = document.querySelectorAll('.js-logout-trigger');
    const logoutModalOverlay = document.getElementById('logoutModalOverlay');
    const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    
    function openLogoutModal(e) {
        e.preventDefault();
        e.stopPropagation();
        closeSidebar(); // Close sidebar first
        if (logoutModalOverlay) {
            logoutModalOverlay.classList.add('open');
        }
    }
    
    function closeLogoutModal() {
        if (logoutModalOverlay) {
            logoutModalOverlay.classList.remove('open');
        }
    }
    
    function performLogout() {
        // Clear localStorage
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith('profile_') || key === 'current_user_id') {
                localStorage.removeItem(key);
            }
        });
        
        // Clear sessionStorage
        sessionStorage.clear();
        
        // Redirect to logout.php to destroy session
        window.location.href = window.BASE_URL + 'auth/logout.php?confirmed=1';
    }
    
    // Logout modal event listeners
    if (logoutTriggers.length > 0 && logoutModalOverlay) {
        logoutTriggers.forEach(function(trigger) {
            trigger.addEventListener('click', openLogoutModal);
        });
    }
    
    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', closeLogoutModal);
    }
    
    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', performLogout);
    }
    
    if (logoutModalOverlay) {
        logoutModalOverlay.addEventListener('click', function(e) {
            if (e.target === logoutModalOverlay) {
                closeLogoutModal();
            }
        });
    }
    // ==================== END LOGOUT MODAL ====================
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            
            if (href && href.includes('logout.php')) {
                return;
            }
            
            if (isSidebarOpen) {
                closeSidebar();
            }
        });
    });
    
    const dropdownLinks = document.querySelectorAll('.nav-dropdown-menu a');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            
            if (href && !href.includes('logout.php')) {
                if (isSidebarOpen) {
                    closeSidebar();
                }
            }
        });
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (logoutModalOverlay && logoutModalOverlay.classList.contains('open')) {
                closeLogoutModal();
            } else if (isSidebarOpen) {
                closeSidebar();
            }
        }
    });
    
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            updateDeviceMode();
            
            // Close sidebar on resize to desktop if open
            if (window.innerWidth > 768 && isSidebarOpen) {
                closeSidebar();
            }
        }, 250);
    });
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '') {
                e.preventDefault();
                const target = document.querySelector(href);
                
                if (target) {
                    if (isSidebarOpen) {
                        closeSidebar();
                    }
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    const currentPath = window.location.pathname;
    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref && linkHref.includes(currentPage)) {
            link.classList.add('active');
            
            const parentDropdown = link.closest('.nav-dropdown');
            if (parentDropdown) {
                parentDropdown.classList.add('active');
            }
        }
    });
    
    let lastClickTime = 0;
    const clickDelay = 300;
    
    document.addEventListener('click', (e) => {
        const now = Date.now();
        if (now - lastClickTime < clickDelay) {
            const target = e.target.closest('a, button');
            if (target) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
        lastClickTime = now;
    }, true);
    
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('Clean Hover Navigation Initialized');
        console.log('Mode:', isHoverMode ? 'Desktop (Hover)' : 'Mobile (Click)');
        console.log('Current Page:', currentPage);
    }
    
})();

// ==================== MINIMAL CSS OVERRIDES ====================
const style = document.createElement('style');
style.textContent = `
    /* Remove all transitions and animations */
    #sidebar,
    #sidebarOverlay,
    .sidebar,
    .sidebar-overlay,
    .hamburger-menu,
    .hamburger-line,
    .nav-item a,
    .nav-dropdown-menu,
    body,
    .main-content {
        transition: none !important;
        animation: none !important;
    }
    
    /* Clean hamburger - white, no background */
    .hamburger-menu {
        background: transparent !important;
        border: none !important;
    }
    
    .hamburger-menu::before {
        display: none !important;
    }
    
    .hamburger-menu:hover {
        background: transparent !important;
        transform: none !important;
    }
    
    .hamburger-line {
        background: white !important;
    }
    
    .hamburger-menu:hover .hamburger-line {
        background: white !important;
    }
    
    /* Remove backdrop blur */
    .sidebar-overlay,
    #sidebarOverlay {
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    
    /* Instant sidebar show/hide */
    #sidebar {
        transition: none !important;
    }
    
    #sidebar.show {
        transform: translateX(0) !important;
    }
    
    /* Remove all page transition effects */
    body {
        opacity: 1 !important;
    }
    
    body.loaded {
        opacity: 1 !important;
    }
    
    .main-content {
        animation: none !important;
    }
    
    /* Remove navigating state */
    .navigating {
        opacity: 1 !important;
        pointer-events: auto !important;
    }
    
    /* Remove ripple effects */
    .ripple-effect {
        display: none !important;
    }
    
    /* Clean hover effects */
    @media (min-width: 769px) {
        .nav-item a:hover {
            transition: background-color 0.1s ease, padding-left 0.1s ease !important;
        }
    }
    
    /* Instant dropdown */
    .nav-dropdown-menu {
        transition: none !important;
    }
    
    .nav-dropdown.active .nav-dropdown-menu {
        max-height: 500px !important;
        opacity: 1 !important;
    }
`;
document.head.appendChild(style);
