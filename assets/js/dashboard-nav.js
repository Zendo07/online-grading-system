// Modern Dashboard Navigation JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    let sidebarTimeout;
    
    // Toggle sidebar on hamburger click
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // Show sidebar on hamburger hover
    if (hamburgerMenu && sidebar) {
        hamburgerMenu.addEventListener('mouseenter', function() {
            clearTimeout(sidebarTimeout);
            showSidebar();
        });
        
        // Keep sidebar open when hovering over it
        sidebar.addEventListener('mouseenter', function() {
            clearTimeout(sidebarTimeout);
        });
        
        // Hide sidebar when mouse leaves
        sidebar.addEventListener('mouseleave', function() {
            sidebarTimeout = setTimeout(function() {
                hideSidebar();
            }, 300);
        });
        
        // Also handle hamburger menu leave
        hamburgerMenu.addEventListener('mouseleave', function() {
            sidebarTimeout = setTimeout(function() {
                if (!sidebar.matches(':hover')) {
                    hideSidebar();
                }
            }, 300);
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            hideSidebar();
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && 
                !hamburgerMenu.contains(e.target) && 
                sidebar.classList.contains('show')) {
                hideSidebar();
            }
        }
    });
    
    // Dropdown functionality
    const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.nav-dropdown');
            parent.classList.toggle('active');
        });
    });
    
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
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // On desktop, you might want to keep sidebar visible
            // Adjust based on your preference
            if (window.innerWidth > 1024) {
                // Optionally show sidebar by default on large screens
                // showSidebar();
            }
        }, 250);
    });
});

function showSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar) {
        sidebar.classList.add('show');
    }
    
    // Show overlay on mobile
    if (window.innerWidth <= 768 && overlay) {
        overlay.classList.add('show');
    }
}

function hideSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar) {
        sidebar.classList.remove('show');
    }
    
    if (overlay) {
        overlay.classList.remove('show');
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    
    if (sidebar) {
        if (sidebar.classList.contains('show')) {
            hideSidebar();
        } else {
            showSidebar();
        }
    }
}

// Smooth scroll to top
window.scrollToTop = function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};