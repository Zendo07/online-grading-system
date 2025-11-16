document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    let sidebarTimeout;
    
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    if (hamburgerMenu && sidebar) {
        hamburgerMenu.addEventListener('mouseenter', function() {
            clearTimeout(sidebarTimeout);
            showSidebar();
        });
        
        sidebar.addEventListener('mouseenter', function() {
            clearTimeout(sidebarTimeout);
        });
        
        sidebar.addEventListener('mouseleave', function() {
            sidebarTimeout = setTimeout(function() {
                hideSidebar();
            }, 300);
        });
        
        hamburgerMenu.addEventListener('mouseleave', function() {
            sidebarTimeout = setTimeout(function() {
                if (!sidebar.matches(':hover')) {
                    hideSidebar();
                }
            }, 300);
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            hideSidebar();
        });
    }
    
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && 
                !hamburgerMenu.contains(e.target) && 
                sidebar.classList.contains('show')) {
                hideSidebar();
            }
        }
    });
    
    const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.nav-dropdown');
            parent.classList.toggle('active');
        });
    });
    
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
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 1024) {
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

window.scrollToTop = function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};